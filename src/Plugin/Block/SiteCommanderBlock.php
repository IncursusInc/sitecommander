<?php

namespace Drupal\sitecommander\Plugin\Block;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Url;
use Drupal\Core\Cron;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\File\FileSystem;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\Query\QueryAggregateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Template\TwigEnvironment;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sitecommander\SiteCommanderUtils;
use Drupal\sitecommander\Controller\BackupController;

/**
 * Provides a SiteCommander Block
 *
 * @Block(
 *   id = "sitecommander_block",
 *   admin_label = @Translation("SiteCommander Block"),
 * )
 */
class SiteCommanderBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */

  /**
   * @var \Drupal\Core\Database\Connection
   */

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

	public function __construct( Connection $connection, ModuleHandler $moduleHandler, QueryFactory $entityQuery, FileSystem $fileSystem, ConfigFactory $configFactory, StateInterface $state, AccountInterface $account,TwigEnvironment $twig, $cron ) 
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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
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

  public function build() {

		$sc = new \Drupal\sitecommander\Controller\SiteCommanderController($this->connection, $this->moduleHandler, $this->entityQuery, $this->fileSystem, $this->configFactory, $this->state, $this->currentUser, $this->twig, $this->cron );

		list($drupalInfo['nodeTypeNames'], $drupalInfo['publishedNodeCounts']) = $sc->getPublishedNodeCounts();
		$drupalInfo['userCount'] = $sc->getUserCount();
		$drupalInfo['installSize'] = $sc->getInstallSize();
		$drupalInfo['oldFilesStorageSize'] = $sc->getOldFilesStorageSize();
		$drupalInfo['enabledModulesCount'] = $sc->getEnabledModulesCount();
		$drupalInfo['numAuthUsersOnline'] =  $sc->getNumAuthUsersOnline();
		$drupalInfo['numSessionEntries'] = $sc->getNumSessionEntries();
		$drupalInfo['sessionedUsers'] = $sc->getSessionedUsers();
		$drupalInfo['usersOnline'] = $sc->getUsersOnline();
		$drupalInfo['mailchimp'] = $sc->getMailChimpInfo();
		$drupalInfo['topSearches'] = $sc->getTodaysTopSearches();
		$drupalInfo['numVisitorsOnline'] = $sc->getAnonymousUsers();
		$drupalInfo['numCores'] = SiteCommanderUtils::getNumCores();
		$drupalInfo['loadAverage'] = $sc->getCpuLoadAverage( $drupalInfo['numCores']);
		$drupalInfo['memInfo'] = $sc->getMemoryInfo();
		$drupalInfo['redisStats'] = $sc->getRedisStats();
		$drupalInfo['opCacheStats'] = $sc->getOpCacheStats();
		$drupalInfo['apcStats'] = $sc->getApcStats();
		$drupalInfo['storageHealth'] = $sc->getStorageHealth();

		// Drupal settings
		$drupalInfo['settings'] = array();
		$drupalInfo['settings']['system']['site'] = $this->configFactory->get('system.site')->get();
		$drupalInfo['settings']['theme'] = $this->configFactory->get('system.theme')->get();

		// Cron info
		$drupalInfo['cron']['cron_key'] = $this->state->get('system.cron_key');
		$drupalInfo['cron']['cron_last'] = SiteCommanderUtils::elapsedTime($this->state->get('system.cron_last'));

		// Last time Drupal/Modules were checked for updates
		$drupalInfo['update_last_check'] = SiteCommanderUtils::elapsedTime($this->state->get('update.last_check'));

		// The line below will send the admin user back to the status page, which may not be desirable
		//$destination = \Drupal::destination('/admin/reports/updates')->getAsArray();
		$destination = array('destination' => '/admin/reports/updates');
    $drupalInfo['updateCheckURL'] = \Drupal::url('update.manual_status', [], ['query' => $destination]);

		// Maintenance mode status
		$drupalInfo['maintenance_mode'] = $this->state->get('system.maintenance_mode') ? 'On' : 'Off';

		// Is a drush backup still running?
		$drupalInfo['isBackupRunning'] = SiteCommanderUtils::isProcessRunning("drush archive-dump");

		// Get timestamp of last cache rebuild
		$timestamp = $this->state->get('sitecommander.timestamp_cache_last_rebuild');
		if(!$timestamp)
			 $drupalInfo['timestamp_cache_last_rebuild'] = 'Unknown';
		else
			 $drupalInfo['timestamp_cache_last_rebuild'] = SiteCommanderUtils::elapsedTime($timestamp);

		// Get the path to the module install directory
		$drupalInfo['sitecommanderInstallPath'] = drupal_get_path('module', 'sitecommander');

		// Load up SiteCommander config settings so we can pass them to the .js
		$drupalInfo['settings']['admin'] = $this->configFactory->get('sitecommander.settings')->get();

		// Get list of backups
		$backupDir = $this->configFactory->get('sitecommander.settings')->get('backupDirectory');
		$drupalInfo['backupList'] = BackupController::getBackupList( $backupDir );

    return array(
			'#theme' => 'sitecommander',
			'#attached' => array(
				'library' =>  array(
					'sitecommander/sitecommander'
				),
				'drupalSettings' => $drupalInfo
			),
			'#drupalInfo' => $drupalInfo,
			'#cache' => [ 'max-age' => 0, ],
    );
  }
}
