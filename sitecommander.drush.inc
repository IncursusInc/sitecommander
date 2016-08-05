<?

use Drupal\sitecommander\Controller\BackupController;

/**
 * Implements hook_drush_command().
 */
function sitecommander_drush_command() {

  $items['sitecommander-backup-copy'] = array(
    'description' => 'Copies a backup image file to a remote location (as configured via admin/config/sitecommander).',
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
    'aliases' => array('scbr')
  );

  return $items;
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

?>
