(function($, Drupal) {

  "use strict";

	/**
	 * Add new Ajax command for toggling maintenance mode on the UI
	 */

	Drupal.AjaxCommands.prototype.readMessage = function (ajax, response, status) {

		if (response.responseData.siteCommanderCommand == 'toggleMaintenanceMode') {
			$('#maintenanceModeIcon').toggleClass('fa-toggle-on fa-toggle-off');
			$('#maintenanceModeBadge').toggleClass('badge-active badge-green');
			if(response.responseData.mode == 1)
				$('#maintenanceModeBadge').html('On');
			else
				$('#maintenanceModeBadge').html('Off');
		}

		if (response.responseData.siteCommanderCommand == 'toggleScheduledBackups') {
			$('#backupKillSwitch').toggleClass('fa-toggle-on fa-toggle-off');
			$('#backupKillSwitch').toggleClass('icon-green site-commander-icon-noaction');

			$('#minHoursBetweenBackups').toggleClass('label-success label-default');
			$('#timeStampNextBackup').toggleClass('label-success label-default');
		}

		if (response.responseData.siteCommanderCommand == 'runCron') {
			$('#cronLastRun').html(response.responseData.timestamp_cron_last_run);
		}

		if (response.responseData.siteCommanderCommand == 'rebuildDrupalCache') {
			$('#timestamp_cache_last_rebuild').html(response.responseData.timestamp_cache_last_rebuild);
		}

		if (response.responseData.siteCommanderCommand == 'cleanupOldFiles') {
			$('#oldFilesStorageSize').html(response.responseData.oldFilesStorageSize);
			$('#timestamp_cache_last_rebuild').html(response.responseData.timestamp_cache_last_rebuild);
		}

		if (response.responseData.siteCommanderCommand == 'purgeSessions') {
			$('#numSessionEntries').html(response.responseData.newNumSessionEntries);
		}

		if (response.responseData.siteCommanderCommand == 'clearApcOpCache') {
			$('#num-apc-opcache-entries').html(response.responseData.newNumApcOpCacheEntries);
		}

		if(response.responseData.siteCommanderCommand == 'clearPhpOpCache') {
			$('#numPhpOpcacheScripts').html('0');
			$('#numPhpOpcacheKeys').html('0');
		}

		if (response.responseData.siteCommanderCommand == 'clearRedisCache') {
			$('#numRedisObjectsCached').html('0');
		}

		if (response.responseData.siteCommanderCommand == 'deleteSession') {
			$('#' + response.responseData.sid ).fadeOut("medium");
		}

		if (response.responseData.siteCommanderCommand == 'makeBackup') {
		}

		if (response.responseData.siteCommanderCommand == 'restoreBackup') {
		}

		if (response.responseData.siteCommanderCommand == 'deleteBackup') {
			$('#' + response.responseData.payload).fadeOut("medium");
		}

		document.getElementById('sitecommander-task-complete').cloneNode(true).play();
	}
	

})(jQuery, Drupal);
