(function ($) {

	var aThemesAddonsEventsCalendar = function($scope, $) {

		const wrapper = $scope.find('.athemes-addons-events-calendar').eq(0);
		const calendarEl = wrapper.find( '#aafe-events-calendar' ).get(0);

		const initialView 	= $( calendarEl ).data( 'initial-view' ),
			apiKey 			= $( calendarEl ).data( 'api-key' ),
			calendarId 		= $( calendarEl ).data( 'calendar-id' ),
			time_format 	= $( calendarEl ).data('format-24') == 'yes' ? true : false,
			locale 			= $( calendarEl ).data('locale'),
			firstDay 		= $( calendarEl ).data('first-day');

		const calendar = new FullCalendar.Calendar( calendarEl, {
			initialView: initialView,
			editable: false,
			locale: locale,
			firstDay: firstDay,
			headerToolbar: {
				left: 'prev,next today',
				center: 'title',
				right: 'dayGridMonth,timeGridWeek,timeGridDay'
			},		
			eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: !time_format
            },
			googleCalendarApiKey: apiKey,
			eventSources: [
				{
					googleCalendarId: calendarId,
				}
			],
			eventMouseEnter: function( info ) {

				if ( navigator.userAgent.indexOf('Firefox') > -1 && parseInt( navigator.userAgent.match(/Firefox\/([0-9]+)\./)[1] ) < 125 ) {
					return; // Firefox 125 and below doesn't support popovers
				}

				var eventEl = $( info.el );

				var id = 'popover-' + Math.floor( Math.random() * 10000 );

				var buttonEl = $( '<button>' ).addClass( eventEl.attr('class') ).html( eventEl.html() ).attr('popovertarget', id );
		
				eventEl.replaceWith( buttonEl );

				var popoverContent = createPopoverContent( info, id );

				if ( !buttonEl.next().is( '#' + id ) ) {
					buttonEl.after( popoverContent );
				}
				
			}		
		} );

		calendar.render()
	}

	function createPopoverContent( info, id ) {
		var event       = info.event;
		var location    = event.extendedProps.location;
		var description = event.extendedProps.description;

		// Event fields come from the calendar feed, so they are set as text only.
		var $header = $( '<div>' ).addClass( 'modal-header' ).append(
			$( '<h3>' ).text( event.title ),
			$( '<button>' ).addClass( 'close-modal' ).attr( { popovertarget: id, popovertargetaction: 'hide' } ).text( 'X' )
		);

		var $meta = $( '<div>' ).addClass( 'modal-meta' ).append(
			$( '<span>' ).addClass( 'event-time' ).text( '📅 ' + event.start.toLocaleString() + ' - ' + event.end.toLocaleString() )
		);

		if ( location ) {
			$meta.append( $( '<span>' ).addClass( 'event-location' ).text( '🗺️ ' + location ) );
		}

		var $body = $( '<div>' ).addClass( 'modal-body' ).append( $meta );

		if ( description ) {
			// Google descriptions can hold rich text: keep the words and line breaks, drop the markup.
			var lines        = new DOMParser().parseFromString( description.replace( /<br\s*\/?>|<\/(p|div|li|h[1-6])>/gi, '\n' ), 'text/html' ).body.textContent.trim().split( '\n' );
			var $description = $( '<p>' ).addClass( 'event-description' );

			lines.forEach( function ( line, index ) {
				if ( index ) {
					$description.append( $( '<br>' ) );
				}
				$description.append( document.createTextNode( line ) );
			} );

			$body.append( $description );
		}

		var $footer = $( '<div>' ).addClass( 'modal-footer' );

		if ( /^https?:\/\//i.test( event.url || '' ) ) {
			$footer.append( $( '<a>' ).addClass( 'view-event' ).attr( 'href', event.url ).html( AAFESettings.view_event + ' &xrarr;' ) );
		}

		return $( '<div>' ).addClass( 'event-modal' ).attr( { id: id, popover: '' } ).append( $header, $body, $footer );
	}

	$(window).on('elementor/frontend/init', function () {
		elementorFrontend.hooks.addAction('frontend/element_ready/athemes-addons-events-calendar.default', aThemesAddonsEventsCalendar );
	});

})(jQuery);