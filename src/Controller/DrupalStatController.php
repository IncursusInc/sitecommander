<?php

/**
 * @file
 * Contains \Drupal\drupalstat\Controller\DrupalStatController.
 */

namespace Drupal\drupalstat\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\drupalstat\Ajax\ReadMessageCommand;
use Drupal\drupalstat\DrupalStatUtils;

class DrupalStatController extends ControllerBase {

	protected $connection;
	protected $state;
	protected $fileSystem;
	protected $currentUser;

	public function __construct( Connection $connection, StateInterface $state, FileSystem $fileSystem, AccountInterface $account ) {
		$this->connection = $connection;
		$this->state = $state;
		$this->fileSystem = $fileSystem;
		$this->currentUser = $account;
	}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('state'),
      $container->get('file_system'),
      $container->get('current_user')
    );
  }

	// AJAX Callback to toggle maintenance mode
  //public function toggleMaintenanceMode() {
  public function toggleMaintenanceMode() {

		// Toggle maintenance mode via Drupal CLI
		// First, figure out if we are already in maintenance mode
		$currStatus = $this->state->get('system.maintenance_mode');
		if($currStatus)
			$mode = 0;
		else
			$mode = 1;

		$this->state->set('system.maintenance_mode', $mode);

    // Create AJAX Response object.
    $response = new AjaxResponse();

    // Call the DrupalStatAjaxCommand javascript function.
		$responseData = new \StdClass();
		$responseData->command = 'readMessage';
		$responseData->drupalStatCommand = 'toggleMaintenanceMode';
		$responseData->mode = $mode;
    $response->addCommand( new ReadMessageCommand($responseData));

		// Return ajax response.
		return $response;
	}

	// Flush/rebuild Drupal cache from within DrupalStat
  public function rebuildDrupalCache() {
		
		// Flush caches
		drupal_flush_all_caches();

    // Create AJAX Response object.
    $response = new AjaxResponse();

    // Call the DrupalStatAjaxCommand javascript function.
		$responseData->command = 'readMessage';
		$responseData->drupalStatCommand = 'rebuildDrupalCache';
		$responseData->last_cache_rebuild = DrupalStatUtils::elapsedTime($this->state->get('drupalstat.timestamp_cache_last_rebuild'));
    $response->addCommand( new ReadMessageCommand($responseData));

		// Return ajax response.
		return $response;
	}

	// Remove old aggregated css/js files
  public function cleanupOldFiles() {
		
		// Find all old CSS/JS files and remove them
		$publicPath = $this->fileSystem->realpath(file_default_scheme() . "://");

		$dirs = array(
			$publicPath.'/css', 
			$publicPath.'/js'
		);

		foreach($dirs as $dir)
		{
			foreach (glob($dir."/*") as $file) {
				// If file is 24 hours (86400 seconds) old then delete it
				if (filemtime($file) < time() - 86400) {
					unlink($file);
				}
			}
		}

		// Rebuild the cache (in case we've wiped out our current aggregated css/js files :)
		drupal_flush_all_caches();

		// Recalculate the size of those directories
		ob_start();
		$tmp = preg_split('/\s+/', system('du -shc ' . $publicPath . '/css ' . $publicPath . '/js '));
		ob_end_clean();
		$oldFilesStorageSize = $tmp[ 0 ];

    // Create AJAX Response object.
    $response = new AjaxResponse();

    // Call the DrupalStatAjaxCommand javascript function.
		$responseData->command = 'readMessage';
		$responseData->drupalStatCommand = 'cleanupOldFiles';
		$responseData->oldFilesStorageSize = $oldFilesStorageSize;
		$responseData->last_cache_rebuild = DrupalStatUtils::elapsedTime($this->state->get('drupalstat.timestamp_cache_last_rebuild'));
    $response->addCommand( new ReadMessageCommand($responseData));

		// Return ajax response.
		return $response;
	}

	// Purge all session entries (except for the current one)
  public function purgeSessions() {
		
		// Remove all session entries from the session table except for the one for the current user!
		$currentUserUID = $this->currentUser->id();

		$query = $this->connection->delete('sessions');
		$query->condition('uid', $currentUserUID, '!=');
		$query->execute();

		// Fetch the new count of session entries (should be 1 unless they are logged in from multiple places with the same account)
		$query = $this->connection->select('sessions','s');
		$query->addExpression('COUNT( uid )');

		$newNumSessionEntries = $query->execute()->fetchField();

    // Create AJAX Response object.
    $response = new AjaxResponse();

    // Call the DrupalStatAjaxCommand javascript function.
		$responseData->command = 'readMessage';
		$responseData->drupalStatCommand = 'purgeSessions';
		$responseData->newNumSessionEntries = $newNumSessionEntries;
    $response->addCommand( new ReadMessageCommand($responseData));

		// Return ajax response.
		return $response;
	}

}
