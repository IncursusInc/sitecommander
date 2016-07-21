(function($, Drupal) {

	/**
	 * Add new Ajax command for toggling maintenance mode on the UI
	 */

	Drupal.AjaxCommands.prototype.readMessage = function(ajax, response, status){

		$('#maintenanceModeIcon').toggleClass('fa-toggle-on fa-toggle-off');
		$('#maintenanceModeBadge').toggleClass('badge-active badge-green');
		//if($('#maintenanceModeBadge').hasClass('badge-green'))
		if(response.mode == 1)
			$('#maintenanceModeBadge').html('On');
		else
			$('#maintenanceModeBadge').html('Off');
	}

})(jQuery, Drupal);
