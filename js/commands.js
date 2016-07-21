(function($, Drupal) {

	/**
	 * Add new Ajax command for toggling maintenance mode on the UI
	 */

	Drupal.AjaxCommands.prototype.readMessage = function(ajax, response, status){

		if(response.responseData.drupalStatCommand == 'toggleMaintenanceMode')
		{
			$('#maintenanceModeIcon').toggleClass('fa-toggle-on fa-toggle-off');
			$('#maintenanceModeBadge').toggleClass('badge-active badge-green');
			//if($('#maintenanceModeBadge').hasClass('badge-green'))
			if(response.responseData.mode == 1)
				$('#maintenanceModeBadge').html('On');
			else
				$('#maintenanceModeBadge').html('Off');
		}

		if(response.responseData.drupalStatCommand == 'rebuildDrupalCache')
		{
			$('#last_cache_rebuild').html(response.responseData.last_cache_rebuild);
		}

	}
	

})(jQuery, Drupal);
