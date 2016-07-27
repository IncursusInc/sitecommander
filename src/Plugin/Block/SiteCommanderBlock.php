<?php

namespace Drupal\sitecommander\Plugin\Block;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Url;
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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sitecommander\SiteCommanderUtils;

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

	public function __construct( Connection $connection, ModuleHandler $moduleHandler, QueryFactory $entityQuery, FileSystem $fileSystem, ConfigFactory $configFactory, StateInterface $state
														 ) 
	{
		$this->connection = $connection;
		$this->moduleHandler = $moduleHandler;
		$this->entityQuery = $entityQuery;
		$this->fileSystem = $fileSystem;
		$this->configFactory = $configFactory;
		$this->state = $state;
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
      $container->get('state')
    );
  }

  public function build() {

		// TODO - split this shit up into subfunctions
		$drupalInfo = array();

		// Get breakdown of published nodes by content type
		$drupalInfo['nodeTypeNames'] = node_type_get_names();

		$query = $this->entityQuery->getAggregate('node')
											->condition('type', array_keys($drupalInfo['nodeTypeNames']), 'IN')
											->condition('status', 1)
											->groupBy('type')
											->aggregate('type', 'COUNT')
											->sortAggregate('type', 'COUNT', 'DESC');

		$tmpResult = $query->execute();

		// Put the results in a format that is easier for us to work with, using the node type machine name as the index in the array
		$result = array();
		foreach($tmpResult as $val)
			$result[ $val['type'] ] = $val;

		// Add back in the ones that don't have any nodes yet, as the query won't pick those up
		foreach($drupalInfo['nodeTypeNames'] as $machineName => $nodeTypeName)
		{
			if(!array_key_exists($machineName, $result))
				$result[] = array('type' => $machineName, 'type_count' => '0');
		}
		$drupalInfo['publishedNodeCounts'] = $result;
		
		// Get # of users
		$drupalInfo['userCount'] = $this->entityQuery->get('user')
																->condition('uid', 0, '!=')
																->count()->execute();

		// Get size of install (storage footprint) - currently only works under Linux!
		if(preg_match('/.*nux.*/', php_uname()))
		{
			ob_start();
			$tmp = preg_split('/\s+/', system('du -sb'));
			$drupalInfo['installSize'] = format_size($tmp[0]);
			ob_end_clean();
		}
		else
		{
			$drupalInfo['installSize'] = 'Unknown';
		}

		// Get size of temporary file storage
		if(preg_match('/.*nux.*/', php_uname()))
		{
			$publicPath = $this->fileSystem->realpath(file_default_scheme() . "://");

			ob_start();
			$tmp = preg_split('/\s+/', system('du -sbc ' . $publicPath . '/css ' . $publicPath . '/js '));
			ob_end_clean();

			$drupalInfo['oldFilesStorageSize'] = format_size($tmp[ 0 ]);
		}
		else
		{
			$drupalInfo['oldFilesStorageSize'] = 'Unknown';
		}

		// Get Memory Usage
		if(preg_match('/.*nux.*/', php_uname()))
		{
			$drupalInfo['memoryUsage']['totalMemory'] = 0;
			$drupalInfo['memoryUsage']['usedMemory'] = 0;

			$tmp = file('/proc/meminfo', FILE_IGNORE_NEW_LINES);
			foreach($tmp as $line)
			{
				if(preg_match('/^MemTotal:/', $line)) {
					list($label, $drupalInfo['memoryUsage']['totalMemory'], $sizeLabel) = preg_split('/\s+/', $line);
				}

				if (preg_match('/^MemFree:\s+(\d+)\skB$/', $line, $matches)) {
					$availableMemory = $matches[1];
					$drupalInfo['memoryUsage']['usedMemory'] = $drupalInfo['memoryUsage']['totalMemory'] - $availableMemory;
				}
			}
		}
		else
		{
			$drupalInfo['memoryUsage']['totalMemory'] = 0;
			$drupalInfo['memoryUsage']['usedMemory'] = 0;
		}

		// Get number of enabled modules
		$drupalInfo['enabledModulesCount'] = count($this->moduleHandler->getModuleList());

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

		// Get timestamp of last cache rebuild
		$timestamp = $this->state->get('sitecommander.timestamp_cache_last_rebuild');
		if(!$timestamp)
			 $drupalInfo['timestamp_cache_last_rebuild'] = 'Unknown';
		else
			 $drupalInfo['timestamp_cache_last_rebuild'] = SiteCommanderUtils::elapsedTime($timestamp);

		// Get # of authenticated users online right now (we look at the number of sessions that were last updated within the past 15 minutes)
		$query = $this->connection->select('sessions','s');
		$query->addExpression('COUNT( uid )');
		$query->condition('timestamp', strtotime('15 minutes ago'), '>');
		$query->condition('uid', 0, '>');

		$drupalInfo['numAuthUsersOnline'] =  $query->execute()->fetchField();
		$drupalInfo['numVisitorsOnline'] = \Drupal\sitecommander\Controller\SiteCommanderController::getAnonymousUsers();

		// Get total # of session entries in the database
		$query = $this->connection->select('sessions','s');
		$query->addExpression('COUNT( uid )');

		$drupalInfo['numSessionEntries'] = $query->execute()->fetchField();

		// If MailChimp is installed, get all MailChimp lists and total # of subscribers for each
		if ($this->moduleHandler->moduleExists('mailchimp'))
		{
			$mcLists = mailchimp_get_lists();
			$drupalInfo['mailchimp'] = $mcLists;
		}

		// Get top 15 search phrases done today
		$header = array(
			array('data' => $this->t('Count'), 'field' => 'count', 'sort' => 'desc'),
			array('data' => $this->t('Message'), 'field' => 'message'),
		);

		$count_query = $this->connection->select('watchdog');
		$count_query->addExpression('COUNT(DISTINCT(message))');
		$count_query->condition('type', 'search');

		$query = $this->connection->select('watchdog', 'w')
					->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
					->extend('\Drupal\Core\Database\Query\TableSortExtender');
		$query->addExpression('COUNT(wid)', 'count');
		$query = $query
					->fields('w', array('message', 'variables'))
					->condition('timestamp', strtotime('today'), '>=')
					->condition('w.type', 'search')
					->groupBy('message')
					->groupBy('variables')
					->limit(15)
					->orderByHeader($header);
		$query->setCountQuery($count_query);
		$result = $query->execute();

		$drupalInfo['topSearches'] = array();
		foreach ($result as $dblog) {
			$unSerializedData = unserialize($dblog->variables);	
			$drupalInfo['topSearches'][] = array('searchPhrase' => $unSerializedData['%keys'], 'count' => $dblog->count);
		}

		$drupalInfo['numCores'] = SiteCommanderUtils::getNumCores();
		$drupalInfo['loadAverage'] = \Drupal\sitecommander\Controller\SiteCommanderController::getCpuLoadAverage( $drupalInfo['numCores']);
		$drupalInfo['redisStats'] = \Drupal\sitecommander\Controller\SiteCommanderController::getRedisStats();
		$drupalInfo['opCacheStats'] = \Drupal\sitecommander\Controller\SiteCommanderController::getOpCacheStats();
		$drupalInfo['apcStats'] = \Drupal\sitecommander\Controller\SiteCommanderController::getApcStats();

		// Load up SiteCommander config settings so we can pass them to the .js
		$drupalInfo['settings']['admin'] = $this->configFactory->get('sitecommander.settings')->get();

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