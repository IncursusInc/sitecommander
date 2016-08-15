<?php

/**
 * @file
 * Contains \Drupal\sitecommander\Controller\SiteCommanderController.
 */

namespace Drupal\sitecommander\Controller;

//use Pusher;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Cron;
use Drupal\Core\Ajax\AjaxResponse;
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
use Drupal\sitecommander\Ajax\ReadMessageCommand;
use Drupal\sitecommander\SiteCommanderUtils;
use Drupal\pusher_integration\Controller\PusherController;
use Drupal\Core\Template\TwigEnvironment;

class SiteCommanderController extends ControllerBase {

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
	public		$pusher;

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

		// Create connection to Pusher (if it is enabled)
		if($this->configFactory->get('sitecommander.settings')->get('enableBroadcastManager'))
			$this->pusher = new PusherController( $this->configFactory, $this->currentUser );
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

	public function processBroadcastCommand( $commandName )
	{
		return $this->$commandName();
	}

	// AJAX Callback to toggle maintenance mode
  public function toggleMaintenanceMode() {

		// Toggle maintenance mode via Drupal
		// First, figure out if we are already in maintenance mode
		$currStatus = $this->state->get('system.maintenance_mode');
		if($currStatus)
			$mode = 0;
		else
			$mode = 1;

		$this->state->set('system.maintenance_mode', $mode);

    // Create AJAX Response object.
    $response = new AjaxResponse();

    // Call the SiteCommanderAjaxCommand javascript function.
		$responseData = new \StdClass();
		$responseData->command = 'readMessage';
		$responseData->siteCommanderCommand = 'toggleMaintenanceMode';
		$responseData->mode = $mode;
    $response->addCommand( new ReadMessageCommand($responseData));

		// Return ajax response.
		return $response;
	}

	// AJAX Callback to toggle scheduled backups
  public function toggleScheduledBackups() {

		// Toggle scheduled backups via Drupal 
		// First, figure out if we are already in maintenance mode
		$currStatus = $this->configFactory->get('sitecommander.settings')->get('enableScheduledBackups');
		if($currStatus)
			$mode = 0;
		else
			$mode = 1;

		$config = $this->configFactory->getEditable('sitecommander.settings');
		$config->set('enableScheduledBackups', $mode)->save();

    // Create AJAX Response object.
    $response = new AjaxResponse();

    // Call the SiteCommanderAjaxCommand javascript function.
		$responseData = new \StdClass();
		$responseData->command = 'readMessage';
		$responseData->siteCommanderCommand = 'toggleScheduledBackups';
		$responseData->mode = $mode;
    $response->addCommand( new ReadMessageCommand($responseData));

		// Return ajax response.
		return $response;
	}

	// Flush/rebuild Drupal cache from within SiteCommander
  public function rebuildDrupalCache() {
		
		// Flush caches
		drupal_flush_all_caches();

    // Create AJAX Response object.
    $response = new AjaxResponse();

    // Call the SiteCommanderAjaxCommand javascript function.
		$responseData = new \StdClass();
		$responseData->command = 'readMessage';
		$responseData->siteCommanderCommand = 'rebuildDrupalCache';
		$responseData->timestamp_cache_last_rebuild = SiteCommanderUtils::elapsedTime($this->state->get('sitecommander.timestamp_cache_last_rebuild'));
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

    // Call the SiteCommanderAjaxCommand javascript function.
		$responseData = new \StdClass();
		$responseData->command = 'readMessage';
		$responseData->siteCommanderCommand = 'cleanupOldFiles';
		$responseData->oldFilesStorageSize = $oldFilesStorageSize;
		$responseData->timestamp_cache_last_rebuild = SiteCommanderUtils::elapsedTime($this->state->get('sitecommander.timestamp_cache_last_rebuild'));
    $response->addCommand( new ReadMessageCommand($responseData));

		// Return ajax response.
		return $response;
	}

	// Clear Redis cache
	public function clearRedisCache()
	{
		$redisHostName = $this->configFactory->get('sitecommander.settings')->get('redisHostName');
		$redisPort = $this->configFactory->get('sitecommander.settings')->get('redisPort');
		$redisDatabaseIndex = $this->configFactory->get('sitecommander.settings')->get('redisDatabaseIndex');

		// Try to get existing Redis connection, if one is available
		$redis = \Drupal\redis\ClientFactory::getClient();

		if (!$redis)
		{
			if (class_exists('Redis') && $redisHostName && $redisPort) {

				$redis = new \Redis();

				$redis->connect($redisHostName, $redisPort);
				$redis->select($redisDatabaseIndex);
				$redis->flushAll();
			}
		}
		else
		{
			$redis->select($redisDatabaseIndex);
			$redis->flushAll();
		}

    // Create AJAX Response object.
    $response = new AjaxResponse();

    // Call the SiteCommanderAjaxCommand javascript function.
		$responseData = new \StdClass();
		$responseData->command = 'readMessage';
		$responseData->siteCommanderCommand = 'clearRedisCache';
    $response->addCommand( new ReadMessageCommand($responseData));

		// Return ajax response.
		return $response;
	}

	// Clear PHP OpCache
	public function clearPhpOpCache()
	{
		opcache_reset();

    // Create AJAX Response object.
    $response = new AjaxResponse();

    // Call the SiteCommanderAjaxCommand javascript function.
		$responseData = new \StdClass();
		$responseData->command = 'readMessage';
		$responseData->siteCommanderCommand = 'clearPhpOpCache';
    $response->addCommand( new ReadMessageCommand($responseData));

		// Return ajax response.
		return $response;
	}

	// Clear APC Op Cache
	public function clearApcOpCache()
	{
		apc_clear_cache();

		$apcOpCacheInfo = apc_cache_info('opcode', true);
		$numEntries = $apcOpCacheInfo['num_entries'];

    // Create AJAX Response object.
    $response = new AjaxResponse();

    // Call the SiteCommanderAjaxCommand javascript function.
		$responseData = new \StdClass();
		$responseData->command = 'readMessage';
		$responseData->siteCommanderCommand = 'clearApcOpCache';
		$responseData->newNumApcOpCacheEntries = $numEntries;
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

    // Call the SiteCommanderAjaxCommand javascript function.
		$responseData = new \StdClass();
		$responseData->command = 'readMessage';
		$responseData->siteCommanderCommand = 'purgeSessions';
		$responseData->newNumSessionEntries = $newNumSessionEntries;
    $response->addCommand( new ReadMessageCommand($responseData));

		// Return ajax response.
		return $response;
	}

	public function deleteSession($sid = '') {

		$query = $this->connection->delete('sessions');
		$query->condition('sid', $sid, '=');
		$query->execute();

    // Create AJAX Response object.
    $response = new AjaxResponse();

    // Call the SiteCommanderAjaxCommand javascript function.
		$responseData = new \StdClass();
		$responseData->command = 'readMessage';
		$responseData->siteCommanderCommand = 'deleteSession';
		$responseData->sid = $sid;
    $response->addCommand( new ReadMessageCommand($responseData));

		// Return ajax response.
		return $response;
	}

	public function updatePoll()
	{
		$drupalInfo['numCores'] = SiteCommanderUtils::getNumCores();
		$uptime = \Drupal\sitecommander\Controller\SiteCommanderController::getUptime( $drupalInfo['numCores'] );
		$drupalInfo['uptime'] = $uptime['uptime'];
		$drupalInfo['idletime'] = $uptime['idletime'];
		$drupalInfo['idlepct'] = $uptime['idlepct'];

		$drupalInfo['loadAverage'] = \Drupal\sitecommander\Controller\SiteCommanderController::getCpuLoadAverage( $drupalInfo['numCores']);
		$drupalInfo['memInfo'] = \Drupal\sitecommander\Controller\SiteCommanderController::getMemoryInfo();
		$drupalInfo['redisStats'] = \Drupal\sitecommander\Controller\SiteCommanderController::getRedisStats();
		$drupalInfo['numRedisObjectsCached'] = $drupalInfo['redisStats']['numObjectsCached'];
		$drupalInfo['opCacheStats'] = \Drupal\sitecommander\Controller\SiteCommanderController::getOpCacheStats();
		$drupalInfo['numPhpOpcacheScripts'] = $drupalInfo['opCacheStats']['opcache_statistics']['num_cached_scripts'];
		$drupalInfo['numPhpOpcacheKeys'] = $drupalInfo['opCacheStats']['opcache_statistics']['num_cached_keys'];
		$drupalInfo['apcStats'] = \Drupal\sitecommander\Controller\SiteCommanderController::getApcStats();
		$drupalInfo['storageHealth'] = \Drupal\sitecommander\Controller\SiteCommanderController::getStorageHealth();
		$drupalInfo['numAuthUsersOnline'] =  $this->getNumAuthUsersOnline();
		$drupalInfo['numSessionEntries'] = $this->getNumSessionEntries();
		$drupalInfo['numVisitorsOnline'] = $this->getAnonymousUsers();
		$drupalInfo['oldFilesStorageSize'] = $this->getOldFilesStorageSize();
		$drupalInfo['backupStorageSize'] = $this->getBackupStorageSize();
		$drupalInfo['minHoursBetweenBackups'] = $this->configFactory->get('sitecommander.settings')->get('minHoursBetweenBackups');
		$drupalInfo['backupMaxAgeInDays'] = $this->configFactory->get('sitecommander.settings')->get('backupMaxAgeInDays');
		$drupalInfo['enableScheduledBackups'] = $this->configFactory->get('sitecommander.settings')->get('enableScheduledBackups');

		$drupalInfo['dbDriver'] = $this->connection->driver();
		$drupalInfo['dbStats'] = $this->getDatabaseStats( $drupalInfo['dbDriver'] );
		$drupalInfo['dbConfig'] = $this->getDatabaseConfig( $drupalInfo['dbDriver'] );
		$this->calculateDbFields($drupalInfo);

		$drupalInfo['pusherNumPublicSubscribers'] = $this->getPusherNumSubscribers();

		// Let's figure out how many modules need to be (or can/should be) updated
		$available = update_get_available(TRUE);
		$project_data = update_calculate_project_data($available);

		$drupalInfo['moduleUpdatesAvailable'] = 0;

		foreach($project_data as $name => $project)
		{
			// Skip ones that are already up to date
			if ($project['status'] == UPDATE_CURRENT) continue;
  
			$drupalInfo['moduleUpdatesAvailable']++;
		}

		if($this->state->get('sitecommander.timestamp_last_backup'))
			$drupalInfo['timeStampNextBackup'] = date('Y.m.d H:i:s', $this->state->get('sitecommander.timestamp_last_backup') + ($drupalInfo['minHoursBetweenBackups'] * 60 * 60));
		else
			$drupalInfo['timeStampNextBackup'] = 'Unknown';

		$drupalInfo['usersOnline'] = \Drupal\sitecommander\Controller\SiteCommanderController::getUsersOnline();
    $path = '@sitecommander/tab-users-online.html.twig';
    $template = $this->twig->loadTemplate($path);

    $drupalInfo['usersOnlineTable'] = $template->render(['drupalInfo' => $drupalInfo]);

		// Cron info
		$drupalInfo['cron']['cron_key'] = $this->state->get('system.cron_key');
		$drupalInfo['cronLastRun'] = SiteCommanderUtils::elapsedTime($this->state->get('system.cron_last'));

		// Is a drush backup still running?
		$drupalInfo['isBackupRunning'] = SiteCommanderUtils::isProcessRunning("drush archive-dump");

		// Last time Drupal/Modules were checked for updates
		$drupalInfo['lastCheckUpdates'] = SiteCommanderUtils::elapsedTime($this->state->get('update.last_check'));

		// Get timestamp of last cache rebuild
		$timestamp = $this->state->get('sitecommander.timestamp_cache_last_rebuild');
		if(!$timestamp)
			 $drupalInfo['timestamp_cache_last_rebuild'] = 'Unknown';
		else
			 $drupalInfo['timestamp_cache_last_rebuild'] = SiteCommanderUtils::elapsedTime($timestamp);

    // Create AJAX Response object.
    $response = new AjaxResponse();

    // Call the SiteCommanderAjaxCommand javascript function.
		$responseData = new \StdClass();
		$responseData->command = 'readMessage';
		$responseData->siteCommanderCommand = 'updatePoll';
		$responseData->payload = $drupalInfo;
    $response->addCommand( new ReadMessageCommand($responseData));

		// Return ajax response.
		return $response;
	}

	// Get Redis stats if they are using it as a caching backend
	public function getRedisStats()
	{
		$redis = \Drupal\redis\ClientFactory::getClient();

		$redisHostName = $this->configFactory->get('sitecommander.settings')->get('redisHostName');
		$redisPort = $this->configFactory->get('sitecommander.settings')->get('redisPort');
		$redisDatabaseIndex = $this->configFactory->get('sitecommander.settings')->get('redisDatabaseIndex');

		if (!$redis)
		{
			if (class_exists('Redis') && $redisHostName && $redisPort) {

				$redis = new \Redis();

				$redis->connect($redisHostName, $redisPort);
				$redis->select($redisDatabaseIndex);

				// Do not allow PhpRedis serialize itself data, we are going to do it
				// ourself. This will ensure less memory footprint on Redis size when
				// we will attempt to store small values.
				$redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);
			}
		}

		if($redis)
		{
			$redisInfo = $redis->info();
			$redisConfig = $redis->config('GET', '*');

			// Keyspace hits/misses gauge
			$redisStats = array();
			$redisStats['keyspaceHits'] = $redisInfo['keyspace_hits'];
			$redisStats['keyspaceMisses'] = $redisInfo['keyspace_misses'];
			$redisStats['keyspaceTotal'] = $redisInfo['keyspace_hits'] + $redisInfo['keyspace_misses'];
			$redisStats['keyspaceMissPct'] = round( $redisInfo['keyspace_misses'] / $redisStats['keyspaceTotal'], 2) * 100 . '%';
			$redisStats['keyspaceHitPct'] = round( $redisInfo['keyspace_hits'] / $redisStats['keyspaceTotal'], 2) * 100;

			// Memory Usage Gauge
			$redisStats['memoryAllocatedByRedis'] = round($redisConfig['maxmemory'] / pow(1024, 2), 4);
			$redisStats['memoryUsedByRedis'] = round($redisInfo['used_memory'] / pow(1024, 2), 4);

			// Peak Memory Usage Gauge
			$redisStats['peakMemoryAllocatedByRedis'] = round($redisConfig['maxmemory'] / pow(1024, 2), 4);
			$redisStats['peakMemoryUsedByRedis'] = round($redisInfo['used_memory_peak'] / pow(1024, 2), 4);

			// Number of cached objects
			list($keys, $rest) = preg_split('/,/', $redisInfo['db' . $redisDatabaseIndex]);
			list($keys, $numObjects) = preg_split('/=/', $keys);

			// Format I/O stats
			$redisInfo['totalNetInputBytesFormatted'] = format_size( $redisInfo['total_net_input_bytes'] );
			$redisInfo['totalNetOutputBytesFormatted'] = format_size( $redisInfo['total_net_output_bytes'] );

			$redisStats['numObjectsCached'] = $numObjects ? $numObjects : 0;

			$redisInfo = array_merge($redisStats, $redisInfo);
			return $redisInfo;
		}

		return array();
	}

	// Get APC stats if they are using it
	public function getApcStats()
	{
		if(extension_loaded('apc') && ini_get('apc.enabled')) 
		{
			$apcOpCacheInfo = apc_cache_info('', true);
			$apcSmaInfo = apc_sma_info(true);
			$apcStats = array(
				'numSlots' => $apcOpCacheInfo['num_slots'],
				'used' => round($apcOpCacheInfo['mem_size'] / pow(1024, 2), 2),
				'free' => round($apcSmaInfo['avail_mem'] / pow(1024, 2), 2),
				'totalMem' => round( ($apcSmaInfo['avail_mem'] + $apcOpCacheInfo['mem_size'] ) / pow(1024, 2), 2),
				'hits' => $apcOpCacheInfo['num_hits'],
				'misses' => $apcOpCacheInfo['num_misses'],
				'numEntries' => $apcOpCacheInfo['num_entries'],
			);

			return $apcStats;
		}
	}

	// Get opcache stats if they are using it
	public function getOpCacheStats()
	{
		if (function_exists('opcache_get_status')) {
			$stats = opcache_get_status(false);

			$stats['memory_usage']['usedMemory'] = round($stats['memory_usage']['used_memory'] / pow(1024, 2), 4);
			$stats['memory_usage']['freeMemory'] = round($stats['memory_usage']['free_memory'] / pow(1024, 2), 4);
			$stats['memory_usage']['allocatedMemory'] = round($stats['memory_usage']['usedMemory'] + $stats['memory_usage']['freeMemory'], 0);

			return $stats;
		}
	}

	// Get uptime/idletime
	public function getUptime( $numCores=1 )
	{
		if(preg_match('/.*nux.*/', php_uname()))
		{
			ob_start();
			list($uptime, $idletime) = preg_split('/\s+/', system('cat /proc/uptime'));
			ob_end_clean();

			$idlepct = round( ($idletime/$numCores) / $uptime * 100, 2);
			$uptime = SiteCommanderUtils::formatUptime($uptime);
			$idletime = SiteCommanderUtils::formatUptime($idletime / $numCores);
		}
		else
		{
			$uptime = 'Unknown';
			$idletime = 'Unknown';
			$idlepct = 'Unknown';
		}

		return array('uptime' => $uptime, 'idletime' => $idletime, 'idlepct' => $idlepct);
	}

	public function getCpuLoadAverage( $numCores = 1 )
	{
		// Get CPU load average
		if(preg_match('/.*nux.*/', php_uname()))
		{
			ob_start();
			$tmp = preg_split('/\s+/', system('cat /proc/loadavg'));
			$loadAverage = array($tmp[0], $tmp[1], $tmp[2], $tmp[0]/$numCores, $tmp[1]/$numCores, $tmp[2]/$numCores);
			ob_end_clean();
		}
		else
		{
			$loadAverage = array(0, 0, 0, 0, 0, 0);
		}

		return $loadAverage;
	}

	public function getAnonymousUsers()
	{
		$redisHostName = $this->configFactory->get('sitecommander.settings')->get('redisHostName');
		$redisPort = $this->configFactory->get('sitecommander.settings')->get('redisPort');
		$redisDatabaseIndex = $this->configFactory->get('sitecommander.settings')->get('redisDatabaseIndex');

		if (class_exists('Redis') && $redisHostName && $redisPort) {

			$redis = new \Redis();

			$redis->connect($redisHostName, $redisPort);
			$redis->select($redisDatabaseIndex);

			// Do not allow PhpRedis serialize itself data, we are going to do it
			// ourself. This will ensure less memory footprint on Redis size when
			// we will attempt to store small values.
			$redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);

			$anonUserKeys = $redis->keys('siteCommander_anon_user_*');

			if(!isset($anonUserKeys) || !count($anonUserKeys))
				return 0;
			else
				return count($anonUserKeys);
		}
	}

	// Determine health of attached storage devices
	public function getStorageHealth()
	{
		// Linux
		if(preg_match('/.*nux.*/', php_uname()))
		{
			ob_start();
			system('df');

			$lines = preg_split('/\n/', ob_get_contents());
			ob_end_clean();

			$storageHealth = array();
			foreach($lines as $line)
			{
				$flds = preg_split('/\s+/', $line);
				if(count($flds) == 6)
				{
					$storageHealth[ $flds[0] ] = array(
						'totalSizeHumanReadable' => format_size(1024 * $flds[1]),
						'freeSpaceHumanReadable' => format_size(1024 * $flds[3]),
						'totalBlocks' => $flds[1],
						'usedBlocks' => $flds[2],
						'availableBlocks' => $flds[3],
						'usePct' => $flds[4],
						'mountPoint' => $flds[5]
					);
				}
			}

			// Eat the header row, /dev, etc
			unset($storageHealth['Filesystem']);
			unset($storageHealth['devtmpfs']);
		}
		else
		{
		}

		return $storageHealth;
	}

	// Get Memory Information/Usage
	public function getMemoryInfo()
	{
		$memInfo = array();
		if(preg_match('/.*nux.*/', php_uname()))
		{
			$tmp = file('/proc/meminfo', FILE_IGNORE_NEW_LINES);

			$warningIndicator = 'default';

			foreach($tmp as $line)
			{
				$tmpArray = preg_split('/\s+/', $line);
				if(count($tmpArray) != 3) continue;

				$label = $tmpArray[0];
				$value = $tmpArray[1];
				
				$label = str_replace(':', '', $label);

				$memInfo[ $label ] = array('warningIndicator' => $warningIndicator, 'valueHuman' => format_size($value * 1024), 'value' => $value);
			}

			// These warning indicators are static
			$memInfo['MemTotal']['warningIndicator'] = 'success';

			// If available memory drops below a certain point, set the warning indicator
			if($memInfo['MemAvailable']['value'] / $memInfo['MemTotal']['value'] <= .10)
				$memInfo['MemAvailable']['warningIndicator'] = 'danger';
			else
			if($memInfo['MemAvailable']['value'] / $memInfo['MemTotal']['value'] <= .25)
				$memInfo['MemAvailable']['warningIndicator'] = 'warning';
			else
				$memInfo['MemAvailable']['warningIndicator'] = 'success';

			// If swap starts growing, set the warning indicator
			if($memInfo['SwapFree']['value'] / $memInfo['SwapTotal']['value'] <= .50)
				$memInfo['SwapFree']['warningIndicator'] = 'danger';
			else
			if($memInfo['SwapFree']['value'] / $memInfo['SwapTotal']['value'] <= .25)
				$memInfo['SwapFree']['warningIndicator'] = 'warning';
			else
				$memInfo['SwapFree']['warningIndicator'] = 'success';
		}
		else
		{
			// TODO - Windows
		}

		return $memInfo;
	}

	// Count users active within the defined period.
	public function getUsersOnline()
	{
		// Calculate 15 minutes ago
		$interval = time() - 15*60;

		// First, find all users who have had activity in the last 15 minutes
		$query = $this->entityQuery->get('user');
		$query->condition('access', $interval, '>=');
		$uids = $query->execute();
		$activeUsers = entity_load_multiple('user', $uids);

		// Now, let's get all the session IDs that we have
		$query = $this->connection->select('sessions','s');
		$query->condition('uid', 0, '>');
		$query->fields('s', array('uid', 'sid'));
		$sessionResult = $query->execute()->fetchAllAssoc('uid', \PDO::FETCH_ASSOC);
		$sessionIds = array_keys($sessionResult);

		// Marry them up / filter out the ones that have had no access in the last 15 minutes
		$actualActiveUsers = array();

		foreach($sessionResult as $uid => $sr)
		{
			foreach($activeUsers as $u)
			{
				if($u->uid->value == $uid)
				{
					$u->accessElapsedTime = SiteCommanderUtils::elapsedTime($u->access->value);
					$actualActiveUsers[] = $u;
					break;
				}
			}
		}
		
		return $actualActiveUsers;
	}

	// Get sessioned user list
	public function getSessionedUsers()
	{
		$query = $this->connection->select('sessions','s');
		$query->condition('uid', 0, '>');
		$query->fields('s', array('uid', 'sid'));
		$sessionResult = $query->execute()->fetchAllAssoc('uid', \PDO::FETCH_ASSOC);

		$sessionIds = array_keys($sessionResult);

		$query = $this->entityQuery->get('user');
		$query->condition('uid', $sessionIds, 'IN');
		$uids = $query->execute();

		$users = entity_load_multiple('user', $uids);

		// Build a cohesive result set
		$sessionedUsers = array();

		foreach($sessionResult as $uid => $sr)
		{
			foreach($users as $u)
			{
				if($u->uid->value == $uid)
				{
					$u->accessElapsedTime = SiteCommanderUtils::elapsedTime($u->access->value);
					$sessionedUsers[] = array('u' => $u, 's' => $sr);
					break;
				}
			}
		}

		return $sessionedUsers;
	}

	public function getPublishedNodeCounts() {

		// Figure out if we are excluding certain content types in the configuration
		$excludedContentTypes = $this->configFactory->get('sitecommander.settings')->get('excludedContentTypes');
		$excludedContentTypes = array_filter($excludedContentTypes);

		// Get breakdown of published nodes by content type
		$nodeTypeNames = node_type_get_names();

		$query = $this->entityQuery->getAggregate('node');

		$query->andConditionGroup()
							->condition('type', array_keys($nodeTypeNames), 'IN')
							->condition('status', 1);

		$query->groupBy('type')
					->aggregate('type', 'COUNT')
					->sortAggregate('type', 'COUNT', 'DESC');

		$tmpResult = $query->execute();

		// Put the results in a format that is easier for us to work with, using the node type machine name as the index in the array
		$result = array();
		foreach($tmpResult as $val)
		{
			if(!array_key_exists($val['type'], $excludedContentTypes))
			{
				$result[ $val['type'] ] = $val;
				$result[ $val['type'] ]['name'] = $nodeTypeNames[ $val['type'] ];
			}
		}

		// Add back in the ones that don't have any nodes yet, as the query won't pick those up
		foreach($nodeTypeNames as $machineName => $nodeTypeName)
		{
			if(!in_array($machineName, $excludedContentTypes))
			{
				if(!array_key_exists($machineName, $result))
					$result[] = array('type' => $machineName, 'type_count' => '0', 'name' => $nodeTypeNames[ $machineName ]);
			}
		}

		return $result;
	}

	// Get # of users
	public function getUserCount()
	{
		return $this->entityQuery->get('user')
								->condition('uid', 0, '!=')
								->count()->execute();
	}

	// Get size of install (storage footprint) - currently only works under Linux!
	public function getInstallSize()
	{
		if(preg_match('/.*nux.*/', php_uname()))
		{
			ob_start();
			$tmp = preg_split('/\s+/', system('du -sb'));
			$installSize = format_size($tmp[0]);
			ob_end_clean();
		}
		else
		{
			$installSize = 'Unknown';
		}

		return $installSize;
	}

	// Get size of temporary file storage
	public function getOldFilesStorageSize()
	{
		if(preg_match('/.*nux.*/', php_uname()))
		{
			$publicPath = $this->fileSystem->realpath(file_default_scheme() . "://");

			ob_start();
			$tmp = preg_split('/\s+/', system('du -sbc ' . $publicPath . '/css ' . $publicPath . '/js '));
			ob_end_clean();

			$oldFilesStorageSize = format_size($tmp[ 0 ]);
		}
		else
		{
			$oldFilesStorageSize = 'Unknown';
		}

		return $oldFilesStorageSize;
	}

	public function getBackupStorageSize()
	{
		if(preg_match('/.*nux.*/', php_uname()))
		{
			$config = $this->configFactory->getEditable('sitecommander.settings');
			$backupDir = $config->get('backupDirectory');

			if($backupDir && is_dir($backupDir))
			{
				ob_start();
				$tmp = preg_split('/\s+/', system('du -sbc ' . $backupDir));
				ob_end_clean();

				$backupStorageSize = format_size($tmp[ 0 ]);
			} else {
				$backupStorageSize = 'Unknown';
			}
		}
		else
		{
			$backupStorageSize = 'Unknown';
		}

		return $backupStorageSize;
	}

	// Get number of enabled modules
	public function getEnabledModulesCount()
	{
		return count($this->moduleHandler->getModuleList());
	}

	// Get # of authenticated users online right now (we look at the number of sessions that were last updated within the past 15 minutes)
	public function getNumAuthUsersOnline()
	{
		$query = $this->connection->select('sessions','s');
		$query->addExpression('COUNT( uid )');
		$query->condition('timestamp', strtotime('15 minutes ago'), '>');
		$query->condition('uid', 0, '>');

		return $query->execute()->fetchField();
	}

	// Get total # of session entries in the database
	public function getNumSessionEntries()
	{
		$query = $this->connection->select('sessions','s');
		$query->addExpression('COUNT( uid )');

		return $query->execute()->fetchField();
	}

	// If MailChimp is installed, get all MailChimp lists and total # of subscribers for each
	public function getMailChimpInfo()
	{
		if ($this->moduleHandler->moduleExists('mailchimp'))
			return mailchimp_get_lists();
	}

	// Get top 15 search phrases done today
	public function getTodaysTopSearches()
	{
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

		$topSearches = array();
		foreach ($result as $dblog) {
			$unSerializedData = unserialize($dblog->variables);	
			$topSearches[] = array('searchPhrase' => $unSerializedData['%keys'], 'count' => $dblog->count);
		}

		return $topSearches;
	}

	public function getDatabaseConfig( $dbDriver )
	{
		switch($dbDriver)
		{
			case 'mysql': return $this->getMySqlConfig();
		}

		return array();
	}

	public function getDatabaseStats( $dbDriver )
	{
		switch($dbDriver)
		{
			case 'mysql': return $this->getMySqlStats();
		}

		return array();
	}

	public function getMySqlStats()
	{
		$result = $this->connection->query('SHOW GLOBAL STATUS')->fetchAll();
		$stats = array();
		foreach($result as $r)
			$stats[strtolower($r->Variable_name)] = $r->Value;
		return $stats;
	}

	public function getMySqlConfig()
	{
		$result = $this->connection->query('SHOW VARIABLES')->fetchAll();
		$stats = array();
		foreach($result as $r)
			$stats[strtolower($r->Variable_name)] = $r->Value;
		return $stats;
	}

	public function calculateDbFields( &$drupalInfo )
	{
		switch($drupalInfo['dbDriver'])
		{
			case 'mysql': $drupalInfo['dbVersion'] = $drupalInfo['dbConfig']['version'];
										$drupalInfo['dbName'] = $drupalInfo['dbConfig']['version_comment'];
										$drupalInfo['dbUptime'] = SiteCommanderUtils::formatUptime($drupalInfo['dbStats']['uptime']);
										$drupalInfo['dbTotalQueries'] = SiteCommanderUtils::formatNumber($drupalInfo['dbStats']['questions']);
										$drupalInfo['dbQPS'] = round($drupalInfo['dbStats']['questions'] / $drupalInfo['dbStats']['uptime'], 2);
										$drupalInfo['dbTotalConnections'] = SiteCommanderUtils::formatNumber($drupalInfo['dbStats']['connections']);
										$drupalInfo['dbBytesSent'] = format_size($drupalInfo['dbStats']['bytes_sent']);
										$drupalInfo['dbBytesReceived'] = format_size($drupalInfo['dbStats']['bytes_received']);
										$drupalInfo['dbQueryCacheHitRatio'] = round(($drupalInfo['dbStats']['qcache_hits'] / ($drupalInfo['dbStats']['com_select'] + $drupalInfo['dbStats']['qcache_hits'])) * 100, 2);

										$per_thread_buffers =
											@$drupalInfo['dbConfig']['record_buffer'] +
											@$drupalInfo['dbConfig']['record_rnd_buffer'] +
											@$drupalInfo['dbConfig']['sort_buffer'] +
											@$drupalInfo['dbConfig']['thread_stack'] +
											@$drupalInfo['dbConfig']['join_buffer_size'];

										$total_per_thread_buffers = $per_thread_buffers * $drupalInfo['dbConfig']['max_connections'];
										$max_total_per_thread_buffers = $per_thread_buffers * $drupalInfo['dbStats']['max_used_connections'];

    								$max_tmp_table_size =
      								( $drupalInfo['dbConfig']{'tmp_table_size'} > $drupalInfo['dbConfig']{'max_heap_table_size'} )
      									? $drupalInfo['dbConfig']{'max_heap_table_size'}
      									: $drupalInfo['dbConfig']{'tmp_table_size'};
    								$server_buffers =
      								$drupalInfo['dbConfig']{'key_buffer_size'} + $max_tmp_table_size;
    								$server_buffers +=
      								$drupalInfo['dbConfig']{'innodb_buffer_pool_size'}
      								? $drupalInfo['dbConfig']{'innodb_buffer_pool_size'}
      								: 0;
    								$server_buffers +=
      								$drupalInfo['dbConfig']{'innodb_additional_mem_pool_size'}
      								? $drupalInfo['dbConfig']{'innodb_additional_mem_pool_size'}
      								: 0;
    								$server_buffers +=
      								$drupalInfo['dbConfig']{'innodb_log_buffer_size'}
      								? $drupalInfo['dbConfig']{'innodb_log_buffer_size'}
      								: 0;
    								$server_buffers +=
      								$drupalInfo['dbConfig']{'query_cache_size'} ? $drupalInfo['dbConfig']{'query_cache_size'} : 0;
    								$server_buffers +=
      								@$drupalInfo['dbConfig']{'aria_pagecache_buffer_size'}
      								? @$drupalInfo['dbConfig']{'aria_pagecache_buffer_size'}
      								: 0;

										$max_used_memory = $server_buffers + $max_total_per_thread_buffers + $this->getPerformanceSchemaMemory() + $this->getgCacheMemory();
										$max_peak_memory = $server_buffers + $total_per_thread_buffers + $this->getPerformanceSchemaMemory() + $this->getgCacheMemory();

										$drupalInfo['dbMaxMemoryUsage'] = format_size($max_used_memory);
										$drupalInfo['dbMaxPossibleMemoryUsage'] = format_size($max_peak_memory);
										break;
		}
	}

	// TODO
	public function getgCacheMemory()
	{
		return 0;
	}

	public function getPerformanceSchemaMemory()
	{
		$result = $this->connection->query('SHOW ENGINE PERFORMANCE_SCHEMA STATUS')->fetchAll();

		foreach($result as $r)
			if($r->Name == 'performance_schema.memory')
				return $r->Status;
	
		return 0;
	}

	public function runCron()
	{
		$this->cron->run();

    // Create AJAX Response object.
    $response = new AjaxResponse();

    // Call the SiteCommanderAjaxCommand javascript function.
		$responseData = new \StdClass();
		$responseData->command = 'readMessage';
		$responseData->siteCommanderCommand = 'runCron';
		$responseData->timestamp_cron_last_run = SiteCommanderUtils::elapsedTime( $this->state->get('system.cron_last') );
    $response->addCommand( new ReadMessageCommand($responseData));

		// Return ajax response.
		return $response;
	}

	public function getPusherNumSubscribers( $channelName='site-commander')
	{
		if($this->pusher)
		{
			$info = $this->pusher->getChannelInfo($channelName, array('info' => 'subscription_count'));
			if(isset($info->subscription_count))
				return $info->subscription_count;
		}

		return 1;
	}

	public function getTagCloudData()
	{
		$config = $this->configFactory->get('sitecommander.settings');
		$tagCloudVocabulary = $config->get('tagCloudVocabulary');
		$tagCloudLimit = $config->get('tagCloudLimit');

		$query = $this->connection->select('taxonomy_term_data','td');
		$query->addExpression('COUNT(td.tid)', 'count');
		$query->fields('td', array('tid'));
		$query->fields('tfd', array('name'));

		$query->join('taxonomy_index', 'tn', 'td.tid = tn.tid');
		$query->join('node_field_data', 'n', 'tn.nid = n.nid');
		$query->join('taxonomy_term_field_data', 'tfd', 'tfd.tid = tn.tid');

		$query->condition('td.vid', $tagCloudVocabulary);
		$query->condition('n.status', 1);

		$query->groupBy('td.tid')->groupBy('td.vid')->groupBy('tfd.name');

		$query->having('COUNT(td.tid)>0');
		$query->orderBy('count', 'DESC');
		$query->range(0, $tagCloudLimit);

		$result = $query->execute()->fetchAll();

		$tagCloudData = array();
		foreach($result as $r)
		{
			$term = \Drupal\taxonomy\Entity\Term::load($r->tid);
			$tagCloudData[] = array(
				'name' => $r->name,
				'count' => $r->count,
				'url' => \Drupal::url('entity.taxonomy_term.canonical', ['taxonomy_term' => $term->id()], array('absolute' => TRUE))
			);
		}

		// Sort by name now (case insensitive)
		$customSort = function($a, $b) {
			return strcasecmp($a['name'], $b['name']);
		};

		uasort($tagCloudData, $customSort);

		return $tagCloudData;
	}

}
