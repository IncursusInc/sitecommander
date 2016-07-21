<?php

/**
 * @file
 * Contains \Drupal\drupalstat\Controller\DrupalStatController.
 */

namespace Drupal\drupalstat\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\drupalstat\Ajax\ReadMessageCommand;
use Drupal\drupalstat\DrupalStatUtils;

class DrupalStatController extends ControllerBase {

	// AJAX Callback to toggle maintenance mode
  //public function toggleMaintenanceMode() {
  public function toggleMaintenanceMode() {

		// Toggle maintenance mode via Drupal CLI
		
		// First, figure out if we are already in maintenance mode
		$currStatus = \Drupal::state()->get('system.maintenance_mode');
		if($currStatus)
			$mode = 0;
		else
			$mode = 1;

		\Drupal::state()->set('system.maintenance_mode', $mode);

    // Create AJAX Response object.
    $response = new AjaxResponse();

    // Call the DrupalStatAjaxCommand javascript function.
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
		$responseData->last_cache_rebuild = DrupalStatUtils::elapsedTime(\Drupal::state()->get('drupalstat.timestamp_cache_last_rebuild'));
    $response->addCommand( new ReadMessageCommand($responseData));

		// Return ajax response.
		return $response;
	}

}
?>
