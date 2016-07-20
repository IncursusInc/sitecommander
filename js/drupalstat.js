jQuery(document).ready(function($){

	var timer = setInterval( function() {

		$('#loading-message-container').fadeOut("medium", function() {

			var g = new JustGage({
				id: "loadAverage1",
				value: drupalSettings.loadAverage[0] * 100,
				min: 0,
				max: 100,
				title: "CPU Load Avg (1 min)",
				label: 'Percentage',
			});
	
			var g2 = new JustGage({
				id: "loadAverage2",
				value: drupalSettings.loadAverage[1] * 100,
				min: 0,
				max: 100,
				title: "CPU Load Avg (5 min)",
				label: "Percentage",
			});
	
			var g3 = new JustGage({
				id: "loadAverage3",
				value: drupalSettings.loadAverage[2] * 100,
				min: 0,
				max: 100,
				title: "CPU Load Avg (15 min)",
				label: "Percentage",
			});

			$('#toggleMaintenanceMode').click(function(e) {
				$.get( "/drupalstat/toggle-maintenance-mode", {
				}, function( resp ) {
					$('#maintenanceModeIcon').toggleClass('fa-toggle-on fa-toggle-off');
					$('#maintenanceModeBadge').toggleClass('badge-active badge-green');
					if($('#maintenanceModeBadge').hasClass('badge-green'))
						$('#maintenanceModeBadge').html('On');
					else
						$('#maintenanceModeBadge').html('Off');
				});
			});

			clearInterval( timer );
		});
	}, 2500);

});
