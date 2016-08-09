<?php
/**
 * @file
 * Contains \Drupal\sitecommander\Form\ConfigureForm.
 */

namespace Drupal\sitecommander\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure form.
 */
class ConfigureForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
		return 'sitecommander_configure_form';
  }

/** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sitecommander.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

		$config = $this->config('sitecommander.settings');

		// General settings
		$form['general'] = array(
			'#type' => 'fieldset',
			'#title' => t('General Settings'),
			//'#markup' => '<p>' . t('These are general settings.') . '</p>'
		);

				// Get content types so we can choose to exclude certain content types from the dashboard
				$nodeTypeNames = node_type_get_names();

				$config = \Drupal::service('config.factory')->getEditable('sitecommander.settings');
				$excludedContentTypes = $config->get('excludedContentTypes');

				$form['general']['excludedContentTypes'] = array(
				'#type' => 'checkboxes',
				'#title' => t('Exclude Specific Content Types From Dashboard'),
				'#required' => FALSE,
				'#options' => $nodeTypeNames,
				'#default_value' => $excludedContentTypes,
				);

				$form['general']['includeBootstrapCSS'] = array(
    			'#type' => 'checkbox',
    			'#title' => t('Include Bootstrap CSS via CDN'),
    			'#required' => FALSE,
					'#default_value' => $config->get('includeBootstrapCSS'),
					'#description' => 'If your Drupal theme is built around Bootstrap CSS, there is no need to check this. The side effect of it loading twice will be that modals will disappear as soon as they appear, and the module might format strangely - FYI.'
				);

				$form['general']['includejQuery'] = array(
    			'#type' => 'checkbox',
    			'#title' => t('Include jQuery via CDN'),
    			'#required' => FALSE,
					'#default_value' => $config->get('includejQuery'),
					'#description' => 'If your Drupal theme or perhaps a mod already includes jQuery, there is no need to check this. If things like the tabbed interface do not function properly, check this box, clear your cache, and try again - FYI.'
				);

				$form['general']['tagCloudVocabulary'] = array(
    			'#type' => 'textfield',
    			'#title' => t('Name of the taxonomy vocabulary to use in the tag cloud widget'),
    			'#required' => FALSE,
					'#default_value' => $config->get('tagCloudVocabulary') ? $config->get('tagCloudVocabulary') : 'tags'
				);

				$form['general']['refreshRate'] = array(
    			'#type' => 'number',
    			'#title' => t('Dashboard AJAX Refresh Rate (in seconds)'),
    			'#required' => TRUE,
					'#default_value' => $config->get('refreshRate') ? $config->get('refreshRate') : 60
				);

		// Redis settings
		$form['redis'] = array(
			'#type' => 'fieldset',
			'#title' => t('Redis Settings'),
			'#markup' => t('Redis can be used to temporarily track IP addresses of non-authenticated visitors, for reporting visitor counts on the dashboard. If you do not have Redis installed, simply leave the following fields blank.')
			//'#markup' => '<p>' . t('These are general settings.') . '</p>'
		);

				$form['redis']['redisHostName'] = array(
    			'#type' => 'textfield',
    			'#title' => t('Redis Hostname'),
    			'#required' => FALSE,
					'#default_value' => $config->get('redisHostName') ? $config->get('redisHostName') : ''
				);

				$form['redis']['redisPort'] = array(
    			'#type' => 'number',
    			'#title' => t('Redis Port'),
    			'#required' => FALSE,
					'#default_value' => $config->get('redisPort') ? $config->get('redisPort') : 6379
				);

				$form['redis']['redisDatabaseIndex'] = array(
    			'#type' => 'number',
    			'#title' => t('Redis Database Index to Use'),
    			'#required' => FALSE,
					'#default_value' => $config->get('redisDatabaseIndex') ? $config->get('redisDatabaseIndex') : 0,
					'#description' => 'The numeric database index to use - default is database 0. If you do not know what you are doing, leave this alone! When you clear the Redis cache from SiteCommander, this database will get cleared!'
				);

				$form['redis']['redisPort'] = array(
    			'#type' => 'number',
    			'#title' => t('Redis Port'),
    			'#required' => FALSE,
					'#default_value' => $config->get('redisPort') ? $config->get('redisPort') : 6379
				);

		// Anonymous user tracking
		$form['userTracking'] = array(
			'#type' => 'fieldset',
			'#title' => t('Anonymous User Tracking'),
			'#markup' => '<p>' . t('Currently, Redis is required for tracking/reporting of anonymous visitors. If you do not have Redis installed, do not enable this feature!') . '</p>'
		);

				$form['userTracking']['enableAnonymousUserTracking'] = array(
    			'#type' => 'checkbox',
    			'#title' => t('Enable tracking of anonymous users'),
    			'#required' => FALSE,
					'#default_value' => $config->get('enableAnonymousUserTracking')
				);

				$form['userTracking']['visitorIpAddressTTL'] = array(
    			'#type' => 'number',
    			'#title' => t('Timeframe for tracking visitors'),
    			'#required' => FALSE,
					'#default_value' => $config->get('visitorIpAddressTTL') ? $config->get('visitorIpAddressTTL') : 15,
					'#description' => 'How many minutes should SiteCommander look backwards to track non-authenticated user IP addresses? Enter a number, in minutes.'
				);

		// Backup Manager settings
		$form['serverPool'] = array(
			'#type' => 'fieldset',
			'#title' => t('Server Pool'),
			//'#markup' => '<p>' . t('These are general settings.') . '</p>'
		);

				$form['serverPool']['serverPoolList'] = array(
    			'#type' => 'textarea',
    			'#title' => t('List of Drupal Servers in Your App Pool'),
    			'#required' => FALSE,
    			'#rows' => 10,
					'#default_value' => $config->get('serverPoolList') ? $config->get('serverPoolList') : '',
    			'#description' => t('List of hostnames for all Drupal servers in your pool. At a minimum, you should have "localhost" if running a single server setup. If running more than one server, use proper hostnames (not "localhost"). One entry per line.'),
    			'#placeholder' => t('e.g. localhost'),
				);

		// Backup Manager settings
		$form['backupManager'] = array(
			'#type' => 'fieldset',
			'#title' => t('Backup Manager Settings'),
			//'#markup' => '<p>' . t('These are general settings.') . '</p>'
		);

				$form['backupManager']['backupDirectory'] = array(
    			'#type' => 'textfield',
    			'#title' => t('Backup directory'),
    			'#required' => FALSE,
					'#default_value' => $config->get('backupDirectory') ? $config->get('backupDirectory') : '',
    			'#description' => t('The directory to be used for storing archived backup files. We recommend a remote share mounted on a local mountpoint, but regular directories will suffice!'),
    			'#placeholder' => t('e.g. /var/backups'),
				);

				ob_start();
				system('whereis drush');
				$drushAutoFindPath = ob_get_contents();
				ob_end_clean();

				$drushAutoFindPath = str_replace('drush: ', '', $drushAutoFindPath);

				$form['backupManager']['drushPath'] = array(
    			'#type' => 'textfield',
    			'#title' => t('Path to drush'),
    			'#required' => FALSE,
					'#default_value' => $config->get('drushPath') ? $config->get('drushPath') : '',
    			'#description' => t('The full path and filename to the drush command, which is used to perform backups and restores. We have attempted to find it for you: <i>' . $drushAutoFindPath . '</i>'),
    			'#placeholder' => t('e.g. /usr/local/bin/drush'),
				);

				$form['backupManager']['backupMaxAgeInDays'] = array(
    			'#type' => 'number',
    			'#title' => t('Max backup age (in days)'),
    			'#required' => FALSE,
					'#default_value' => $config->get('backupMaxAgeInDays') ? $config->get('backupMaxAgeInDays') : 7,
					'#description' => 'The maximum age for backup files. Backups older than this will be automatically purged.'
				);

				$form['backupManager']['enableScheduledBackups'] = array(
    			'#type' => 'checkbox',
    			'#title' => t('Enable scheduled backups?'),
    			'#required' => FALSE,
					'#default_value' => $config->get('enableScheduledBackups')
				);

				$form['backupManager']['minHoursBetweenBackups'] = array(
    			'#type' => 'number',
    			'#title' => t('Minimum number of hours between backups'),
    			'#required' => FALSE,
					'#default_value' => $config->get('minHoursBetweenBackups') ? $config->get('minHoursBetweenBackups') : 24
				);

				$form['backupManager']['enableMirroring'] = array(
    			'#type' => 'checkbox',
    			'#title' => t('Enable mirroring backup files to a remote host?'),
    			'#required' => FALSE,
					'#default_value' => $config->get('enableMirroring')
				);

				$form['backupManager']['mirrorMode'] = array(
    			'#type' => 'radios',
    			'#title' => t('Interface to use for mirroring backup files to a remote host'),
    			'#required' => FALSE,
					'#options' => array('SFTP' => 'SFTP'),
					'#default_value' => $config->get('mirrorMode')
				);

				$form['backupManager']['remotePort'] = array(
    			'#type' => 'number',
    			'#title' => t('Remote port #'),
    			'#required' => FALSE,
					'#default_value' => $config->get('remotePort') ? $config->get('remotePort') : '',
    			'#description' => t('Generally this is 22 for SFTP or 21 for FTP.'),
				);

				$form['backupManager']['remoteHost'] = array(
    			'#type' => 'textfield',
    			'#title' => t('Remote mirror host'),
    			'#required' => FALSE,
					'#default_value' => $config->get('remoteHost') ? $config->get('remoteHost') : '',
    			'#description' => t('The hostname or IP address of the remote system that will be used as the mirror destination.'),
    			'#placeholder' => t('e.g. hostname.somedomain.com'),
				);

				$form['backupManager']['remoteDir'] = array(
    			'#type' => 'textfield',
    			'#title' => t('Remote directory'),
    			'#required' => FALSE,
					'#default_value' => $config->get('remoteDir') ? $config->get('remoteDir') : '',
    			'#description' => t('The absolute path to the remote destinationdirectory on the mirror host.'),
					'#placeholder' => t('e.g. /some/path')
				);

				$form['backupManager']['remoteUserName'] = array(
    			'#type' => 'textfield',
    			'#title' => t('Remote username'),
    			'#required' => FALSE,
					'#default_value' => $config->get('remoteUserName') ? $config->get('remoteUserName') : '',
    			'#description' => t('The username to be used for logging into the remote host.'),
				);

				$form['backupManager']['remotePassword'] = array(
    			'#type' => 'textfield',
    			'#title' => t('Remote password'),
    			'#required' => FALSE,
					'#default_value' => $config->get('remotePassword') ? $config->get('remotePassword') : '',
    			'#description' => t('The password to be used for logging into the remote host.'),
				);

		// Broadcast Manager settings
		$form['broadcastManager'] = array(
			'#type' => 'fieldset',
			'#title' => t('Broadcast Manager Settings'),
			'#markup' => t('In order to utilize message broadcasting in SiteCommander, you will need to create an account (free) at <a href="http://pusher.com" target="_blank">Pusher.com</a>.')
		);

				$form['broadcastManager']['pusherAppId'] = array(
    			'#type' => 'textfield',
    			'#title' => t('Pusher App ID'),
    			'#required' => FALSE,
					'#default_value' => $config->get('pusherAppId') ? $config->get('pusherAppId') : '',
    			'#description' => t('The Pusher App ID you created at Pusher.com.'),
				);

				$form['broadcastManager']['pusherAppKey'] = array(
    			'#type' => 'textfield',
    			'#title' => t('Pusher App Key'),
    			'#required' => FALSE,
					'#default_value' => $config->get('pusherAppKey') ? $config->get('pusherAppKey') : '',
    			'#description' => t('The Pusher App Key you created at Pusher.com.'),
				);

				$form['broadcastManager']['pusherAppSecret'] = array(
    			'#type' => 'textfield',
    			'#title' => t('Pusher App Secret'),
    			'#required' => FALSE,
					'#default_value' => $config->get('pusherAppSecret') ? $config->get('pusherAppSecret') : '',
    			'#description' => t('The Pusher App Secret you created at Pusher.com.'),
				);

		return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('sitecommander.settings');

		// General settings
		$config->set('excludedContentTypes', $form_state->getValue('excludedContentTypes'))
					->set('includeBootstrapCSS', $form_state->getValue('includeBootstrapCSS'))
					->set('includejQuery', $form_state->getValue('includejQuery'))
					->set('tagCloudVocabulary', $form_state->getValue('tagCloudVocabulary'))
					->set('refreshRate', $form_state->getValue('refreshRate'))

					// Redis settings
					->set('redisHostName', $form_state->getValue('redisHostName'))
					->set('redisPort', $form_state->getValue('redisPort'))
					->set('redisDatabaseIndex', $form_state->getValue('redisDatabaseIndex'))

					// Anonymous user tracking settings
					->set('enableAnonymousUserTracking', $form_state->getValue('enableAnonymousUserTracking'))
					->set('visitorIpAddressTTL', $form_state->getValue('visitorIpAddressTTL'))

					// Backup Manager settings
					->set('backupDirectory', $form_state->getValue('backupDirectory'))
					->set('drushPath', $form_state->getValue('drushPath'))
					->set('backupMaxAgeInDays', $form_state->getValue('backupMaxAgeInDays'))
					->set('enableScheduledBackups', $form_state->getValue('enableScheduledBackups'))
					->set('minHoursBetweenBackups', $form_state->getValue('minHoursBetweenBackups'))
					->set('remoteHost', $form_state->getValue('remoteHost'))
					->set('remotePort', $form_state->getValue('remotePort'))
					->set('remoteUserName', $form_state->getValue('remoteUserName'))
					->set('remotePassword', $form_state->getValue('remotePassword'))
					->set('remoteDir', $form_state->getValue('remoteDir'))
					->set('enableMirroring', $form_state->getValue('enableMirroring'))
					->set('mirrorMode', $form_state->getValue('mirrorMode'))

					// Server Pool
					->set('serverPoolList', $form_state->getValue('serverPoolList'))

					// Broadcast Manager settings
					->set('pusherAppId', $form_state->getValue('pusherAppId'))
					->set('pusherAppKey', $form_state->getValue('pusherAppKey'))
					->set('pusherAppSecret', $form_state->getValue('pusherAppSecret'))

					->save();


		parent::submitForm($form, $form_state);
  }
}
