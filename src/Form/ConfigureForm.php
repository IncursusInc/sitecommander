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
    			'#description' => t('The directory to be used for storing archived backup files.'),
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
    			'#description' => t('The fill path and filename to the drush command, which is used to perform backups and restores. We have attempted to find it for you: <i>' . $drushAutoFindPath . '</i>'),
    			'#placeholder' => t('e.g. /usr/local/bin/drush'),
				);

				$form['sitecommander_settings']['backupManager']['backupMaxAgeInDays'] = array(
    			'#type' => 'number',
    			'#title' => t('Max backup age (in days)'),
    			'#required' => FALSE,
					'#default_value' => $config->get('backupMaxAgeInDays') ? $config->get('backupMaxAgeInDays') : 7,
					'#description' => 'The maximum age for backup files. Backups older than this will be automatically purged.'
				);

		// Broadcast Manager settings
		$form['sitecommander_settings']['broadcastManager'] = array(
			'#type' => 'fieldset',
			'#title' => t('Broadcast Manager Settings'),
			//'#markup' => '<p>' . t('These are general settings.') . '</p>'
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

		// Broadcast Manager settings

		parent::submitForm($form, $form_state);
  }
}
