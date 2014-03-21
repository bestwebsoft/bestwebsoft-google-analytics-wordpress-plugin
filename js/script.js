/* Load Visualization */
google.load( 'visualization', '1', { 'packages': ['annotatedtimeline'] });
/************************************************************************/
/* Get Vebproperties For Selected Account */
function getWebproperties() {
	var i = 0;
	webPropIDs = [];
	document.getElementById( 'gglnltcs-webproperties' ).innerHTML = '';
	var selectedAccount = accountsId[ document.getElementById( 'gglnltcs-accounts' ).selectedIndex ];
	var selectedAccountWebproperties = profileAccounts[ selectedAccount ]['webproperties'];     
	for ( webproperty in selectedAccountWebproperties ) {
		var newOption = document.createElement( 'option' );
		newOption.innerHTML = selectedAccountWebproperties[ webproperty ];
		webPropIDs.push( webproperty );
		document.getElementById( 'gglnltcs-webproperties' ).appendChild( newOption );
		if ( window.selectedWebroperty !== undefined && window.selectedWebroperty == selectedAccountWebproperties[ webproperty ] ) {
			document.getElementById( 'gglnltcs-webproperties' ).options[ i ].selected = true;
			/* Makes first option selected in the weproperties select list. 
			 * This is necessary when you select another account. */
			selectedWebroperty = false;
		}       
		i++;
	}
}
/************************************************************************/
/* Sets View ID To The Hidden Field */
function setViewID() {
	document.getElementById( 'gglnltcs-view-id' ).value = 'ga:' + profileWebproperties[ webPropIDs[ document.getElementById( 'gglnltcs-webproperties' ).selectedIndex ] ];
}
/************************************************************************/
/* jQuery Wrapper */
( function( $ ) {
	$( document ).ready( function() {
		/* Processing Clicks On Navigation Tabs */
		$( '#gglnltcs-line-nav-tab, #gglnltcs-table-nav-tab, #gglnltcs-tracking-code-nav-tab' ).on( 'click', function( event ) {
			event = event || window.event;
			event.preventDefault();
			/* Change active tab. */
			$( '.nav-tab-wrapper .nav-tab-active' ).removeClass( 'nav-tab-active' );
			$( this ).addClass( 'nav-tab-active' );
			/* Clear page content and print new content. */
			$( '#gglnltcs-main-content' ).fadeTo( 500, .4 ); /* Blur previous content. */
			$( '#gglnltcs-main-content' ).find( ':input' ).attr( 'disabled', true ); /* Block form elemnts to prevent double content. */
			var activeTab = $( this ).attr( 'id' ); /* Determine which tab did user click. */
			if( activeTab == 'gglnltcs-line-nav-tab' ) {
				ajaxTabContent( 'line_chart' ); /* Print content for Line Chart Tab */
			} else if ( activeTab == 'gglnltcs-table-nav-tab' ) {
				ajaxTabContent( 'table_chart' ); /* Print content for Table Chart Tab. */
			} else if ( activeTab == 'gglnltcs-tracking-code-nav-tab' ) {
				ajaxTabContent( 'tracking_code' ); /* Print content for Tracking Code $ Reset Tab. */
			}
		});
		contentProcessing(); /* Content Processing */
 	});
/************************************************************************/
	/* Content Processing. This functions will be recalled every time user selects new tab. */
	function contentProcessing() {
		/* Accounts on change */
		$( '#gglnltcs-accounts' ).on( 'change', function() {
			getWebproperties();
			setViewID();
		});
		/* Webproperties on change */
		$( '#gglnltcs-webproperties' ).on( 'change', function() {
			setViewID();
		});
		/* Add Datepicker */
		var dateInput = $( '#gglnltcs-start-date, #gglnltcs-end-date' );
		dateInput.datepicker( {
			dateFormat : 'yy-mm-dd',
			changeMonth: true,
			changeYear: true,
			showButtonPanel: true
		});
		/* Date Validation */
		var validationError = 0;
		dateInput.on( 'change', function() {
			dateValue = $( this ).val();
			if ( ! dateValue.match( /^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/ ) ) {
				$( this ).addClass( 'gglnltcs-validation-failed' );
				$( this ).parent().addClass( 'gglnltcs-validation-failed' );
				$( this ).parent().parent().addClass( 'gglnltcs-validation-failed' );
				if ( $( this ).next( '.gglnltcs-error-message' ).length == 0  && validationError != 1 ) {
					$( this ).after( ' <span class="gglnltcs-error-message">' + gglnltcsLocalize.matchPattern + '</span>' );
				}
			} else {
				$( this ).removeClass( 'gglnltcs-validation-failed' );
				$( this ).parent().removeClass( 'gglnltcs-validation-failed' );
				$( this ).parent().parent().removeClass( 'gglnltcs-validation-failed' );
				$( this ).next( '.gglnltcs-error-message' ).remove();
			}
		});
		/* Date Note */
		dateInput.on( 'focus', function() {
			$( '.gglnltcs-date span.gglnltcs-note:not(.gglnltcs-err)' ).text( gglnltcsLocalize.matchPattern );
		});
		dateInput.on( 'blur', function() {
			dateInput.removeClass( 'gglnltcs-invalid-date-range' );
			$( '.gglnltcs-date span.gglnltcs-note' ).removeClass( 'gglnltcs-err' );
		});
		/* Metrics Validation */
		$( '#gglnltcs-metrics input:checkbox' ).on( 'change', function() {
			if ( $( '#gglnltcs-metrics input:checkbox:checked' ).length == 0 ) {
				$( '#gglnltcs-metrics input:checkbox' ).addClass( 'gglnltcs-validation-failed' );
				$( '#gglnltcs-metrics .gglnltcs-table-name' ).after( '<span class="gglnltcs-error-message">' +  gglnltcsLocalize.metricsValidation + '</span>' );
			} else {
				$( '#gglnltcs-metrics input:checkbox' ).removeClass( 'gglnltcs-validation-failed' );
				$( '#gglnltcs-metrics .gglnltcs-table-name' ).next( '.error-message' ).remove();
			}
		});
		/* Main form preventing submit */
		$( '#gglnltcs-main-form' ).on( 'submit', function( event ) {
			event = event || window.event;

			/* Submit on the Table Tab */
			if ( $( '#gglnltcs-get-statistics-button' ).length ) {
				var startDateInput = $( '#gglnltcs-start-date' );
				var endDateInput   = $( '#gglnltcs-end-date'   );
				var startDateValue = startDateInput.val();     
				var endDateValue   = endDateInput.val();  
				var runAjax 	   = true;
				  /* Start date is missing */
				if ( ! startDateValue ) {
					startDateInput.addClass( 'gglnltcs-validation-failed' );
					runAjax = false;
				} /* End date is missing */
				if ( ! endDateValue ) {
					endDateInput.addClass( 'gglnltcs-validation-failed' );
					runAjax = false;
				} /* Invalid date range. When start date aheades of end date. */
				if ( startDateValue && endDateValue ) {
					startDateValue = new Date( startDateValue ).getTime();
					endDateValue   = new Date( endDateValue   ).getTime();
					if ( startDateValue > endDateValue ) {
						$( '.gglnltcs-date span.gglnltcs-note' ).text( gglnltcsLocalize.invalidDateRange ).addClass( 'err' );
						dateInput.addClass( 'gglnltcs-invalid-date-range' );
						runAjax = false;
					}
				} /* When date didn't match the pattern */
				if ( dateInput.hasClass( 'gglnltcs-validation-failed' ) ) {
					runAjax = false;
				} /* When no metrics were selected */
				if ( $( '#gglnltcs-metrics input:checkbox:checked' ).length == 0 ) {
					$( '#gglnltcs-metrics input:checkbox' ).addClass( 'gglnltcs-validation-failed' );
					runAjax = false;
					if ( $( '#gglnltcs-metrics .gglnltcs-error-message' ).length == 0) {
						$( '#gglnltcs-metrics .gglnltcs-table-name' ).after( '<span class="gglnltcs-error-message">' +  gglnltcsLocalize.metricsValidation + '</span>' );
					}       
				} /* If Everything Is OK, 
				 /* Prevent Default Submit 
				/* and Run Ajax Function for the Table Chart*/
				event.preventDefault();
				if ( runAjax ) { 
					ajaxBuildTableChart();
				}
			/* Submit on the Line Chart Tab */
			} else if ( $( '#gglnltcs-get-statistics-button-line-chart' ).length ) {
				event.preventDefault();
				ajaxBuildLineChart();
			} else if ( $( 'input[name="gglnltcs_tracking_id"]' ).length ) {
				var trackingIdVal  = $( 'input[name="gglnltcs_tracking_id"]' ).val();
				if ( ! trackingIdVal ) {
					event.preventDefault();
					$( 'input[name="gglnltcs_tracking_id"]' ).addClass( 'gglnltcs-validation-failed' );
					console.log( trackingIdVal );
				}
			}
		});
		/* Tracking ID form preventing submit */
		$( '#gglnltcs-tracking-id-form' ).on( 'submit', function( event ) {
			event = event || window.event;
			var input = $( this ).find( 'input[name="gglnltcs_tracking_id"]' )
			if ( input.val() == 0 ) {
				input.addClass( 'gglnltcs-validation-failed' );
				event.preventDefault();
			}
		});
		$( '#gglnltcs-tracking-id-form input[name="gglnltcs_tracking_id"], input[name="gglnltcs_tracking_id"], #gglnltcs-authentication-code-input' ).on( 'keypress', function() {
			$( this ).removeClass( 'gglnltcs-validation-failed' );
		});
		/* Google Authentication form preventing submit */
		$( '#gglnltcs-authentication-form' ).on( 'submit', function( event ) {
			event = event || window.event;
			var input = $( '#gglnltcs-authentication-code-input' );
			if ( ! input.val() ) {
				event.preventDefault();
				input.addClass( 'gglnltcs-validation-failed' );
			}
		});
		/* Here We Register Which Curves To Display On The Line Chart. */
		if ( $( '#gglnltcs-metrics-line-chart' ).length ) {
			var chartCurves = {
				'visitors'			: false,
				'newVisits'			: false,
				'visits'			: false,
				'visitBounceRate'	: false,
				'avgTimeOnSite'	 	: false,
				'pageviews'		 	: false,
				'pageviewsPerVisit' : false
			};
			$( '#gglnltcs-metrics-line-chart' ).data( 'chartCurves', chartCurves );
			/* On document ready */
			$( '#gglnltcs-metrics-line-chart input:checkbox:checked' ).each( function() {
				var chartSelectedMetric = $( this ).val();
					chartSelectedMetric = chartSelectedMetric.substring(3);
				var chartCurves = $( '#gglnltcs-metrics-line-chart' ).data( 'chartCurves' );
					chartCurves[ chartSelectedMetric ] = true;
			});
			/* On change */
			$( '#gglnltcs-metrics-line-chart input:checkbox' ).on( 'change', function() {
				var chartSelectedMetric = $( this ).val();
					chartSelectedMetric = chartSelectedMetric.substring(3);
				var chartCurves = $( '#gglnltcs-metrics-line-chart' ).data( 'chartCurves' );
				if ( ! chartCurves[ chartSelectedMetric ] ) {
					chartCurves[ chartSelectedMetric ] = true;
				} else {
					chartCurves[ chartSelectedMetric ] = false;
				};
				if ( $( this ).hasClass( 'gglnltcs-validation-failed' ) ) {
					$( '#gglnltcs-metrics-line-chart input:checkbox' ).removeClass( 'gglnltcs-validation-failed' );
				}
			});
		}
		resultsTableFunctions(); /* Call all neccessary functions to process results table. */
		/* Run Ajax when user selects webproperty. */
		/* Run Ajax when documet ready if we are on the appropriate page.. */
		if( $( '#gglnltcs-continuous_chart_div' ).length ) { 
			$( '#gglnltcs-webproperties, #gglnltcs-accounts' ).on( 'change', ajaxBuildLineChart );
			ajaxBuildLineChart();
		}

	} /* close content processing. */
/************************************************************************/
	/* All neccessary processing for the results table. */
	function resultsTableFunctions() {
		/* Results table hover highlight cells */
		$( '.gglnltcs-results div.gglnltcs-table-body td' ).on( 'mouseover', function() {
			var cellIndex = $( this ).index();
			$( '.gglnltcs-results div.gglnltcs-table-body tr' ).each( function() {
				$( this ).find( 'td' ).eq( cellIndex ).addClass( 'gglnltcs-hovered-cell' );
			});
			$( this ).addClass( 'gglnltcs-this-hovered-cell' );
		});
		$( '.gglnltcs-results div.gglnltcs-table-body td' ).on( 'mouseleave', function() {
			var cellIndex = $( this ).index();
			$( '.gglnltcs-results div.gglnltcs-table-body tr' ).each( function() {
				$( this ).find( 'td' ).eq( cellIndex ).removeClass( 'gglnltcs-hovered-cell' );
			});
			$( this ).removeClass( 'gglnltcs-this-hovered-cell' );
		});
		/* Height and Width of result tables. */
		if ( $( "#gglnltcs-results-wrapper" ).length ) {
			/* Fix results table wrapper height. Prevents table from jumping, when you press group-By buttons. */
			$( '#gglnltcs-results-wrapper' ).height( $( '#gglnltcs-results-wrapper' ).height() );
			/* Set results table width. */
			setResultsTableWidth()
			$( window ).on( 'resize', setResultsTableWidth );
		}
		/* Change year month day in the results table */
		$( '#gglnltcs-group-by-Y-M-D input' ).on( 'click', function() {
			$( '#gglnltcs-group-by-Y-M-D input' ).removeClass( 'gglnltcs-selected' );
			$( this ).addClass( 'gglnltcs-selected' );
			if ( $( '.gglnltcs-results .gglnltcs-bad-results' ).length == 0 ) {
				var index = $( this ).index();
				var tablesTotal = $( '#gglnltcs-results-wrapper .gglnltcs-results' );
				tablesTotal.hide().css( 'position', 'absolute' );
				$( '#gglnltcs-results-wrapper .gglnltcs-results:eq(' + ( tablesTotal.length - index - 1 ) + ')' ).show().css( 'position', 'relative' );
			}
		});
	} /* close resultsTableFunctions. */
/************************************************************************/
	/* Set Results Table Width. */
	function setResultsTableWidth() {
		var adminMenu = $( '#adminmenuwrap');
		if ( adminMenu.length && ( adminMenu.height() != 0 ) ) {
			var adminMenuWidth = $( '#adminmenuback' ).width();
			var viewportWidth  = $( window ).width();
			var resultsThWidth = $( '.gglnltcs-results th:first' ).width();
			if ( viewportWidth > 960  ) {
				var z = 50;
			} else {
				var z = 20;
			}
			$( '#gglnltcs-results-wrapper .gglnltcs-table-body' ).width( viewportWidth - resultsThWidth - adminMenuWidth - z ).css( 'max-width', '100%' );
			$( '#wpcontent' ).width( viewportWidth - adminMenuWidth - z + 20 ).css( "overflow", "hidden" );
		} else {
			var bodyWidth = $( 'body' ).width();
			var resultsThWidth = $( '.gglnltcs-results th:first' ).width();
			$( '#gglnltcs-results-wrapper .gglnltcs-table-body' ).width( bodyWidth - resultsThWidth - 25 ).css( 'max-width', '100%' );
			$( '#wpcontent' ).width( bodyWidth ).css( "overflow", "hidden" );
		}
	}
/************************************************************************/
	/* Ajax Function To Print Tab Content When User Click Another Tab */
	function ajaxTabContent( tabName ) {
		var loadingCircle = $( '<div>', { 'class': 'gglnltcs-loading-icon' } ).hide().appendTo( '.nav-tab-wrapper' ).fadeIn( 1000 );
		var data = {
		 	action: 'gglnltcs_print_tab_content',
		 	tab: tabName,
		 	page: 'bws-google-analytics.php'
		};
		$.post( ajaxurl, data, function( data ) {
		 	$( '#gglnltcs-main-content' ).html( data ).fadeTo( 200, 1 );
		 	loadingCircle.remove(); /* Remove loading circle gif. */
			contentProcessing(); /* Update all scripts in order to apply them to new page content */
		});
	}
/***********************************************************************/
	/* Ajax Function To Build The Line Chart When User Clicks "Get Statisics" Button */
	function ajaxBuildLineChart() {
		var settings = $( '#gglnltcs-main-form' ).serialize();
		/* Block form elements while chart is being loaded to prevent double submit. */
		$( '#gglnltcs-metrics-line-chart input:checkbox' ).attr( 'disabled', true ); /* Block metrics checkboxes. */
		$( '#gglnltcs-accounts' ).attr( 'disabled', true ); /* Block accounts select. */
		$( '#gglnltcs-webproperties' ).attr( 'disabled', true ); /* Block webproperties select. */
		$( '#gglnltcs-get-statistics-button-line-chart' ).attr( 'disabled', true ); /* Block "Get Statistics" submit button. */
		/* Add loading circle gif */
		var loadingCircle = $( '<div>', { 'class': 'gglnltcs-loading-icon' } ).hide().insertAfter( '#gglnltcs-get-statistics-button-line-chart' ).fadeIn( 1000 );
		/* Blur existing chart */
		$( '#gglnltcs-continuous_chart_div' ).fadeTo( 200, .1 ); 
		/* Build new chart */
		if ( $( '#gglnltcs-metrics-line-chart input:checkbox:checked' ).length ) {
			/* First we need to hide error message that asks us to choos at least one metric */
			if ( $( '#gglnltcs-continuous_chart_div_container .gglnltcs-error-message' ).length ) {
				$( '#gglnltcs-continuous_chart_div_container .gglnltcs-error-message' ).fadeOut( 500, function() { 
				 	$( this ).remove(); 
				});
			}
			var viewProfileId = $( '#gglnltcs-view-id' ).val();
			var data = {
				action: 'gglnltcs_action',
				viewProfileId: viewProfileId,
				settings: settings,
				tab: 'line_chart',
				page: 'bws-google-analytics.php'
			};
			$.post( ajaxurl, data, function( data ) {
				data = $.parseJSON( data );
				var chartCurves = $( '#gglnltcs-metrics-line-chart' ).data( 'chartCurves' );
				var chartRows = [];
				var chartDate = data[0];
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
					if ( chartCurves.visitors   	   ) { chartRows.push( parseInt( visitors[i]   ) ) }
					if ( chartCurves.newVisits  	   ) { chartRows.push( parseInt( newVisits[i]  ) ) }
					if ( chartCurves.visits     	   ) { chartRows.push( parseInt( visits[i]     ) ) }
					if ( chartCurves.visitBounceRate   ) { chartRows.push( parseInt( bounceRate[i] ) ) }
					if ( chartCurves.avgTimeOnSite     ) { chartRows.push( parseInt( avgTime[i]    ) ) }
					if ( chartCurves.pageviews  	   ) { chartRows.push( parseInt( pageviews[i]  ) ) }
					if ( chartCurves.pageviewsPerVisit ) { chartRows.push( parseInt( perVisit[i]   ) ) } 
					ajaxChart.addRows( [ chartRows ] );
				}    
				var newChart = new google.visualization.AnnotatedTimeLine( document.getElementById( 'gglnltcs-continuous_chart_div' ));
				newChart.draw( ajaxChart, {
					displayZoomButtons: true,
					pointSize: 8,
					scaleType: 'allmaximized',
					thickness: 3,
					wmode: 'transparent'
			}); /* Bright up line chart. */
				$( '#gglnltcs-continuous_chart_div' ).fadeTo( 500, 1 );
				/* Unlock form elements after chart is loaded. */
				$( '#gglnltcs-metrics-line-chart input:checkbox' ).attr( 'disabled', false ); /* Unlock metrics checkboxes. */
				$( '#gglnltcs-accounts' ).removeAttr( 'disabled' ); /* Unlock accounts select. */
				$( '#gglnltcs-webproperties' ).removeAttr( 'disabled' ); /* Unlock webproperties select. */
				$( '#gglnltcs-get-statistics-button-line-chart' ).removeAttr( 'disabled' ); /* Unlock "Get Statistics" submit button. */
				loadingCircle.remove(); /* Remove loading circle gif */
			}); // close $.post
		} else { // close if.
			var chartErrorMessage = $( '<div>', { 'class': 'error-message', text: gglnltcsLocalize.metricsValidation } ).hide().appendTo( '#gglnltcs-continuous_chart_div_container' );
			 loadingCircle.remove();
			chartErrorMessage.fadeIn( 1000 );
			/* Unlock form elements. */
			$( '#gglnltcs-metrics-line-chart input:checkbox' ).attr( 'disabled', false ).addClass( 'validation-failed' ); /* Unlock metrics checkboxes and remove red border. */
			$( '#gglnltcs-accounts' ).removeAttr( 'disabled' ); /* Unlock accounts select. */
			$( '#gglnltcs-webproperties' ).removeAttr( 'disabled' ); /* Unlock webproperties select. */
			$( '#gglnltcs-get-statistics-button-line-chart' ).removeAttr( 'disabled' ); /* Unlock "Get Statistics" submit button. */
		}
	} /* close ajaxBuildLineChart */
/***********************************************************************/	
	/* Ajax Function To Build The Table With Results When User Clicks "Get Statisics" Button */
	function ajaxBuildTableChart() {
		$( '#gglnltcs-get-statistics-button' ).attr( 'disabled', true );
		if ( $( '#gglnltcs-results-wrapper' ).length ) {
			$( '#gglnltcs-results-wrapper' ).fadeTo( 200, .1 );
		}
		var loadingCircle = $( '<div>', { 'class' : 'gglnltcs-loading-icon' } ).hide().appendTo( '#gglnltcs-date' ).fadeIn( 1000 );
		var settings = $( '#gglnltcs-main-form' ).serialize();
		var data = {
			action: 'gglnltcs_action',
			settings: settings,
			tab: 'table_chart',
			page: 'bws-google-analytics.php'
		};
		$.post( ajaxurl, data, function( data ) {
			/* Remove existing results tables. */
			if ( $( '#gglnltcs-results-wrapper' ).length ) {
				$( '#gglnltcs-results-wrapper' ).remove();
			}
			loadingCircle.remove(); /* Remove loading circle gif. */
			$( '#gglnltcs-get-statistics-button' ).removeAttr( 'disabled' );
			$( '#gglnltcs-main-form' ).after( data ); /* Print results tables. */
			resultsTableFunctions(); /* Call all neccessary functions to process results table. */
		});
	} /* close ajaxBuildTableChart */
})( jQuery );