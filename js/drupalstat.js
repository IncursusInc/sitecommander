jQuery(document).ready(function($){

	var dsg1, dsg2, dsg3;

	var timer = setInterval( function() {

		$('#drupalstat-loading-message-container').fadeOut("medium", function() {

			dsg1 = new JustGage({
				id: "loadAverage1",
				value: drupalSettings.loadAverage[0] * 100,
				min: 0,
				max: 100,
				title: "CPU Load Avg (1 min)",
				label: 'Percentage',
			});
	
			dsg2 = new JustGage({
				id: "loadAverage2",
				value: drupalSettings.loadAverage[1] * 100,
				min: 0,
				max: 100,
				title: "CPU Load Avg (5 min)",
				label: "Percentage",
			});
	
			dsg3 = new JustGage({
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
			dsg1.refresh(response[0].responseData.loadAverage[0] * 100);
			dsg2.refresh(response[0].responseData.loadAverage[1] * 100);
			dsg3.refresh(response[0].responseData.loadAverage[2] * 100);
		});
	}, 10000);

});
