<?php

namespace Drupal\drupalstat\Plugin\Block;

use Drupal\Core\Url;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\drupalstat\DrupalStatUtils;

/**
 * Provides a DrupalStat Block
 *
 * @Block(
 *   id = "drupalstat_block",
 *   admin_label = @Translation("DrupalStat Block"),
 * )
 */
class DrupalStatBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */

	public function formatBytes($size, $precision = 2)
	{
		$base = log($size, 1024);
		$suffixes = array('', 'K', 'M', 'G', 'T');   

		return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
	}

  public function build() {

		// TODO - split this shit up into subfunctions

		$drupalInfo = array();

		// Get breakdown of published nodes by content type
		$drupalInfo['nodeTypeNames'] = node_type_get_names();

		$query = \Drupal::entityQueryAggregate('node')
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
		$drupalInfo['userCount'] = \Drupal::entityQuery('user')->count()->execute();

		// Get size of install (storage footprint) - currently only works under Linux!
		if(preg_match('/.*nux.*/', php_uname()))
		{
			ob_start();
			$tmp = preg_split('/\s+/', system('du -sh'));
			$drupalInfo['installSize'] = $tmp[0];
			ob_end_clean();
		}
		else
		{
			$drupalInfo['installSize'] = 'Unknown';
		}

		// Get CPU load average
		if(preg_match('/.*nux.*/', php_uname()))
		{
			ob_start();
			$tmp = preg_split('/\s+/', system('cat /proc/loadavg'));
			$drupalInfo['loadAverage'] = array($tmp[0], $tmp[1], $tmp[2]);
			ob_end_clean();
		}
		else
		{
			$drupalInfo['loadAverage'] = array(0, 0, 0);
		}

		// Get number of enabled modules
		$drupalInfo['enabledModulesCount'] = count(\Drupal::moduleHandler()->getModuleList());

		// Drupal settings
		$drupalInfo['settings'] = array();
		$drupalInfo['settings']['system']['site'] = \Drupal::config('system.site')->get();
		$drupalInfo['settings']['theme'] = \Drupal::config('system.theme')->get();

		// Cron info
		$drupalInfo['cron']['cron_key'] = \Drupal::state()->get('system.cron_key');
		$drupalInfo['cron']['cron_last'] = DrupalStatUtils::elapsedTime(\Drupal::state()->get('system.cron_last'));

		// Last time Drupal/Modules were checked for updates
		$drupalInfo['update_last_check'] = DrupalStatUtils::elapsedTime(\Drupal::state()->get('update.last_check'));
		// The line below will send the admin user back to the status page, which may not be desirable
		//$destination = \Drupal::destination('/admin/reports/updates')->getAsArray();
		$destination = array('destination' => '/admin/reports/updates');
    $drupalInfo['updateCheckURL'] = \Drupal::url('update.manual_status', [], ['query' => $destination]);

		// Maintenance mode status
		$drupalInfo['maintenance_mode'] = \Drupal::state()->get('system.maintenance_mode') ? 'On' : 'Off';

		// Get timestamp of last cache rebuild
		$timestamp = \Drupal::state()->get('drupalstat.timestamp_cache_last_rebuild');
		if(!$timestamp)
			 $drupalInfo['timestamp_cache_last_rebuild'] = 'Unknown';
		else
			 $drupalInfo['timestamp_cache_last_rebuild'] = DrupalStatUtils::elapsedTime($timestamp);

		// If MailChimp is installed, get all MailChimp lists and total # of subscribers for each
		if (\Drupal::moduleHandler()->moduleExists('mailchimp'))
		{
			$mcConfig = \Drupal::config('mailchimp.settings')->get();
			$mcApiKey = $mcConfig['api_key'];
			$mcClassName = $mcConfig['api_classname'];

			$mcLists = mailchimp_get_lists();

			//echo '<pre>'; print_r($mcLists); exit;
			$drupalInfo['mailchimp'] = $mcLists;
		}

		// TODO: Get some PHP config info?

    return array(
			'#theme' => 'drupalstat',
			'#attached' => array(
				'library' =>  array(
					'drupalstat/drupalstat'
				),
				'drupalSettings' => $drupalInfo
			),
			'#drupalInfo' => $drupalInfo,
			'#cache' => [ 'max-age' => 0, ],
    );
  }
}
?>
