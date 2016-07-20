<?php

/**
 * @file
 * Contains \Drupal\drupalstat\Controller\DrupalStatController.
 */

namespace Drupal\drupalstat\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;

class DrupalStatController extends ControllerBase {

	// AJAX Callback to toggle maintenance mode
  public function toggleMaintenanceMode() {

    // Create AJAX Response object.
    $response = new AjaxResponse();

    // Call the readMessage javascript function.
    //$response->addCommand( new ReadMessageCommand($message));

		// Toggle maintenance mode via Drupal CLI
		
		// First, figure out if we are already in maintenance mode
		$currStatus = \Drupal::state()->get('system.maintenance_mode');
		if($currStatus)
			$mode = 0;
		else
			$mode = 1;

		\Drupal::state()->set('system.maintenance_mode', $mode);

		// Return ajax response.
		return $response;
	}

}
?>
