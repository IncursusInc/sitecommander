<?php
/**
 * @file
 * Contains \Drupal\drupalstat\Form\ConfigureForm.
 */

namespace Drupal\drupalstat\Form;

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
		return 'drupalstat_configure_form';
  }

/** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'drupalstat.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

		$config = $this->config('drupalstat.settings');

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

		$form['visitorIpAddressTTL'] = array(
    	'#type' => 'number',
    	'#title' => t('Timeframe for tracking visitors'),
    	'#required' => FALSE,
			'#default_value' => $config->get('visitorIpAddressTTL') ? $config->get('visitorIpAddressTTL') : 15,
			'#description' => 'How many minutes should DrupalStat look backwards to track non-authenticated user IP addresses? Enter a number, in minutes. Must use Redis as a caching backend for this to work!'
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
		$config = \Drupal::service('config.factory')->getEditable('drupalstat.settings');
		$config->set('refreshRateLoadAverage', $form_state->getValue('refreshRateLoadAverage'))->save();
		$config->set('redisHostName', $form_state->getValue('redisHostName'))->save();
		$config->set('redisPort', $form_state->getValue('redisPort'))->save();

		parent::submitForm($form, $form_state);
  }
}
?>
