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

		$form['refreshRateLoadAverage'] = array(
    	'#type' => 'number',
    	'#title' => t('CPU Load Average AJAX Refresh Rate (in seconds)'),
    	'#required' => TRUE,
			'#default_value' => $config->get('refreshRateLoadAverage') ? $config->get('refreshRateLoadAverage') : 60
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

		parent::submitForm($form, $form_state);
  }
}
?>
