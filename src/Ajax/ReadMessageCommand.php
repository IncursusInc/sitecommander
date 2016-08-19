<?php

namespace Drupal\sitecommander\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 *
 */
class ReadMessageCommand implements CommandInterface {

  protected $responseData;

  /**
   * Constructs a SiteCommanderAjaxCommand object.
   */
  public function __construct($responseData) {
    $this->responseData = $responseData;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return array(
      'command' => $this->responseData->command,
      'responseData' => $this->responseData,
    );
  }

}
