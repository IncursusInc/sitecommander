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

		$form['refreshRate'] = array(
    	'#type' => 'number',
    	'#title' => t('AJAX Refresh Rate (in seconds)'),
    	'#required' => TRUE,
			'#default_value' => $config->get('refreshRate') ? $config->get('refreshRate') : 60
		);

		$form['redisHostName'] = array(
    	'#type' => 'textfield',
    	'#title' => t('Redis Hostname'),
    	'#required' => FALSE,
			'#default_value' => $config->get('redisHostName') ? $config->get('redisHostName') : ''
		);

		$form['redisPort'] = array(
    	'#type' => 'number',
    	'#title' => t('Redis Port'),
    	'#required' => FALSE,
			'#default_value' => $config->get('redisPort') ? $config->get('redisPort') : 6379
		);

		$form['redisDatabaseIndex'] = array(
    	'#type' => 'number',
    	'#title' => t('Redis Database Index to Use'),
    	'#required' => FALSE,
			'#default_value' => $config->get('redisDatabaseIndex') ? $config->get('redisDatabaseIndex') : 0,
			'#description' => 'The numeric database index to use - default is database 0. If you do not know what you are doing, leave this alone! When you clear the Redis cache from SiteCommander, this is the database that will get cleared!'
		);

		$form['visitorIpAddressTTL'] = array(
    	'#type' => 'number',
    	'#title' => t('Timeframe for tracking visitors'),
    	'#required' => FALSE,
			'#default_value' => $config->get('visitorIpAddressTTL') ? $config->get('visitorIpAddressTTL') : 15,
			'#description' => 'How many minutes should SiteCommander look backwards to track non-authenticated user IP addresses? Enter a number, in minutes. Must use Redis as a caching backend for this to work!'
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
		$config->set('refreshRate', $form_state->getValue('refreshRate'))->save();
		$config->set('redisHostName', $form_state->getValue('redisHostName'))->save();
		$config->set('redisPort', $form_state->getValue('redisPort'))->save();
		$config->set('redisDatabaseIndex', $form_state->getValue('redisDatabaseIndex'))->save();

		parent::submitForm($form, $form_state);
  }
}
