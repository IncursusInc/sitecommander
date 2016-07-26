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
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\drupalstat\Ajax\ReadMessageCommand;
use Drupal\drupalstat\DrupalStatUtils;

class DrupalStatController extends ControllerBase {

	protected $connection;
	protected $state;
	protected $fileSystem;
	protected $currentUser;
	protected $configFactory;

	public function __construct( Connection $connection, StateInterface $state, FileSystem $fileSystem, AccountInterface $account, ConfigFactory $configFactory ) {
		$this->connection = $connection;
		$this->state = $state;
		$this->fileSystem = $fileSystem;
		$this->currentUser = $account;
		$this->configFactory = $configFactory;
	}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('state'),
      $container->get('file_system'),
      $container->get('current_user'),
      $container->get('config.factory')
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

	// Clear PHP OpCache
	public function clearPhpOpCache()
	{
		opcache_reset();

    // Create AJAX Response object.
    $response = new AjaxResponse();

    // Call the DrupalStatAjaxCommand javascript function.
		$responseData->command = 'readMessage';
		$responseData->drupalStatCommand = 'clearPhpOpCache';
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

    // Call the DrupalStatAjaxCommand javascript function.
		$responseData->command = 'readMessage';
		$responseData->drupalStatCommand = 'clearApcOpCache';
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

    // Call the DrupalStatAjaxCommand javascript function.
		$responseData->command = 'readMessage';
		$responseData->drupalStatCommand = 'purgeSessions';
		$responseData->newNumSessionEntries = $newNumSessionEntries;
    $response->addCommand( new ReadMessageCommand($responseData));

		// Return ajax response.
		return $response;
	}

	public function updateGauges()
	{
		$drupalInfo['loadAverage'] = \Drupal\drupalstat\Controller\DrupalStatController::getCpuLoadAverage();
		$drupalInfo['redisStats'] = \Drupal\drupalstat\Controller\DrupalStatController::getRedisStats();
		$drupalInfo['opCacheStats'] = \Drupal\drupalstat\Controller\DrupalStatController::getOpCacheStats();
		$drupalInfo['apcStats'] = \Drupal\drupalstat\Controller\DrupalStatController::getApcStats();

    // Create AJAX Response object.
    $response = new AjaxResponse();

    // Call the DrupalStatAjaxCommand javascript function.
		$responseData->command = 'readMessage';
		$responseData->drupalStatCommand = 'updateGauges';
		$responseData->payload = $drupalInfo;
    $response->addCommand( new ReadMessageCommand($responseData));

		// Return ajax response.
		return $response;
	}

	// Get Redis stats if they are using it as a caching backend
	public static function getRedisStats()
	{
		$redisHostName = \Drupal::config('drupalstat.settings')->get('redisHostName');
		$redisPort = \Drupal::config('drupalstat.settings')->get('redisPort');

		if (class_exists('Redis') && $redisHostName && $redisPort) {

			$redis = new \Redis();

			$redis->connect($redisHostName, $redisPort);

			// Do not allow PhpRedis serialize itself data, we are going to do it
			// ourself. This will ensure less memory footprint on Redis size when
			// we will attempt to store small values.
			$redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);

			$redisInfo = $redis->info();
			$redisConfig = $redis->config('GET', '*');

			// Keyspace hits/misses gauge
			$redisStats = array();
			$redisStats['keyspaceHits'] = $redisInfo['keyspace_hits'];
			$redisStats['keyspaceMisses'] = $redisInfo['keyspace_misses'];
			$redisStats['keyspaceTotal'] = $redisInfo['keyspace_hits'] + $redisInfo['keyspace_misses'];
			$redisStats['keyspaceMissPct'] = round( $redisInfo['keyspace_misses'] / $redisStats['keyspaceTotal'], 2) * 100 . '%';
			$redisStats['keyspaceHitPct'] = round( $redisInfo['keyspace_hits'] / $redisStats['keyspaceTotal'], 2) * 100 . '%';

			// Memory Usage Gauge
			$redisStats['memoryAllocatedByRedis'] = round($redisConfig['maxmemory'] / pow(1024, 2), 4);
			$redisStats['memoryUsedByRedis'] = round($redisInfo['used_memory'] / pow(1024, 2), 4);

			// Peak Memory Usage Gauge
			$redisStats['peakMemoryAllocatedByRedis'] = round($redisConfig['maxmemory'] / pow(1024, 2), 4);
			$redisStats['peakMemoryUsedByRedis'] = round($redisInfo['used_memory_peak'] / pow(1024, 2), 4);

			// Number of cached objects
			list($keys, $rest) = preg_split('/,/', $redisInfo['db0']);
			list($keys, $numObjects) = preg_split('/=/', $keys);

			// Format I/O stats
			$redisInfo['total_net_input_bytes'] = format_size($redisInfo['total_net_input_bytes']);
			$redisInfo['total_net_output_bytes'] = format_size($redisInfo['total_net_output_bytes']);

			$redisStats['numObjectsCached'] = $numObjects;

			$redisInfo = array_merge($redisStats, $redisInfo);

			return $redisInfo;
		}
	}

	// Get APC stats if they are using it
	public static function getApcStats()
	{
		if (function_exists('apc_cache_info')) {
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
	public static function getOpCacheStats()
	{
		if (function_exists('opcache_get_status')) {
			$stats = opcache_get_status(false);

			$stats['memory_usage']['usedMemory'] = round($stats['memory_usage']['used_memory'] / pow(1024, 2), 4);
			$stats['memory_usage']['freeMemory'] = round($stats['memory_usage']['free_memory'] / pow(1024, 2), 4);

			return $stats;
		}
	}

	public static function getCpuLoadAverage()
	{
		// Get CPU load average
		if(preg_match('/.*nux.*/', php_uname()))
		{
			// Get # of CPU cores
			$numCPUs = DrupalStatUtils::getNumCPUs();

			ob_start();
			$tmp = preg_split('/\s+/', system('cat /proc/loadavg'));
			$loadAverage = array($tmp[0]/$numCPUs, $tmp[1]/$numCPUs, $tmp[2]/$numCPUs);
			ob_end_clean();
		}
		else
		{
			$loadAverage = array(0, 0, 0);
		}

		return $loadAverage;
	}
}
