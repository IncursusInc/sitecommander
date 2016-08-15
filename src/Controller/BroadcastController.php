<?php

/**
 * @file
 * Contains \Drupal\sitecommander\Controller\SiteCommanderController.
 */

namespace Drupal\sitecommander\Controller;

use Pusher;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\sitecommander\Ajax\ReadMessageCommand;
use Drupal\sitecommander\SiteCommanderUtils;
use Drupal\pusher_integration\Controller\PusherController;

class BroadcastController extends ControllerBase {

	protected $configFactory;
	protected $currentUser;
	public		$pusher;

	public function __construct( ConfigFactory $configFactory, AccountInterface $account )
	{
		$this->configFactory = $configFactory;
		$this->currentUser = $account;

		$this->pusher = new PusherController( $this->configFactory, $this->currentUser );
	}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('current_user')
    );
  }

	public function broadcastMessage()
	{
		$data = array(
			'messageType' => \Drupal::request()->request->get('messageType', 'info'),
			'messagePosition' => \Drupal::request()->request->get('messagePosition', 'toast-top-right'),
			'messageBody' => \Drupal::request()->request->get('messageBody', 'No message provided!')
		);

		$this->pusher->broadcastMessage( $this->configFactory, 'site-commander', 'broadcastMessage', $data );

    // Create AJAX Response object.
    $response = new AjaxResponse();
		return $response;
	}

	// AJAX Callback to toggle maintenance mode
  public function broadcastCommand( $commandName ) {

		$error = false;
		$data = null;

		$serverPoolList = $this->configFactory->get('sitecommander.settings')->get('serverPoolList');

		if(!$serverPoolList)
		{
			$error = true;
		}
		else
		{
			$hostNames = preg_split('/\n/', $serverPoolList);
			
			// Kill empty entries
			$hostNames = array_filter($hostNames);

			// Filter our dupes
			$hostNames = array_unique($hostNames);

			foreach($hostNames as $host)
			{
				$url = 'http://' . $host . '/sitecommander/processBroadcastCommand/' . $commandName;
				$this->callURL( $url, $data );
			}
		}

    // Create AJAX Response object.
    $response = new AjaxResponse();

    // Call the SiteCommanderAjaxCommand javascript function.
		$responseData = new \StdClass();
		$responseData->command = 'readMessage';
		$responseData->siteCommanderCommand = 'broadcastCommand';
		$responseData->commandPayload = $commandName;
    $response->addCommand( new ReadMessageCommand($responseData));

		// Return ajax response.
		return $response;
	}

	public function callURL( $url, $data = null )
	{
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_POST, 1);

		if ($data)
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

		// TODO: HTTP basic auth
		//curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		//curl_setopt($curl, CURLOPT_USERPWD, "username:password");
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_NOBODY, true);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($curl, CURLOPT_NOSIGNAL, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT_MS, 50);

		curl_exec($curl);
		curl_close($curl);
	}

}
