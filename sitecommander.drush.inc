<?php
/**
 * @file
 * Drush integration for SiteCommander
 */

use Drupal\sitecommander\Controller\BackupController;

/**
 * Implements hook_drush_command().
 */
function sitecommander_drush_command() {

  $items = [];

  $items['sitecommander-backup-copy'] = array(
    'description' => 'Copies a backup image file to a remote location (as configured via admin/config/sitecommander).',
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    'aliases' => array('scbc'),
		'arguments' => array(
			'imageFile' => 'The fully qualified path and filename of a local backup image file (or any file for that matter).',
		),
		//'options' => array(
			//'repeat' => 'The number of statement repeats.',
		//),
  );

  $items['sitecommander-backup-reset'] = array(
    'description' => 'Resets the scheduled backup timestamp.',
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    'aliases' => array('scbr')
  );

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function sitecommander_drush_help($section) {
  switch ($section) {
    case 'drush:sitecommander-backup-copy':
      return dt('Copies a backup image file to a remote location as configured in the admin panel (admin/config/sitecommander). Called via Cron after a scheduled backup if mirroring is enabled.');
    case 'drush:sitecommander-backup-reset':
      return dt('Resets the scheduled backup timestamp. Useful when testing your backup settings.');
	}
}

/**
 * Callback for the sitecommander-backup-copy command
 */
function drush_sitecommander_backup_copy( $imageFile ) {
	if(BackupController::mirrorFile($imageFile))
		echo "File mirror operation completed successfully.\n\n";
	else
		echo "File mirror operation ended in error.\n\n";
}

/**
 * Callback for the sitecommander-backup-reset command
 */
function drush_sitecommander_backup_reset() {
	\Drupal::state()->set('sitecommander.timestamp_last_backup', 0);
	echo "Timestamp of last scheduled backup reset to 0\n\n";
}
