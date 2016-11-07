( function( $ ) {
	$( document ).ready( function() {
		/* Load google package for chart visualization */
		google.charts.load( 'current', { packages: ['corechart'] } );
		google.charts.setOnLoadCallback(contentProcessing);
	} );

	/**
	 * Content Processing. This functions will be recalled every time user selects new tab.
	 */
	function contentProcessing() {

		/*
		 * functions for "Tracking Code & Reset" tab
		 */
		/* uncheck "Add tracking Code To Your Blog" checkbox if tracking code is empty */
		if ( ! $( 'input[name="gglnltcs_tracking_id"]' ).val() ) {
			$( '#gglnltcs-add-tracking-code-input' ).attr( "checked", false );
		};
		/* check/uncheck "Add tracking Code To Your Blog" checkbox depending whether tracking code field is empty */
		$( 'input[name="gglnltcs_tracking_id"]' ).change( function() {
			$( '#gglnltcs-add-tracking-code-input' ).attr( "checked", !! $( this ).val() );
		} );
		$( '#gglnltcs-tracking-id-form input[name="gglnltcs_tracking_id"], input[name="gglnltcs_tracking_id"], #gglnltcs-authentication-code-input' ).on( 'keypress', function() {
			$( this ).removeClass( 'gglnltcs-validation-failed' );
		} );
		/* Google Authentication form preventing submit */
		$( '#gglnltcs-authentication-form' ).on( 'submit', function( event ) {
			event = event || window.event;
			var input = $( '#gglnltcs-authentication-code-input' );
			if ( ! input.val() ) {
				event.preventDefault();
				input.addClass( 'gglnltcs-validation-failed' );
			}
		} );

		/*
		 * Add Datepicker
		 */
		var dateInput = $( '#gglnltcs-start-date, #gglnltcs-end-date' );
		dateInput.on( 'change', function() {
			var dateValue = $( this ).val(),
				startDateInput = $( '#gglnltcs-start-date' ),
				endDateInput   = $( '#gglnltcs-end-date' ),
				startDateValue = startDateInput.val(),
				endDateValue   = endDateInput.val(),
				rightFormat    = /^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/;
			/* if the entered value has wrong format */
			if ( dateValue && ! dateValue.match( rightFormat ) ) {
				/* highlight the text field */
				$( this ).addClass( 'gglnltcs-validation-failed' );
				/* highlight the tooltip */
				$( '#gglnltcs-date-tooltip, .gglnltcs-date-format' ).addClass( 'gglnltcs-error-tooltip' );
			/* check both dates */
			} else if ( startDateValue && endDateValue ) {
				var start  = new Date( startDateValue ).getTime(),
					end    = new Date( endDateValue   ).getTime(),
					dayGap = Math.abs( end - start ) / 86400000;

				/* the time gap between two dates must not be more than 999 days */
				if ( dayGap > 999 ) {
					/* change the "to" field if the "from" field has been changed just right now */
					if ( $( this ).attr( 'id' ) == 'gglnltcs-start-date' ) {
						var endTime = new Date( start + 86313600000 ); /* 86313600000 - is number of milliseconds in 999 days */
						endDateInput.val( endTime.getFullYear() + '-' + ( '0' + ( endTime.getMonth() + 1 ) ).slice( -2 ) + '-' + ( '0' + endTime.getDate() ).slice( -2 ) );
					/* change the "from" field if the "to" field has been changed just right now */
					} else if ( $( this ).attr( 'id' ) == 'gglnltcs-end-date' ) {
						var startTime = new Date( end - 86313600000 );
						startDateInput.val( startTime.getFullYear() + '-' + ( '0' + ( startTime.getMonth() + 1 ) ).slice( -2 ) + '-' + ( '0' + startTime.getDate() ).slice( -2 ) );
					}
					/* highlight the tooltip */
					$( '#gglnltcs-date-tooltip, .gglnltcs-max-gap' ).addClass( 'gglnltcs-error-tooltip' );
				} else if ( start > end ) {
					/* change the values by places */
					startDateInput.val( endDateValue );
					endDateInput.val( startDateValue );
				} else {
					if ( dateValue.match( rightFormat ) ) {
						$( '.gglnltcs-error-tooltip' ).removeClass( 'gglnltcs-error-tooltip' );
						$( this ).removeClass( 'gglnltcs-validation-failed' );
					}
				}
			} else {
				$( this ).removeClass( 'gglnltcs-validation-failed' );
				$( '.gglnltcs-error-tooltip' ).removeClass( 'gglnltcs-error-tooltip' );
			}
		} ).datepicker( {
			dateFormat :     'yy-mm-dd',
			changeMonth:     true,
			changeYear:      true,
			showButtonPanel: true,
			minDate:         new Date( '2005-01-01' )
		} );

		/*
		 * Main form preventing submit
		 */
		$( '#gglnltcs-get-statistics-button' ).on( 'click', function( event ) {

			event = event || window.event;
			event.preventDefault();

			var startDateInput = $( '#gglnltcs-start-date' ),
				endDateInput   = $( '#gglnltcs-end-date' ),
				startDateValue = startDateInput.val(),
				endDateValue   = endDateInput.val();

			if ( ! startDateValue || ! endDateValue ) {
				var now = new Date;
				startDateInput.val( ( now.getFullYear() - 1 ) + '-' + ( '0' + ( now.getMonth() + 1 ) ).slice( -2 ) + '-' + ( '0' + now.getDate() ).slice( -2 ) ); /* year ago */
				endDateInput.val( now.getFullYear() + '-' + ( '0' + ( now.getMonth() + 1 ) ).slice( -2 ) + '-' + ( '0' + now.getDate() ).slice( -2 ) );
			}

			if ( ! dateInput.hasClass( 'gglnltcs-validation-failed' ) ) {
				$( '.gglnltcs-error-tooltip' ).removeClass( 'gglnltcs-error-tooltip' );
				displayStatistics();
			}
		} );

		setChartCurves();
		/*
		 * Display statistics
		 */
		displayStatistics( false );

		$( '.gglnltcs-select' ).on( 'change', function() {
			if ( $( this ).attr( 'id' ) == 'gglnltcs-accounts' )
				getWebproperties();

			displayStatistics();
		} );
	}


	/**
	 * Display statistical data
	 * @since   1.6.7
	 * @param   boolean  make_ajax
	 * @rerturn void
	 */
	function displayStatistics( make_ajax ) {
		make_ajax = 'undefined' ===  typeof make_ajax ? true : make_ajax;
		var viewMode   = $( 'input[name="gglnltcs_view_mode"]:checked' ).val(),
			metrics    = $( '.gglnltcs-metrics' );
		if ( make_ajax )
			$( '#gglnltcs-results-wrapper' ).children().fadeTo( 200, .3 );
		if ( ! $( '.gglnltcs-metrics input:checkbox:checked' ).length ) {
			$( '.gglnltcs-metrics input:checkbox' ).addClass( 'gglnltcs-validation-failed' );
			displayError( gglnltcsLocalize.metricsValidation );
			return false;
		}

		switch ( viewMode ) {
			case 'table':
				if ( make_ajax )
					ajaxBuildTableChart( pagenow );
				else
					resultsTableFunctions();
				break;
			case 'chart':
			default:
				ajaxBuildLineChart( pagenow );
				break;

		}
	}

	/**
	 * Here We Register Which Curves To Display On The Line Chart.
	 * @since   1.6.7
	 * @param   void
	 * @rerturn void
	 */
	function setChartCurves() {
		var metrics    = $( '#gglnltcs-metrics' ),
			checkboxes = metrics.find( 'input:checkbox' ),
			checked    = metrics.find( 'input:checkbox:checked' ),
			id         = metrics.attr( 'id' ),
			chartCurves = {
				'visitors'			: false,
				'newVisits'			: false,
				'visits'			: false,
				'visitBounceRate'	: false,
				'avgTimeOnSite'	 	: false,
				'pageviews'		 	: false,
				'pageviewsPerVisit' : false
			}, chartSelectedMetric;
		if ( metrics.length ) {
			metrics.data( 'chartCurves', chartCurves );
			/* On document ready */
			checked.each( function() {
				chartSelectedMetric = $( this ).val(),
				chartSelectedMetric = chartSelectedMetric.substring(3),
				chartCurves[ chartSelectedMetric ] = true;
			} );
			metrics.data( 'chartCurves', chartCurves );
			/* On change */
			checkboxes.on( 'change', function() {
				chartSelectedMetric = $( this ).val(),
				chartSelectedMetric = chartSelectedMetric.substring(3),
				chartCurves[ chartSelectedMetric ] = chartCurves[ chartSelectedMetric ] ? false : true;
				checkboxes.removeClass( 'gglnltcs-validation-failed' );
				metrics.data( 'chartCurves', chartCurves );
			} );
		}
	}

	/* Set Results Table Height. */
	function setResultsTableHeight() {
		if ( $( 'input[name="gglnltcs_view_mode"]:checked' ).val() != 'table' )
			return false;
		$( 'table.gglnltcs-results:visible tr' ).each( function() {
			$( this ).height( $( this ).find( 'th' ).outerHeight() );
		} );
	}

	/* All neccessary processing for the results table. */
	function resultsTableFunctions() {
		/* Results table hover highlight cells */
		$( '.gglnltcs-results td' ).on( 'mouseover', function() {
			var cellIndex = $( this ).index();
			$( '.gglnltcs-results tr' ).each( function() {
				$( this ).find( 'td' ).eq( cellIndex - 2 ).addClass( 'gglnltcs-hovered-cell' );
			} );
			$( this ).addClass( 'gglnltcs-this-hovered-cell' );
		} ).on( 'mouseleave', function() {
			var cellIndex = $( this ).index();
			$( '.gglnltcs-results tr' ).each( function() {
				$( this ).find( 'td' ).eq( cellIndex - 2 ).removeClass( 'gglnltcs-hovered-cell' );
			} );
			$( this ).removeClass( 'gglnltcs-this-hovered-cell' );
		} );
		/* Height and Width of result tables. */
		if ( $( "#gglnltcs-results-wrapper" ).length ) {
			setResultsTableHeight();
			var width = $( window ).width();
			$( window ).on( 'resize', function() {
				if ( $( this ).width() != width ) {
					width = $( this ).width();
					setResultsTableHeight();
				}
			} );
		}
		/* Change year month day in the results table */
		$( '#gglnltcs-group-by-Y-M-D input' ).on( 'click', function() {
			$( '#gglnltcs-group-by-Y-M-D input' ).removeClass( 'gglnltcs-selected' );
			$( this ).addClass( 'gglnltcs-selected' );
			if ( ! $( '.gglnltcs-results .gglnltcs-bad-results' ).length ) {
				var index = $( this ).index();
				var tablesTotal = $( '.gglnltcs-results-table-wrap' );
				tablesTotal.hide();
				$( '.gglnltcs-results-table-wrap' ).eq( tablesTotal.length - index ).show();
				setResultsTableHeight();
			}
		} );
	}

	/**
	 * Display an error message
	 * @since  1.6.7
	 * @param  string   errror   the text of message
	 * @return void
	 */
	function displayError( error ) {
		var parentWidth = $( '#gglnltcs-results-wrapper' ).width();
		$( '.gglnltcs-error-message' ).remove();
		$( '<div>', { 'class': 'gglnltcs-error-message', html: error } )
			.hide()
			.appendTo( '#gglnltcs-results-wrapper' )
			.css( { 'left': ( ( parentWidth - $( '.gglnltcs-error-message' ).width() ) / 2 ) + 'px' } )
			.fadeIn( 500 );
	}

	/* Ajax Function To Build The Line Chart When User Clicks "Get Statisics" Button */
	function ajaxBuildLineChart( url ) {
		var settings      = $( '#gglnltcs-main-form' ).serialize(),
			toDisable     = $( '.gglnltcs_to_disable' ).attr( 'disabled', true ),
			loadingCircle = $( '<div>', { 'class': 'gglnltcs-loading-icon' } ).hide().insertAfter( '#gglnltcs-get-statistics-button' ).fadeIn( 500 ),
			chartCanvas   = $( '#gglnltcs-results-wrapper' ).children(),
			data          = {
				action:         'gglnltcs_action',
				settings:       settings,
				url:            url,
				tab:            'line_chart',
				page:           'bws-google-analytics.php',
				gglnltcs_nonce: gglnltcsLocalize.gglnltcs_ajax_nonce
			};
		$.post( ajaxurl, data, function( data ) {
			try {
				/* fetch only JSON string from the result  */
				var regExp = /<!-- start bws-ga-results -->([^]+)<!-- end bws-ga-results -->/,
					matches = regExp.exec( data );
				if ( matches ) {
					data = matches[1];
				} else {
					displayError( gglnltcsLocalize.ajaxApiError + '<br/>' + data );
					return false;
				}

				data = $.parseJSON( data );

				var chartCurves = $( '#gglnltcs-metrics' ).data( 'chartCurves' ),
					chartRows   = [];
					chartDate   = data[0];
				if ( chartCurves.visitors 		   ) { var visitors   = data[1]; }
				if ( chartCurves.newVisits		   ) { var newVisits  = data[2]; }
				if ( chartCurves.visits 		   ) { var visits     = data[3]; }
				if ( chartCurves.visitBounceRate   ) { var bounceRate = data[4]; }
				if ( chartCurves.avgTimeOnSite 	   ) { var avgTime 	  = data[5]; }
				if ( chartCurves.pageviews 		   ) { var pageviews  = data[6]; }
				if ( chartCurves.pageviewsPerVisit ) { var perVisit   = data[7]; }

				var ajaxChart = new google.visualization.DataTable();
				ajaxChart.addColumn( 'date', 'Date' );
				if ( chartCurves.visitors   	   ) { ajaxChart.addColumn( 'number', gglnltcsLocalize.chartVisitors   ); }
				if ( chartCurves.newVisits  	   ) { ajaxChart.addColumn( 'number', gglnltcsLocalize.chartNewVisits  ); }
				if ( chartCurves.visits     	   ) { ajaxChart.addColumn( 'number', gglnltcsLocalize.chartVisits     ); }
				if ( chartCurves.visitBounceRate   ) { ajaxChart.addColumn( 'number', gglnltcsLocalize.chartBounceRate ); }
				if ( chartCurves.avgTimeOnSite     ) { ajaxChart.addColumn( 'number', gglnltcsLocalize.chartAvgTime    ); }
				if ( chartCurves.pageviews  	   ) { ajaxChart.addColumn( 'number', gglnltcsLocalize.chartPageviews  ); }
				if ( chartCurves.pageviewsPerVisit ) { ajaxChart.addColumn( 'number', gglnltcsLocalize.chartPerVisit   ); }

				for ( var i = 0; i < chartDate.length; i++ ) {
					chartRows = [];
					chartRows.push( new Date( chartDate[i][0], chartDate[i][1] - 1, chartDate[i][2] ) );
					if ( chartCurves.visitors   	   ) { chartRows.push( parseInt( visitors[i], 10   ) ) }
					if ( chartCurves.newVisits  	   ) { chartRows.push( parseInt( newVisits[i], 10  ) ) }
					if ( chartCurves.visits     	   ) { chartRows.push( parseInt( visits[i], 10     ) ) }
					if ( chartCurves.visitBounceRate   ) { chartRows.push( parseInt( bounceRate[i], 10 ) ) }
					if ( chartCurves.avgTimeOnSite     ) { chartRows.push( parseInt( avgTime[i], 10    ) ) }
					if ( chartCurves.pageviews  	   ) { chartRows.push( parseInt( pageviews[i], 10  ) ) }
					if ( chartCurves.pageviewsPerVisit ) { chartRows.push( parseInt( perVisit[i], 10   ) ) }
					ajaxChart.addRows( [ chartRows ] );
				}
				chartCanvas.parent().html( '<div id="gglnltcs-chart"></div>' );
				function drawChart( tableData ) {
					var options = {
						aggregationTarget: 'series',
						chartArea: {
							backgroundColor: {
								fill: 'white',
								opacity: 100,
								stroke: '#666',
								strokeWidth: 0
							},
						},
						explorer: {
							axis: 'horizontal',
							maxZoomOut: 1,
							maxZoomIn: 10,
							keepInBounds: true
						},
						focusTarget: 'category',
						hAxis: {
							viewWindowMode: 'explicit'
						},
						height: 300,
						interpolateNulls: false,
						legend: {
							maxLines: 8,
							position: 'top'
						},
						lineWidth: 2,
						pointSize: 2,
						selectionMode : 'multiple',
						vAxis: {
							viewWindowMode: 'pretty'
						}
					}
					var chart = new google.visualization.LineChart( document.getElementById('gglnltcs-chart') );
					chart.draw( tableData, options );
				}
				drawChart( ajaxChart );
				var width = $( window ).width();
				$( window ).on( 'resize', function() {
					if ( $( this ).width() != width ) {
						width = $( this ).width();
						drawChart( ajaxChart );
					}
				} );
				$( '#gglnltcs-results-wrapper' ).css('height', 'auto');
			} catch ( errorInAjax ) {
				displayError( gglnltcsLocalize.ajaxApiError + '<br/>' + errorInAjax );
			}
			$( '#gglnltcs-results-wrapper .gglnltcs-error-message' ).remove();
			chartCanvas.fadeTo( 500, 1 );
			loadingCircle.remove();
			toDisable.attr( 'disabled', false );
		} );
	}

	/* Ajax Function To Build The Table With Results When User Clicks "Get Statisics" Button */
	function ajaxBuildTableChart( url ) {
		var settings      = $( '#gglnltcs-main-form' ).serialize(),
			toDisable     = $( '.gglnltcs_to_disable' ).attr( 'disabled', true ),
			loadingCircle = $( '<div>', { 'class': 'gglnltcs-loading-icon' } ).hide().insertAfter( '#gglnltcs-get-statistics-button' ).fadeIn( 500 ),
			tableWrapper  = $( '#gglnltcs-results-wrapper' ),
			data = {
				action:         'gglnltcs_action',
				settings:       settings,
				url:            url,
				tab:            'table_chart',
				gglnltcs_nonce: gglnltcsLocalize.gglnltcs_ajax_nonce
			};

		$.post( ajaxurl, data, function( data ) {
			tableWrapper.html( data );
			resultsTableFunctions();
			tableWrapper.fadeTo( 500, 1 );
			loadingCircle.remove();
			toDisable.attr( 'disabled', false );
		} );
	}
} )( jQuery );

/**
 * Get Webproperties For Selected Account
 */
function getWebproperties() {
	var account    = document.getElementById( 'gglnltcs-accounts' ).value,
		select     = document.getElementById( 'gglnltcs-webproperties' ),
		properties = profileAccounts[ account ]['webproperties'],
		property, profile, option;
	select.innerHTML = '';
	for ( property in properties ) {
		for ( profile in properties[ property ]['profiles'] ) {
			option = document.createElement( 'option' );
			option.innerHTML = properties[ property ]['name'] + ' ( ' + properties[ property ]['profiles'][profile] + ' )';
			option.value     = 'ga:' + profile;
			select.appendChild( option );
		}
	}
}