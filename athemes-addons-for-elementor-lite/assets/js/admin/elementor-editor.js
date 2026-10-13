(function ($, window, document, undefined) {

		/**
		 * Post Filter
		 */
		var filterOptions = elementor.modules.controls.Select2.extend({

			isUpdated: false,
	
			onReady: function () {
				var self = this,
					type = self.options.container.settings.attributes.post_type_filter;
	
				if ('post' !== type) {
					var options = (0 === this.model.get('options').length);
	
					if (options) {
						self.fetchData(type);
					}
				}
	
				elementor.channels.editor.on('change', function (view) {
					var changed = view.container.settings.changed;
	
					if (undefined !== changed.post_type_filter && 'post' !== changed.post_type_filter && !self.isUpdated) {
						self.isUpdated = true;
						self.fetchData(changed.post_type_filter);
					}
				});
			},
	
			fetchData: function (type) {
				var self = this;
				$.ajax({
					url: AAFESettings.ajaxurl,
					dataType: 'json',
					type: 'POST',
					data: {
						nonce: AAFESettings.nonce,
						action: 'athemes_addons_update_posts_filter',
						post_type: type
					},
					success: function (res) {
						self.updateFilterOptions(JSON.parse(res.data));
						self.isUpdated = false;
	
						self.render();
					},
					error: function (err) {
						console.log(err);
					},
				});
			},
	
			updateFilterOptions: function (options) {
				this.model.set("options", options);
			},
	
			onBeforeDestroy: function () {
				if (this.ui.select.data('select2')) {
					// this.ui.select.select2('destroy');
				}
	
				this.$el.remove();
			}
		});
	
		elementor.addControlView("aafe-post-filter", filterOptions);
		
})(jQuery, window, document);

/**
 * Query Control
 */
(function ($) {
    // Titles and term names can arrive entity-encoded; decode without inserting markup.
    var decodeEntities = function (value) {
        return new DOMParser().parseFromString(String(value), 'text/html').documentElement.textContent;
    };

    $(document).on('aafe_query_control_init', function (event, obj) {
        var ID = '#elementor-control-default-' + obj.data._cid;
        setTimeout(function () {
            var IDSelect2 = $(ID).select2({
                minimumInputLength: 3,
                ajax: {
                    type: 'POST',
                    url: AAFESettings.ajaxurl,
                    dataType: 'json',
                    data: function ( params ) {
                        return {
                            action: 'aafe_posts_filter_autocomplete',
                            post_type: obj.data.source_type,
                            source_name: obj.data.source_name,
                            term: params.term,
                            nonce: AAFESettings.nonce,
                        }
                    },
                },
                initSelection: function (element, callback) {
                    if (!obj.multiple) {
                        callback({id: '', text: AAFESettings.search_text});
                    }else{
						callback({id: '', text: ''});
					}
					var ids = [];
                    if(!Array.isArray(obj.currentID) && obj.currentID != ''){
						 ids = [obj.currentID];
					}else if(Array.isArray(obj.currentID)){
						 ids = obj.currentID.filter(function (el) {
							return el != null;
						})
					}

                    if (ids.length > 0) {
                        var label = $("label[for='elementor-control-default-" + obj.data._cid + "']");
                        label.after('<span class="elementor-control-spinner">&nbsp;<i class="eicon-spinner eicon-animation-spin"></i>&nbsp;</span>');
                        $.ajax({
                            method: "POST",
                            url: AAFESettings.ajaxurl,
                            data: {
                                action: 'aafe_get_posts_value_titles',
                                post_type: obj.data.source_type, 
                                source_name: obj.data.source_name, 
                                id: ids,
                                nonce: AAFESettings.nonce,
                            }
                        }).done(function (response) {
                            if (response.success && typeof response.data.results != 'undefined') {
                                ids.forEach(function (item, index){
                                    if(typeof response.data.results[item] != 'undefined'){
                                        element.append(new Option(decodeEntities(response.data.results[item]), item, true, true));
                                    }
                                })
                            }
							label.siblings('.elementor-control-spinner').remove();
                        });
                    }
                }
            });

            setTimeout(function (){
                IDSelect2.next().children().children().children().sortable({
                    containment: 'parent',
                    stop: function(event, ui) {
                        ui.item.parent().children('[title]').each(function() {
                            var title = $(this).attr('title');
                            var original = $('option:contains(' + title + ')', IDSelect2).first();
                            original.detach();
                            IDSelect2.append(original)
                        });
                        IDSelect2.change();
                    }
                });

                $(ID).on("select2:select", function(evt) {
                    var element = evt.params.data.element;
                    var $element = $(element);

                    $element.detach();
                    $(this).append($element);
                    $(this).trigger("change");
                });
            },200);
        }, 100);

    });
}(jQuery));

/**
 * Template Link Control JavaScript
 * 
 */
(function($) {
    'use strict';

    var TemplateLink = elementor.modules.controls.BaseData.extend({
        
        ui: function() {
            var ui = elementor.modules.controls.BaseData.prototype.ui.apply(this, arguments);
            ui.templateContainer = '.aafe-template-link-container';
            ui.templateValue = '.aafe-template-value';
            return ui;
        },
        
        onReady: function() {
            this.initControl();
        },
        
        /**
         * Initialize the control
         */
        initControl: function() {
            var self = this;
            var connectedOption = this.getConnectedOption();
            
            if (!connectedOption) {
                return;
            }
            
            // Update value when initialized
            this.updateTemplateValue();
            
            // Listen for changes in other controls
            this.listenTo(this.container.settings, 'change:' + connectedOption, this.updateTemplateValue);
        },
        
        /**
         * Get the name of the connected option
         */
        getConnectedOption: function() {
            return this.model.get('connected_option');
        },
        
        /**
         * Update the template value display
         */
        updateTemplateValue: function() {
            var connectedOption = this.getConnectedOption();
            if (!connectedOption) {
                return;
            }
            
            var templateId = parseInt(this.container.settings.get(connectedOption), 10);
            
            // Clear content if no template is selected
            if (!templateId) {
                this.ui.templateValue.empty();
                return;
            }
            
            // Create the edit link
            var $link = $('<a>')
                .attr({ href: this.getTemplateEditUrl(templateId), target: '_blank', style: 'margin-top: -10px;border:0!important;' })
                .addClass('elementor-button elementor-button-default')
                .html(AAFESettings.edit_template);

            this.ui.templateValue.empty().append($link);
        },
        
        /**
         * Get the edit URL for a template
         */
        getTemplateEditUrl: function(templateId) {
            if (typeof AAFESettings.admin_url !== 'undefined') {
                return AAFESettings.admin_url + 
                       'post.php?post=' + templateId + 
                       '&action=elementor';
            }
            
            // Fallback
            return window.location.origin + 
                   '/wp-admin/post.php?post=' + templateId + 
                   '&action=elementor';
        },
    });

    // Register the control
    elementor.addControlView('aafe-template-link', TemplateLink);
    
})(jQuery); 