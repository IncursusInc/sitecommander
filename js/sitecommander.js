jQuery(document).ready(function($){

	var dsg1, dsg2, dsg3, dsg4, dsg5, dsg6, dsg7, dsg8, dsg9, dsg10;

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

			if(drupalSettings.apcStats)
			{
				dsg4 = new JustGage({
					id: "apcOpcacheStatus",
					value: drupalSettings.apcStats.hits,
					min: 0,
					max: drupalSettings.apcStats.hits + drupalSettings.apcStats.misses,
					levelColors: ["#ff0000", "#f9c802", "#a9d70b"],
					title: "APC Cache Hits",
					label: "Cache Hits",
				});

				dsg5 = new JustGage({
					id: "apcMemoryUsage",
					value: drupalSettings.apcStats.used,
					min: 0,
					max: drupalSettings.apcStats.totalMem,
					title: "APC Memory Usage",
					label: "Memory in MB",
				});
			}

			if(drupalSettings.redisStats)
			{
				dsg6 = new JustGage({
					id: "redisKeyspaceHits",
					value: drupalSettings.redisStats.keyspaceHits,
					min: 0,
					max: drupalSettings.redisStats.keyspaceTotal,
					title: "Keyspace Hits",
					levelColors: ["#ff0000", "#f9c802", "#a9d70b"],
					label: drupalSettings.redisStats.keyspaceHitPct
				});
	
				dsg7 = new JustGage({
					id: "redisMemoryUsage",
					value: drupalSettings.redisStats.memoryUsedByRedis,
					min: 0,
					max: drupalSettings.redisStats.memoryAllocatedByRedis,
					title: "Memory Usage (MB)",
					label: "Cache Size >>",
        	humanFriendlyDecimal: 2,
        	decimals: 2
				});
	
				dsg8 = new JustGage({
					id: "redisPeakMemoryUsage",
					value: drupalSettings.redisStats.peakMemoryUsedByRedis,
					min: 0,
					max: drupalSettings.redisStats.memoryAllocatedByRedis,
					title: "Peak Memory Usage (MB)",
					label: "Cache Size >>",
        	humanFriendlyDecimal: 2,
        	decimals: 2
				});
			}


			if(drupalSettings.opCacheStats)
			{
				dsg9 = new JustGage({
					id: "opCacheHits",
					value: drupalSettings.opCacheStats.opcache_statistics.opcache_hit_rate,
					min: 0,
					max: 100,
					title: "Keyspace Hits",
					levelColors: ["#ff0000", "#f9c802", "#a9d70b"],
					label: 'Percentage'
				});
	
				dsg10 = new JustGage({
					id: "opCacheMemoryUsage",
					value: drupalSettings.opCacheStats.memory_usage.usedMemory,
					min: 0,
					max: drupalSettings.opCacheStats.memory_usage.usedMemory + drupalSettings.opCacheStats.memory_usage.freeMemory,
					title: "Memory Usage",
					label: 'MBs'
				});
	
			}

			clearInterval( timer );
		});
	}, 2500);

	// Update gauges via Ajax periodically
	setInterval(function() {
		$.get('drupalstat/update-gauges', function (response) { 
			console.log(response);
			dsg1.refresh(response[0].responseData.payload.loadAverage[0] * 100);
			dsg2.refresh(response[0].responseData.payload.loadAverage[1] * 100);
			dsg3.refresh(response[0].responseData.payload.loadAverage[2] * 100);
			if(response[0].responseData.payload.apcStats)
			{
				dsg4.refresh(response[0].responseData.payload.apcStats.hits, response[0].responseData.payload.apcStats.hits + response[0].responseData.payload.apcStats.misses);
				dsg5.refresh(response[0].responseData.payload.apcStats.used, response[0].responseData.payload.apcStats.totalMem);
			}
			if(response[0].responseData.payload.redisStats)
			{
				dsg6.refresh(response[0].responseData.payload.redisStats.keyspaceHits, response[0].responseData.payload.redisStats.keyspaceTotal);
				dsg7.refresh(response[0].responseData.payload.redisStats.memoryUsedByRedis, response[0].responseData.payload.redisStats.memoryAllocatedByRedis);
				dsg8.refresh(response[0].responseData.payload.redisStats.peakMemoryUsedByRedis, response[0].responseData.payload.redisStats.memoryAllocatedByRedis);
			}
			if(response[0].responseData.payload.opCacheStats)
			{
				dsg9.refresh(response[0].responseData.payload.opCacheStats.opcache_statistics.opcache_hit_rate);
				dsg10.refresh(response[0].responseData.payload.opCacheStats.memory_usage.usedMemory);
			}
		});
	}, drupalSettings.settings.admin.refreshRateLoadAverage * 1000);

});
