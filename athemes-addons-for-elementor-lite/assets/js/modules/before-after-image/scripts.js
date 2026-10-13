(function ($) {

	// twentytwenty builds its labels by string concatenation, so escape them first.
	var escapeAttr = function (value) {
		return String(null == value ? '' : value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#39;');
	};

	// Switchers render "1" when on, "" when off and "no" when never toggled.
	var isOn = function (value) {
		return true === value || 1 === value || '1' === value || 'yes' === value;
	};

	var aThemesAddonsBeforeAfterImage = function($scope, $) {
		var $beforeAfterImage = $scope.find('.athemes-addons-before-after-image');
		var offset            = parseFloat($beforeAfterImage.data('offset'));

		$beforeAfterImage.twentytwenty({
			default_offset_pct: isNaN(offset) ? 0.5 : offset,
			orientation: 'vertical' === $beforeAfterImage.data('orientation') ? 'vertical' : 'horizontal',
			move_slider_on_hover: isOn($beforeAfterImage.data('move-on-hover')),
			click_to_move: $beforeAfterImage.data('click-to-move'),
			no_overlay: $beforeAfterImage.data('overlay'),
			before_label: escapeAttr($beforeAfterImage.data('before-label')),
			after_label: escapeAttr($beforeAfterImage.data('after-label')),
		});
	}

	$(window).on('elementor/frontend/init', function () {
		elementorFrontend.hooks.addAction('frontend/element_ready/athemes-addons-before-after-image.default', aThemesAddonsBeforeAfterImage );
	});

})(jQuery);
