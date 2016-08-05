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
		$form['sitecommander_settings']['general'] = array(
			'#type' => 'fieldset',
			'#title' => t('General Settings'),
			//'#markup' => '<p>' . t('These are general settings.') . '</p>'
		);

				// Get content types so we can choose to exclude certain content types from the dashboard
				$nodeTypeNames = node_type_get_names();

				$config = \Drupal::service('config.factory')->getEditable('sitecommander.settings');
				$excludedContentTypes = $config->get('excludedContentTypes');

				$form['sitecommander_settings']['general']['excludedContentTypes'] = array(
				'#type' => 'checkboxes',
				'#title' => t('Exclude Specific Content Types From Dashboard'),
				'#required' => FALSE,
				'#options' => $nodeTypeNames,
				'#default_value' => $excludedContentTypes,
				);

				$form['sitecommander_settings']['general']['includeBootstrapCSS'] = array(
    			'#type' => 'checkbox',
    			'#title' => t('Include Bootstrap CSS via CDN'),
    			'#required' => FALSE,
					'#default_value' => $config->get('includeBootstrapCSS'),
					'#description' => 'If your Drupal theme is built around Bootstrap CSS, there is no need to check this. The side effect of it loading twice will be that modals will disappear as soon as they appear, and the module might format strangely - FYI.'
				);

				$form['sitecommander_settings']['general']['includejQuery'] = array(
    			'#type' => 'checkbox',
    			'#title' => t('Include jQuery via CDN'),
    			'#required' => FALSE,
					'#default_value' => $config->get('includejQuery'),
					'#description' => 'If your Drupal theme or perhaps a mod already includes jQuery, there is no need to check this. If things like the tabbed interface do not function properly, check this box, clear your cache, and try again - FYI.'
				);

				$form['sitecommander_settings']['general']['refreshRate'] = array(
    			'#type' => 'number',
    			'#title' => t('Dashboard AJAX Refresh Rate (in seconds)'),
    			'#required' => TRUE,
					'#default_value' => $config->get('refreshRate') ? $config->get('refreshRate') : 60
				);

		// Redis settings
		$form['sitecommander_settings']['redis'] = array(
			'#type' => 'fieldset',
			'#title' => t('Redis Settings'),
			'#markup' => t('Redis can be used to temporarily track IP addresses of non-authenticated visitors, for reporting visitor counts on the dashboard.')
			//'#markup' => '<p>' . t('These are general settings.') . '</p>'
		);

				$form['sitecommander_settings']['redis']['redisHostName'] = array(
    			'#type' => 'textfield',
    			'#title' => t('Redis Hostname'),
    			'#required' => FALSE,
					'#default_value' => $config->get('redisHostName') ? $config->get('redisHostName') : ''
				);

				$form['sitecommander_settings']['redis']['redisPort'] = array(
    			'#type' => 'number',
    			'#title' => t('Redis Port'),
    			'#required' => FALSE,
					'#default_value' => $config->get('redisPort') ? $config->get('redisPort') : 6379
				);

				$form['sitecommander_settings']['redis']['redisDatabaseIndex'] = array(
    			'#type' => 'number',
    			'#title' => t('Redis Database Index to Use'),
    			'#required' => FALSE,
					'#default_value' => $config->get('redisDatabaseIndex') ? $config->get('redisDatabaseIndex') : 0,
					'#description' => 'The numeric database index to use - default is database 0. If you do not know what you are doing, leave this alone! When you clear the Redis cache from SiteCommander, this database will get cleared!'
				);

				$form['sitecommander_settings']['redis']['redisPort'] = array(
    			'#type' => 'number',
    			'#title' => t('Redis Port'),
    			'#required' => FALSE,
					'#default_value' => $config->get('redisPort') ? $config->get('redisPort') : 6379
				);

		// Anonymous user tracking
		$form['sitecommander_settings']['userTracking'] = array(
			'#type' => 'fieldset',
			'#title' => t('Anonymous User Tracking'),
			'#markup' => '<p>' . t('Currently, Redis is required for tracking/reporting of anonymous visitors. If you do not have Redis installed, do not enable this feature!') . '</p>'
		);

				$form['sitecommander_settings']['userTracking']['enableAnonymousUserTracking'] = array(
    			'#type' => 'checkbox',
    			'#title' => t('Enable tracking of anonymous users'),
    			'#required' => FALSE,
					'#default_value' => $config->get('enableAnonymousUserTracking')
				);

				$form['sitecommander_settings']['userTracking']['visitorIpAddressTTL'] = array(
    			'#type' => 'number',
    			'#title' => t('Timeframe for tracking visitors'),
    			'#required' => FALSE,
					'#default_value' => $config->get('visitorIpAddressTTL') ? $config->get('visitorIpAddressTTL') : 15,
					'#description' => 'How many minutes should SiteCommander look backwards to track non-authenticated user IP addresses? Enter a number, in minutes.'
				);

		// Backup Manager settings
		$form['sitecommander_settings']['backupManager'] = array(
			'#type' => 'fieldset',
			'#title' => t('Backup Manager Settings'),
			//'#markup' => '<p>' . t('These are general settings.') . '</p>'
		);

				$form['sitecommander_settings']['backupManager']['backupDirectory'] = array(
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

				$form['sitecommander_settings']['backupManager']['drushPath'] = array(
    			'#type' => 'textfield',
    			'#title' => t('Path to drush'),
    			'#required' => FALSE,
					'#default_value' => $config->get('drushPath') ? $config->get('drushPath') : '',
    			'#description' => t('The full path and filename to the drush command, which is used to perform backups and restores. We have attempted to find it for you: <i>' . $drushAutoFindPath . '</i>'),
    			'#placeholder' => t('e.g. /usr/local/bin/drush'),
				);

				$form['sitecommander_settings']['backupManager']['backupMaxAgeInDays'] = array(
    			'#type' => 'number',
    			'#title' => t('Max backup age (in days)'),
    			'#required' => FALSE,
					'#default_value' => $config->get('backupMaxAgeInDays') ? $config->get('backupMaxAgeInDays') : 7,
					'#description' => 'The maximum age for backup files. Backups older than this will be automatically purged.'
				);

				$form['sitecommander_settings']['backupManager']['enableScheduledBackups'] = array(
    			'#type' => 'checkbox',
    			'#title' => t('Enable scheduled backups?'),
    			'#required' => FALSE,
					'#default_value' => $config->get('enableScheduledBackups')
				);

				$form['sitecommander_settings']['backupManager']['minHoursBetweenBackups'] = array(
    			'#type' => 'number',
    			'#title' => t('Minimum number of hours between backups'),
    			'#required' => FALSE,
					'#default_value' => $config->get('minHoursBetweenBackups') ? $config->get('minHoursBetweenBackups') : 24
				);

				$form['sitecommander_settings']['backupManager']['enableMirroring'] = array(
    			'#type' => 'checkbox',
    			'#title' => t('Enable mirroring backup files to a remote host?'),
    			'#required' => FALSE,
					'#default_value' => $config->get('enableMirroring')
				);

				$form['sitecommander_settings']['backupManager']['mirrorMode'] = array(
    			'#type' => 'radios',
    			'#title' => t('Interface to use for mirroring backup files to a remote host'),
    			'#required' => FALSE,
					'#options' => array('SFTP' => 'SFTP'),
					'#default_value' => $config->get('mirrorMode')
				);

				$form['sitecommander_settings']['backupManager']['remotePort'] = array(
    			'#type' => 'number',
    			'#title' => t('Remote port #'),
    			'#required' => FALSE,
					'#default_value' => $config->get('remotePort') ? $config->get('remotePort') : '',
    			'#description' => t('Generally this is 22 for SFTP or 21 for FTP.'),
				);

				$form['sitecommander_settings']['backupManager']['remoteHost'] = array(
    			'#type' => 'textfield',
    			'#title' => t('Remote mirror host'),
    			'#required' => FALSE,
					'#default_value' => $config->get('remoteHost') ? $config->get('remoteHost') : '',
    			'#description' => t('The hostname or IP address of the remote system that will be used as the mirror destination.'),
    			'#placeholder' => t('e.g. hostname.somedomain.com'),
				);

				$form['sitecommander_settings']['backupManager']['remoteDir'] = array(
    			'#type' => 'textfield',
    			'#title' => t('Remote directory'),
    			'#required' => FALSE,
					'#default_value' => $config->get('remoteDir') ? $config->get('remoteDir') : '',
    			'#description' => t('The absolute path to the remote destinationdirectory on the mirror host.'),
					'#placeholder' => t('e.g. /some/path')
				);

				$form['sitecommander_settings']['backupManager']['remoteUserName'] = array(
    			'#type' => 'textfield',
    			'#title' => t('Remote username'),
    			'#required' => FALSE,
					'#default_value' => $config->get('remoteUserName') ? $config->get('remoteUserName') : '',
    			'#description' => t('The username to be used for logging into the remote host.'),
				);

				$form['sitecommander_settings']['backupManager']['remotePassword'] = array(
    			'#type' => 'textfield',
    			'#title' => t('Remote password'),
    			'#required' => FALSE,
					'#default_value' => $config->get('remotePassword') ? $config->get('remotePassword') : '',
    			'#description' => t('The password to be used for logging into the remote host.'),
				);

		// Broadcast Manager settings
		$form['sitecommander_settings']['broadcastManager'] = array(
			'#type' => 'fieldset',
			'#title' => t('Broadcast Manager Settings'),
			'#markup' => t('In development ... stay tuned!')
		);

		return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
		$config = \Drupal::service('config.factory')->getEditable('sitecommander.settings');

		// General settings
		$config->set('excludedContentTypes', $form_state->getValue('excludedContentTypes'))->save();
		$config->set('includeBootstrapCSS', $form_state->getValue('includeBootstrapCSS'))->save();
		$config->set('includejQuery', $form_state->getValue('includejQuery'))->save();
		$config->set('refreshRate', $form_state->getValue('refreshRate'))->save();

		// Redis settings
		$config->set('redisHostName', $form_state->getValue('redisHostName'))->save();
		$config->set('redisPort', $form_state->getValue('redisPort'))->save();
		$config->set('redisDatabaseIndex', $form_state->getValue('redisDatabaseIndex'))->save();

		// Anonymous user tracking settings
		$config->set('enableAnonymousUserTracking', $form_state->getValue('enableAnonymousUserTracking'))->save();
		$config->set('visitorIpAddressTTL', $form_state->getValue('visitorIpAddressTTL'))->save();

		// Backup Manager settings
		$config->set('backupDirectory', $form_state->getValue('backupDirectory'))->save();
		$config->set('drushPath', $form_state->getValue('drushPath'))->save();
		$config->set('backupMaxAgeInDays', $form_state->getValue('backupMaxAgeInDays'))->save();
		$config->set('enableScheduledBackups', $form_state->getValue('enableScheduledBackups'))->save();
		$config->set('minHoursBetweenBackups', $form_state->getValue('minHoursBetweenBackups'))->save();
		$config->set('remoteHost', $form_state->getValue('remoteHost'))->save();
		$config->set('remotePort', $form_state->getValue('remotePort'))->save();
		$config->set('remoteUserName', $form_state->getValue('remoteUserName'))->save();
		$config->set('remotePassword', $form_state->getValue('remotePassword'))->save();
		$config->set('remoteDir', $form_state->getValue('remoteDir'))->save();
		$config->set('enableMirroring', $form_state->getValue('enableMirroring'))->save();
		$config->set('mirrorMode', $form_state->getValue('mirrorMode'))->save();

		// Broadcast Manager settings

		parent::submitForm($form, $form_state);
  }
}
