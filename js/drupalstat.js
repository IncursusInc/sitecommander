jQuery(document).ready(function($){

	var g1, g2, g3;

	var timer = setInterval( function() {

		$('#drupalstat-loading-message-container').fadeOut("medium", function() {

			g1 = new JustGage({
				id: "loadAverage1",
				value: drupalSettings.loadAverage[0] * 100,
				min: 0,
				max: 100,
				title: "CPU Load Avg (1 min)",
				label: 'Percentage',
			});
	
			g2 = new JustGage({
				id: "loadAverage2",
				value: drupalSettings.loadAverage[1] * 100,
				min: 0,
				max: 100,
				title: "CPU Load Avg (5 min)",
				label: "Percentage",
			});
	
			g3 = new JustGage({
				id: "loadAverage3",
				value: drupalSettings.loadAverage[2] * 100,
				min: 0,
				max: 100,
				title: "CPU Load Avg (15 min)",
				label: "Percentage",
			});

			clearInterval( timer );
		});
	}, 2500);

	// Update gauges via Ajax periodically
	setInterval(function() {
		$.get('drupalstat/update-gauges', function (response) { 
			console.log(response);
			g1.refresh(response[0].responseData.loadAverage[0] * 100);
			g2.refresh(response[0].responseData.loadAverage[1] * 100);
			g3.refresh(response[0].responseData.loadAverage[2] * 100);
		});
	}, 10000);

});
