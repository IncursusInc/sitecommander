<?

use Drupal\sitecommander\Controller\BackupController;

/**
 * Implements hook_drush_command().
 */
function sitecommander_drush_command() {

  $items['sitecommander-backup-copy'] = array(
    'description' => 'Copies a backup image file to a remote location.',
    'aliases' => array('scbc'),
		'arguments' => array(
			'imageFile' => 'The fully qualified path and filename of a local backup image file (or any file for that matter).',
		),
		//'options' => array(
			//'repeat' => 'The number of statement repeats.',
		//),
  );

  return $items;
}

/**
 * Callback for the sitecommander-backup-copy command
 */
function drush_sitecommander_backup_copy( $imageFile ) {

	BackupController::mirrorFile($imageFile);

}
?>
