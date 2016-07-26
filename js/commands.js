(function($, Drupal) {

	/**
	 * Add new Ajax command for toggling maintenance mode on the UI
	 */

	Drupal.AjaxCommands.prototype.readMessage = function(ajax, response, status){

		if(response.responseData.siteCommanderCommand == 'toggleMaintenanceMode')
		{
			$('#maintenanceModeIcon').toggleClass('fa-toggle-on fa-toggle-off');
			$('#maintenanceModeBadge').toggleClass('badge-active badge-green');
			//if($('#maintenanceModeBadge').hasClass('badge-green'))
			if(response.responseData.mode == 1)
				$('#maintenanceModeBadge').html('On');
			else
				$('#maintenanceModeBadge').html('Off');
		}

		if(response.responseData.siteCommanderCommand == 'rebuildDrupalCache')
		{
			$('#last_cache_rebuild').html(response.responseData.last_cache_rebuild);
		}

		if(response.responseData.siteCommanderCommand == 'cleanupOldFiles')
		{
			$('#old-files-storage-size').html(response.responseData.oldFilesStorageSize);
			$('#last_cache_rebuild').html(response.responseData.last_cache_rebuild);
		}

		if(response.responseData.siteCommanderCommand == 'purgeSessions')
		{
			$('#num-session-entries').html(response.responseData.newNumSessionEntries);
		}

		if(response.responseData.siteCommanderCommand == 'clearApcOpCache')
		{
			$('#num-apc-opcache-entries').html(response.responseData.newNumApcOpCacheEntries);
		}

		if(response.responseData.siteCommanderCommand == 'clearPhpOpCache')
		{
			$('#num-php-opcache-scripts').html('0');
			$('#num-php-opcache-keys').html('0');
		}

		if(response.responseData.siteCommanderCommand == 'clearRedisCache')
		{
			$('#num-redis-entries').html('0');
		}

	}
	

})(jQuery, Drupal);
