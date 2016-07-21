<?php

namespace Drupal\drupalstat\Plugin\Block;

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\drupalstat\DrupalStatUtils;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

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

	protected $connection;

	public function __construct() {
		$this->connection = \Drupal::database();
	}

	public function formatMessage($row) {
		// Check for required properties.
		if (isset($row->message) && isset($row->variables)) {

			// Messages without variables or user specified text.
			if ($row->variables === 'N;') {
				$message = Xss::filterAdmin($row->message);
			}

			// Message to translate with injected variables.
			else {
				$message = $this->t(Xss::filterAdmin($row->message), unserialize($row->variables));
			}
		}
		else {
			$message = FALSE;
		}
	
		return $message;
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

		// Get size of temporary file storage
		if(preg_match('/.*nux.*/', php_uname()))
		{
			$publicPath = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");

			ob_start();
			$tmp = preg_split('/\s+/', system('du -shc ' . $publicPath . '/css ' . $publicPath . '/js '));
			ob_end_clean();

			$drupalInfo['oldFilesStorageSize'] = $tmp[ 0 ];
		}
		else
		{
			$drupalInfo['oldFilesStorageSize'] = 'Unknown';
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

		// Get # of authenticated users online right now (we look at the number of sessions that were last updated within the past 15 minutes)
		$query = $this->connection->select('sessions','s');
		$query->addExpression('COUNT( uid )');
		$query->condition('timestamp', strtotime('15 minutes ago'), '>');

		$drupalInfo['numAuthUsersOnline'] = $query->execute()->fetchField();

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
