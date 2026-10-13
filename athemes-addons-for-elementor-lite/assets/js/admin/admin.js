"use strict";

;
(function ($, window, document, undefined) {
	'use strict';

	var athemes_addons_elementor = athemes_addons_elementor || {};
	$(document).ready(function () {

		// Failed saves are usually an expired nonce, which only a reload fixes.
		function showSaveError(message) {
			window.alert(message || window.athemes_addons_elementor.save_failed);
		}

		// Each save rewrites the whole modules option, so send them one at a time.
		var moduleSaveQueue = $.Deferred().resolve().promise();

		function queueModuleSave(send) {
			var saved = $.Deferred();

			moduleSaveQueue.always(function () {
				send().done(saved.resolve).fail(saved.reject);
			});

			moduleSaveQueue = saved.promise();

			return moduleSaveQueue;
		}

		// Module switches only. Settings toggles share the markup but save via the Save button.
		$('.athemes-addons-toggle-switch[data-module] .toggle-switch-checkbox').on('change', function (e) {
			var $checkbox = $(this);
			var $button = $checkbox.closest('.athemes-addons-toggle-switch');
			var isActivate = $checkbox.is(':checked');

			$button.toggleClass('athemes-addons-module-page-button-action-deactivate', isActivate);
			$button.toggleClass('athemes-addons-module-page-button-action-activate', !isActivate);

			// Lock until the request settles so a failure reverts the right state.
			$checkbox.prop('disabled', true);
		
			var ajaxAction = isActivate ? 'athemes_addons_module_activate' : 'athemes_addons_module_deactivate';
		
			queueModuleSave(function () {
				return window.wp.ajax.post(ajaxAction, {
					module: $button.data('module'),
					nonce: window.athemes_addons_elementor.nonce
				});
			}).done(function () {
				$button.find('.saved-label').css('opacity', '1');
				setTimeout(() => {
					$button.find('.saved-label').css('opacity', '0');
				}, 2000);

				var isModuleEnabled = isActivate;
				$('body').toggleClass('athemes-addons-module-disabled', !isModuleEnabled).toggleClass('athemes-addons-module-enabled', isModuleEnabled);
				$('.athemes-addons-module-action').toggleClass('athemes-addons-enabled', isModuleEnabled);
				$('.athemes-addons-module-question-list-dropdown').toggleClass('athemes-addons-show', !isModuleEnabled);
			}).fail(function () {
				$checkbox.prop('checked', !isActivate);
				$button.toggleClass('athemes-addons-module-page-button-action-deactivate', !isActivate);
				$button.toggleClass('athemes-addons-module-page-button-action-activate', isActivate);
				showSaveError();
			}).always(function () {
				$checkbox.prop('disabled', false);
			});
		});	


		// Notifications Sidebar
		var $notificationsSidebar = $('.athemes-addons-notifications-sidebar');
		if ($notificationsSidebar.length) {
			var $notifications = $('.athemes-addons-notifications');
			$notifications.on('click', function (e) {
				e.preventDefault();
				var $notification = $(this);
				var latestNotificationDate = $notificationsSidebar.find('.athemes-addons-notification:first-child .athemes-addons-notification-date').data('raw-date');
				$notificationsSidebar.toggleClass('opened');
				if (!$notification.hasClass('read')) {
					$.post(window.athemes_addons_elementor.ajax_url, {
						action: 'athemes_addons_notifications_read',
						nonce: window.athemes_addons_elementor.nonce,
						latest_notification_date: latestNotificationDate
					}, function (response) {
						if (response.success) {
							setTimeout(function () {
								$notification.addClass('read');
							}, 2000);
						}
					});
				}
			});
			$(window).on('scroll', function () {
				if (window.pageYOffset > 60) {
					$notificationsSidebar.addClass('closing');
					setTimeout(function () {
						$notificationsSidebar.removeClass('opened');
						$notificationsSidebar.removeClass('closing');
					}, 300);
				}
			});

			// Close Sidebar
			$('.athemes-addons-notifications-sidebar-close').on('click', function (e) {
				e.preventDefault();
				$notificationsSidebar.addClass('closing');
				setTimeout(function () {
					$notificationsSidebar.removeClass('opened');
					$notificationsSidebar.removeClass('closing');
				}, 300);
			});
		}

		// Tabs Navigation.
		var tabs = $('.athemes-addons-dashboard-tabs-nav');
		if (tabs.length) {
			var tabsNav = tabs.find('a');
			tabsNav.on('click', function (e) {
				e.preventDefault();
				var $tabContent = $(this).data('tab');

				$(this).addClass('active').siblings().removeClass('active');

				$('.athemes-addons-dashboard-tab-page').each(function () {
					if ($(this).attr('id') === $tabContent) {
						$(this).addClass('active');
					} else {
						$(this).removeClass('active');
					}
				});
			});
		}

		// Filters.
		var appliedFilters = {
			category: 'all',
			status: 'all',
			freePro: 'all'
		};

		// Category filter.
		var categoryFilter = $('.athemes-addons-modules-filter-button-category');
		categoryFilter.on('change', function (e) {
			var category = $(this).val();
			
			// Reset status/type filters to "all" when changing category
			appliedFilters.status = 'all';
			appliedFilters.freePro = 'all';
			$('input[name="athemes-addons-status-filter"][value="all"]').prop('checked', true);
			$('input[name="athemes-addons-free-pro-filter"][value="all"]').prop('checked', true);
			
			if (category === 'all') {
				// Show all categories
				$('.athemes-addons-modules-category').show();
				$('.athemes-addons-modules-list-item').show();
				$('.athemes-addons-modules-bulk').show();
				return;
			}
			
			// Find the category section to scroll to
			var $targetCategory = $('.athemes-addons-modules-category').filter(function() {
				// Find a category that has modules matching the selected category
				var hasMatchingModules = $(this).find('.athemes-addons-modules-list-item[data-category="' + category + '"]').length > 0;
				return hasMatchingModules;
			}).first();
			
			if ($targetCategory.length) {			
				// Scroll to the category
				$('html, body').animate({
					scrollTop: $targetCategory.offset().top - 100 // Increased offset to account for 80px sticky header plus some padding
				}, 500);
			}
		});

		// Status filter.
		var statusFilter = $('input[name="athemes-addons-status-filter"]');
		statusFilter.on('change', function (e) {
			var status = $(this).val();
			appliedFilters.status = status;
			filterModules();
		});

		// Free/pro filter.
		var freeProFilter = $('input[name="athemes-addons-free-pro-filter"]');
		freeProFilter.on('change', function (e) {
			var freePro = $(this).val();
			appliedFilters.freePro = freePro;
			filterModules();
		});

		// Function to filter modules based on applied filters
		function filterModules() {
			var $modules = $('.athemes-addons-modules-list-item');
			
			// First reset all modules to hidden
			$modules.hide();

			// Show modules that match all filters
			$modules.each(function () {
				var module = $(this);
				var meetsCategoryFilter = (appliedFilters.category === 'all') || (module.data('category') === appliedFilters.category);
				var meetsStatusFilter = (appliedFilters.status === 'all') || (module.data('status') === appliedFilters.status);
				var meetsFreeProFilter = (appliedFilters.freePro === 'all') || (module.data('type') === appliedFilters.freePro);

				if (meetsStatusFilter && meetsFreeProFilter) {
					module.show();
				}
			});

			// Update category visibility based on visible modules
			updateCategoryVisibility();
		}

		// Function to update category visibility
		function updateCategoryVisibility() {
			// Make all categories visible first
			$('.athemes-addons-modules-category').show();
			
			// Then hide the ones with no visible modules
			$('.athemes-addons-modules-category').each(function() {
				var $category = $(this);
				var $visibleModules = $category.find('.athemes-addons-modules-list-item:visible');

				// Bulk links only act on visible switches.
				$category.find('.athemes-addons-modules-bulk').toggle($visibleModules.find('.athemes-addons-toggle-switch[data-module]').length > 0);
				
				if ($visibleModules.length === 0) {
					$category.hide();
				}
			});
		}

		// Search.
		var searchInput = $('.athemes-addons-modules-search');
		searchInput.on('keyup', function (e) {
			var search = $(this).val().toLowerCase();
			var $modules = $('.athemes-addons-modules-list-item');

			// Reset module visibility
			$modules.hide();

			// Show modules that match the search
			$modules.each(function () {
				var module = $(this);
				var title = module.data('title').toLowerCase();
				var description = module.data('keywords') ? module.data('keywords').toLowerCase() : '';

				if (title.indexOf(search) > -1 || description.indexOf(search) > -1) {
					module.show();
				}
			});

			// Update category visibility
			updateCategoryVisibility();
		});

		// Enable all / Disable all per category.
		$('.athemes-addons-modules-bulk-action').on('click', function () {
			var $bulk     = $(this).closest('.athemes-addons-modules-bulk');
			var $actions  = $bulk.find('.athemes-addons-modules-bulk-action');
			var $feedback = $bulk.find('.athemes-addons-modules-bulk-feedback');
			var activate  = 'activate' === $(this).data('bulk');

			// Only visible switches that need to change. Locked Pro cards have none.
			var $checkboxes = $(this).closest('.athemes-addons-modules-category').find('.athemes-addons-modules-list-item:visible .athemes-addons-toggle-switch[data-module] .toggle-switch-checkbox').filter(function () {
				return $(this).is(':checked') !== activate;
			});

			if (!$checkboxes.length) {
				return;
			}

			var modules = $checkboxes.map(function () {
				return $(this).closest('.athemes-addons-toggle-switch').data('module');
			}).get();

			function setActive(isActive) {
				$checkboxes.prop('checked', isActive).each(function () {
					$(this).closest('.athemes-addons-toggle-switch')
						.toggleClass('athemes-addons-module-page-button-action-deactivate', isActive)
						.toggleClass('athemes-addons-module-page-button-action-activate', !isActive);
					$(this).closest('.athemes-addons-modules-list-item')
						.attr('data-status', isActive ? 'active' : 'inactive')
						.data('status', isActive ? 'active' : 'inactive');
				});
			}

			function onBulkFailed() {
				setActive(!activate);
				$feedback.addClass('is-error').text(window.athemes_addons_elementor.bulk_failed);
			}

			setActive(activate);
			$actions.prop('disabled', true);
			$checkboxes.prop('disabled', true);
			// A pending "Saved!" timer must not clear this request's message.
			clearTimeout($feedback.data('timer'));
			$feedback.removeClass('is-error').text('');

			queueModuleSave(function () {
				return $.post(window.athemes_addons_elementor.ajax_url, {
					action: 'athemes_addons_modules_bulk',
					nonce: window.athemes_addons_elementor.nonce,
					status: activate ? 'activate' : 'deactivate',
					modules: modules
				});
			}).done(function (response) {
				if (!response || !response.success) {
					onBulkFailed();
					return;
				}

				$feedback.text(window.athemes_addons_elementor.saved);
				$feedback.data('timer', setTimeout(function () {
					$feedback.text('');
				}, 2000));
			}).fail(onBulkFailed).always(function () {
				$actions.prop('disabled', false);
				$checkboxes.prop('disabled', false);
			});
		});

		// Pro upsell popup on locked cards.
		function closeProPopups() {
			$('.athemes-addons-pro-popup').prop('hidden', true);
			$('.athemes-addons-pro-lock').attr('aria-expanded', 'false');
		}

		$(document).on('click', '.athemes-addons-modules-list-item.is-locked', function (e) {
			// Let the popup links and the doc/demo icons work normally.
			if ($(e.target).closest('.athemes-addons-pro-popup, .athemes-addons-module-help-link').length) {
				return;
			}

			var $popup = $(this).find('.athemes-addons-pro-popup');
			var isOpen = !$popup.prop('hidden');

			closeProPopups();

			if (!isOpen) {
				$popup.prop('hidden', false);
				$(this).find('.athemes-addons-pro-lock').attr('aria-expanded', 'true');
			}
		});

		$(document).on('click', function (e) {
			if (!$(e.target).closest('.athemes-addons-modules-list-item.is-locked').length) {
				closeProPopups();
			}
		});

		$(document).on('keydown', function (e) {
			if ('Escape' !== e.key) {
				return;
			}

			var $openLock = $('.athemes-addons-pro-lock[aria-expanded="true"]');

			if ($openLock.length) {
				closeProPopups();
				$openLock.trigger('focus');
			}
		});

		// Settings page.
		$('.aafe-save-settings').on('click', function() {
			var fields = {};

			$(this).text(window.athemes_addons_elementor.saving);
	
			$('.athemes-addons-module-page-setting-fields input[type="text"]').each(function() {
				var fieldId = $(this).attr('name');
				var fieldValue = $(this).val();
				fields[fieldId] = fieldValue;
			});

			$('.athemes-addons-module-page-setting-field-multicheckbox').each(function() {
				var fieldId = $(this).find('input[type="hidden"]').attr('name');
				var fieldValue = $(this).find('input[type="hidden"]').val();
				fields[fieldId] = fieldValue;
			} );

			// Toggle field.
			$('.athemes-addons-module-page-setting-field-toggle input[type="checkbox"]').each(function() {
				var fieldId = $(this).attr('name');
				var fieldValue = $(this).is(':checked') ? 'on' : '';
				fields[fieldId] = fieldValue;
			});
	
			var data = {
				'action': 'aafe_save_settings',
				'nonce': window.athemes_addons_elementor.nonce,
				'fields': fields
			};
	
			function onSaveFailed() {
				$('.aafe-save-settings').text(window.athemes_addons_elementor.save);
				showSaveError();
			}

			$.ajax({
				type: 'POST',
				url: window.athemes_addons_elementor.ajax_url,
				data: data,
				success: function(response) {
					if (!response || !response.success) {
						onSaveFailed();
						return;
					}

					$('.aafe-save-settings').text(window.athemes_addons_elementor.saved);
					setTimeout(() => {
						$('.aafe-save-settings').text(window.athemes_addons_elementor.save);
					}, 2000);
				},
				error: function(error) {
					console.error(error);
					onSaveFailed();
				}
			});
		});

		// AI Abilities mode (off/read/write). Saves immediately via a
		// dedicated handler, separate from the generic aafe-save-settings
		// flow above.
		var abilitiesPreviousMode = $('.athemes-addons-abilities-mode-radio').filter(':checked').val();

		$(document).on('change', '.athemes-addons-abilities-mode-radio', function () {
			var $radios      = $('.athemes-addons-abilities-mode-radio');
			var $feedback    = $('.athemes-addons-abilities-mode-feedback');
			var mode         = $(this).val();
			var previousMode = abilitiesPreviousMode;

			$radios.prop('disabled', true);
			$feedback.hide();

			function revertToPreviousMode() {
				$radios.filter('[value="' + previousMode + '"]').prop('checked', true);
			}

			$.post(window.athemes_addons_elementor.ajax_url, {
				action: 'athemes_addons_abilities_mode',
				nonce: window.athemes_addons_elementor.nonce,
				mode: mode
			}).done(function (response) {
				if (!response || !response.success) {
					$feedback.text(window.athemes_addons_elementor.failed).show();
					revertToPreviousMode();
				} else {
					abilitiesPreviousMode = mode;
				}
			}).fail(function () {
				$feedback.text(window.athemes_addons_elementor.failed).show();
				revertToPreviousMode();
			}).always(function () {
				$radios.prop('disabled', false);
			});
		});

		// Multichecbox control.
		$( '.athemes-addons-module-page-setting-field-multicheckbox' ).each(function () {
			var $control = $(this);
			var $checkboxes = $control.find( 'input[type="checkbox"]' );
			var $allCheckbox = $checkboxes.filter( '[value="all"]' );
			var $specificCheckboxes = $checkboxes.not( '[value="all"]' );

			$checkboxes.on( 'change', function () {
				var $changedCheckbox = $(this);
				
				// If "All" was checked, uncheck all specific options
				if ( $changedCheckbox.val() === 'all' && $changedCheckbox.is( ':checked' ) ) {
					$specificCheckboxes.prop( 'checked', false );
				}
				// If any specific option was checked, uncheck "All"
				else if ( $changedCheckbox.val() !== 'all' && $changedCheckbox.is( ':checked' ) ) {
					$allCheckbox.prop( 'checked', false );
				}
				// If all specific options are unchecked, check "All"
				else if ( $changedCheckbox.val() !== 'all' && !$changedCheckbox.is( ':checked' ) ) {
					var hasCheckedSpecific = $specificCheckboxes.filter( ':checked' ).length > 0;
					if ( !hasCheckedSpecific ) {
						$allCheckbox.prop( 'checked', true );
					}
				}

				var values = [];

				$checkboxes.each( function () {
					if ( $( this ).is( ':checked' ) ) {
						values.push( $( this ).val() );
					}
				} );

				$control.find( 'input[type="hidden"]' ).val( values.join( ',' ) ).trigger( 'change' );
			} );
		} );
		
		// Display conditions.
		//Display conditions
		$(document).on('athemes-addons-display-conditions-select2-initalize', function (event, item) {
			var $item = $(item);
			var $control = $item.closest('.athemes-addons-display-conditions-control');
			var $typeSelectWrap = $item.find('.athemes-addons-display-conditions-select2-type');
			var $typeSelect = $typeSelectWrap.find('select');
			var $conditionSelectWrap = $item.find('.athemes-addons-display-conditions-select2-condition');
			var $conditionSelect = $conditionSelectWrap.find('select');
			var $idSelectWrap = $item.find('.athemes-addons-display-conditions-select2-id');
			var $idSelect = $idSelectWrap.find('select');
			$typeSelect.select2({
			  width: '100%',
			  minimumResultsForSearch: -1
			});
			$typeSelect.on('select2:select', function (event) {
			  $typeSelectWrap.attr('data-type', event.params.data.id);
			});
			$conditionSelect.select2({
			  width: '100%'
			});
			$conditionSelect.on('select2:select', function (event) {
			  var $element = $(event.params.data.element);
		
			  if ($element.data('ajax')) {
				$idSelectWrap.removeClass('hidden');
			  } else {
				$idSelectWrap.addClass('hidden');
			  }

			  $idSelect.val(null).trigger('change');
			});
			var isAjaxSelected = $conditionSelect.find(':selected').data('ajax');

			if (isAjaxSelected) {
			  $idSelectWrap.removeClass('hidden');
			}
		
			$idSelect.select2({
			  width: '100%',
			  placeholder: '',
			  allowClear: true,
			  minimumInputLength: 1,
			  ajax: {
				url: window.athemes_addons_elementor.ajax_url,
				dataType: 'json',
				delay: 250,
				cache: true,
				data: function data(params) {
				  return {
					action: 'athemes_addons_templates_display_conditions_select_ajax',
					term: params.term,
					nonce: window.athemes_addons_elementor.nonce,
					source: $conditionSelect.val()
				  };
				},
				processResults: function processResults(response, params) {

				  if (response.success) {
					return {
					  results: response.data
					};
				  }
		
				  return {};
				}
			  }
			});
		  });
		  $(document).on('click', '.athemes-addons-display-conditions-modal-toggle', function (event) {
			event.preventDefault();
			var $button = $(this);
			var template = wp.template('athemes-addons-display-conditions-template');
			var $control = $button.closest('.athemes-addons-display-conditions-control');
			var $modal = $control.find('.athemes-addons-display-conditions-modal');
		
			if (!$modal.data('initialized')) { 
			
			var $conditionSettings = $control.data('condition-settings');

			  $control.append(template($conditionSettings));
			  var $items = $control.find('.athemes-addons-display-conditions-modal-content-list-item').not('.hidden');
		
			  if ($items.length) {
				$items.each(function () {
				  $(document).trigger('athemes-addons-display-conditions-select2-initalize', this);
				});
			  }
		
			  $modal = $control.find('.athemes-addons-display-conditions-modal');
			  $modal.data('initialized', true);
			  $modal.addClass('open');
			} else {
			  $modal.toggleClass('open');
			}
		  });
		  $(document).on('click', '.athemes-addons-display-conditions-modal', function (event) {
			event.preventDefault();
			var $modal = $(this);
		
			if ($(event.target).is($modal)) {
			  $( '.athemes-addons-display-conditions-modal' ).removeClass('open');
			}
		  });
		  $(document).on('click', '.athemes-addons-display-conditions-modal-add', function (event) {
			event.preventDefault();
			var $button = $(this);
			var $control = $button.closest('.athemes-addons-display-conditions-control');
			var $modal = $control.find('.athemes-addons-display-conditions-modal');
			var $list = $modal.find('.athemes-addons-display-conditions-modal-content-list');
			var $item = $modal.find('.athemes-addons-display-conditions-modal-content-list-item').first().clone();
			var conditionGroup = $button.data('condition-group');
			$item.removeClass('hidden');
			$item.find('.athemes-addons-display-conditions-select2-condition').not('[data-condition-group="' + conditionGroup + '"]').remove();
			$list.append($item);
			$(document).trigger('athemes-addons-display-conditions-select2-initalize', $item);
		  });
		  $(document).on('click', '.athemes-addons-display-conditions-modal-remove', function (event) {
			event.preventDefault();
			var $item = $(this).closest('.athemes-addons-display-conditions-modal-content-list-item');
			$item.remove();
		  });
		  $(document).on('click', '.athemes-addons-display-conditions-modal-save', function (event) {
			event.preventDefault();
			var data = [];
			var $button = $(this);
			var $control = $button.closest('.athemes-addons-display-conditions-control');
			var $modal = $control.find('.athemes-addons-display-conditions-modal');
			var $textarea = $control.find('.athemes-addons-display-conditions-textarea');
			var $items = $modal.find('.athemes-addons-display-conditions-modal-content-list-item').not('.hidden');
			$items.each(function () {
			  var $item = $(this);
			  data.push({
				type: $item.find('select[name="type"]').val(),
				condition: $item.find('select[name="condition"]').val(),
				id: $item.find('select[name="id"]').val()
			  });
			});
			$textarea.val(JSON.stringify(data)).trigger('change');
			$(document).trigger('athemes-addons-display-conditions-update', $textarea);
		});	
		
		
		/**
		 * Theme builder.
		 */
		// Display conditions.
		$(document).on('athemes-addons-display-conditions-update', function (event, item) {
			var $item = $(item);
			var $conditions = $item.val();

			$.post(window.athemes_addons_elementor.ajax_url, {
				action: 'athemes_addons_update_template_conditions',
				nonce: window.athemes_addons_elementor.nonce,
				template_id: $item.parents( '.athemes-addons-tb-element' ).data('template-id'),
				conditions: $conditions
			}, function (response) {
				if (response.success) {
					console.log(response);
				}
			});
		});

		// Delete template.
		$(document).on('click', '.aafe-delete-template', function (e) {
			e.preventDefault();
			var $button = $(this);
			var templateId = $button.parents( '.athemes-addons-tb-element' ).data('template-id');

			$button.children('.dashicons').addClass( 'dashicons-update-alt' ).removeClass( 'dashicons-edit' ).css( 'animation', 'aafe-spin 2s infinite' );

			function onDeleteFailed() {
				$button.children('.dashicons').removeClass( 'dashicons-update-alt' ).css( 'animation', '' );
				showSaveError(window.athemes_addons_elementor.delete_failed);
			}

			$.post(window.athemes_addons_elementor.ajax_url, {
				action: 'athemes_addons_delete_template',
				nonce: window.athemes_addons_elementor.nonce,
				template_id: templateId
			}).done(function (response) {
				if (!response || !response.success) {
					onDeleteFailed();
					return;
				}

				$button.closest('.athemes-addons-tb-element').fadeOut();
			}).fail(onDeleteFailed);
		});

		// Edit template.
		$(document).on('click', '.aafe-edit-template', function (e) {
			e.preventDefault();
			var $button = $(this);
			var templateId = $button.parents( '.athemes-addons-tb-element' ).data('template-id');

			$button.children('.dashicons').toggleClass( 'dashicons-edit dashicons-update-alt' ).css( 'animation', 'aafe-spin 2s infinite' );

			var elementorUrl = window.athemes_addons_elementor.admin_url + 'post.php?post=' + templateId + '&action=elementor';

			var iframe = $('#athemes-addons-elementor-iframe');

			iframe.siblings('.athemes-addons-iframe-loader').show();

			iframe.attr( 'src', elementorUrl );

			iframe.parent().addClass( 'open' );

			iframe.siblings('.athemes-addons-iframe-loader').fadeOut();

			iframe.on( 'load', function() {
				$button.children('.dashicons').addClass( 'dashicons-edit' ).removeClass( 'dashicons-update-alt' ).css( 'animation', 'none' );
			} );

		});

		// Create template.
		$(document).on('click', '.aafe-create-template', function (e) {
			e.preventDefault();
			var $button 		= $(this);
			var templateType 	= $button.data('template-type');
			var templateLabel 	= $button.data('template-label');
			var iframe 			= $('#athemes-addons-elementor-iframe');

			iframe.siblings('.athemes-addons-iframe-loader').show();

			$.post(window.athemes_addons_elementor.ajax_url, {
				action: 'athemes_addons_create_template',
				nonce: window.athemes_addons_elementor.nonce,
				template_type: templateType,
				template_label: templateLabel
			}, function (response) {
				if (response.success) {
					$button.children('.dashicons').toggleClass( 'dashicons-plus-alt2 dashicons-update-alt' ).css( 'animation', 'none' );

					var templateId = response.data.template_id;

					var elementorUrl = window.athemes_addons_elementor.admin_url + 'post.php?post=' + templateId + '&action=elementor';

					iframe.attr( 'src', elementorUrl );

					iframe.parent().addClass( 'open' );

					iframe.siblings('.athemes-addons-iframe-loader').fadeOut();

					iframe.on( 'load', function() {
						$.post(window.athemes_addons_elementor.ajax_url, {
							action: 'athemes_addons_get_templates',
							nonce: window.athemes_addons_elementor.nonce
						}, function (response) {
							if (response.success) {
								$('.athemes-addons-theme-builder-elements[data-type="templates"]').html(response.data);
							}
						});
					});
				}
			});
		});		

		// Close iframe.
		$(document).on('click', '.athemes-addons-close-modal', function (e) {
			e.preventDefault();
			$('#athemes-addons-elementor-iframe').parent().removeClass( 'open' );

			//remove src
			$('#athemes-addons-elementor-iframe').attr( 'src', '' );
		});

		// Header type
		$(document).on('change', '.aafe-header-type-select', function (e) {
			var $select = $(this);
			var headerType = $select.val();
			var templateId = $select.parents( '.athemes-addons-tb-element' ).data('template-id');
			// Last saved value: the server-rendered option until a save succeeds here.
			var savedType = $select.data('saved-type') || $select.find('option[selected]').val() || $select.find('option').first().val();

			function onHeaderTypeFailed() {
				$select.val(savedType);
				showSaveError();
			}

			$select.prop('disabled', true);

			$.post(window.athemes_addons_elementor.ajax_url, {
				action: 'athemes_addons_header_type',
				nonce: window.athemes_addons_elementor.nonce,
				template_id: templateId,
				header_type: headerType
			}).done(function (response) {
				if (!response || !response.success) {
					onHeaderTypeFailed();
					return;
				}

				$select.data('saved-type', headerType);
				setTimeout(() => {
					$select.prev('.saved-label').css('opacity', '1');
					setTimeout(() => {
						$select.prev('.saved-label').css('opacity', '0');
					}, 2000);
				}, 500);
			}).fail(onHeaderTypeFailed).always(function () {
				$select.prop('disabled', false);
			});
		} );

		// Upgrade plugin from theme builder.
		$(document).on('click', '.aafe-upgrade', function (e) {
			e.preventDefault();

			window.open(window.athemes_addons_elementor.upgrade_url, '_blank');
		} );
	});
})(jQuery, window, document);