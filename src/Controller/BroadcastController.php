<?php

/**
 * @file
 * Contains \Drupal\sitecommander\Controller\SiteCommanderController.
 */

namespace Drupal\sitecommander\Controller;

use Pusher;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Cron;
use Drupal\Core\Ajax\AjaxResponse;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Statement;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\Query\QueryAggregateInterface;
//use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\sitecommander\Ajax\ReadMessageCommand;
use Drupal\sitecommander\SiteCommanderUtils;
use Drupal\Core\Template\TwigEnvironment;

class BroadcastController extends ControllerBase {

	protected $connection;
	protected $moduleHandler;
	protected $entityQuery;
	protected $fileSystem;
	protected $configFactory;
	protected $state;
	protected $translation;
	protected $currentUser;
	protected $twig;
	protected $cron;

	public function __construct( Connection $connection, ModuleHandler $moduleHandler, QueryFactory $entityQuery, FileSystem $fileSystem, ConfigFactory $configFactory, StateInterface $state, $account, TwigEnvironment $twig, $cron )
	{
		$this->connection = $connection;
		$this->moduleHandler = $moduleHandler;
		$this->entityQuery = $entityQuery;
		$this->fileSystem = $fileSystem;
		$this->configFactory = $configFactory;
		$this->state = $state;
		$this->currentUser = $account;
		$this->twig = $twig;
		$this->cron = $cron;
	}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('module_handler'),
      $container->get('entity.query'),
      $container->get('file_system'),
      $container->get('config.factory'),
      $container->get('state'),
      $container->get('current_user'),
      $container->get('twig'),
      $container->get('cron')
    );
  }

	public function pusherAuth()
	{
		$config = $this->configFactory->get('sitecommander.settings');
		$pusherAppId = $config->get('pusherAppId');
		$pusherAppKey = $config->get('pusherAppKey');
		$pusherAppSecret = $config->get('pusherAppSecret');
		$cluster = "ap1";

		$options = array('cluster' => $cluster, 'encrypted' => true);

		$pusher = new Pusher( $pusherAppKey, $pusherAppSecret, $pusherAppId, $options );

		$presenceData = array('user_id' => $_POST['socket_id']);

		$jsonResponse = $pusher->socket_auth($_POST['channel_name'], $_POST['socket_id'], $presenceData);

		//echo $pusher->presence_auth($_POST['channel_name'], $_POST['socket_id'], $_POST['socket_id'], $presenceData);
		echo $pusher->presence_auth($_POST['channel_name'], $_POST['socket_id'], $_POST['socket_id'], $presenceData);

    // Create AJAX Response object.
    $response = new AjaxResponse();
		return $response;
	}

	public function broadcastMessage()
	{
		$config = $this->configFactory->get('sitecommander.settings');
		$pusherAppId = $config->get('pusherAppId');
		$pusherAppKey = $config->get('pusherAppKey');
		$pusherAppSecret = $config->get('pusherAppSecret');
		$cluster = "ap1";

		$messageType = \Drupal::request()->request->get('messageType', 'info');
		$messagePosition = \Drupal::request()->request->get('messagePosition', 'top-right');
		$messageBody = \Drupal::request()->request->get('messageBody', 'No message provided!');

		$options = array('cluster' => $cluster, 'encrypted' => true);

		$pusher = new Pusher( $pusherAppKey, $pusherAppSecret, $pusherAppId, $options );

		$data = array(
			'messageType' => $messageType,
			'messageBody' => $messageBody,
			'messagePosition' => $messagePosition
		);

		$pusher->trigger( 'site-commander', 'broadcastMessage', $data );

    // Create AJAX Response object.
    $response = new AjaxResponse();

    // Call the SiteCommanderAjaxCommand javascript function.
		$responseData = new \StdClass();
		$responseData->command = 'readMessage';
		$responseData->siteCommanderCommand = 'broadcastMessage';
    $response->addCommand( new ReadMessageCommand($responseData));

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
