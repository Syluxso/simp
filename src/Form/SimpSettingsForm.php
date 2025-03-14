<?php

namespace Drupal\simp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SimpSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simp_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('simp.settings');

    $form['max_requests'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum requests allowed'),
      '#description' => $this->t('The maximum number of requests allowed per IP before flagging as spam.'),
      '#default_value' => $config->get('max_requests') ?? 5,
      '#required' => TRUE,
    ];

    $form['time_window'] = [
      '#type' => 'number',
      '#title' => $this->t('Time window (seconds)'),
      '#description' => $this->t('The time window in seconds to count requests (in seconds).'),
      '#default_value' => $config->get('time_window') ?? 10,
      '#required' => TRUE,
    ];

    $form['cloudflare_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Cloudflare Email'),
      '#default_value' => $config->get('cloudflare_email'),
      '#required' => TRUE,
    ];

    $form['cloudflare_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cloudflare API Key'),
      '#default_value' => $config->get('cloudflare_api_key'),
      '#required' => TRUE,
    ];

    $form['cloudflare_zone_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cloudflare Zone ID'),
      '#default_value' => $config->get('cloudflare_zone_id'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('simp.settings')
      ->set('max_requests', $form_state->getValue('max_requests'))
      ->set('time_window', $form_state->getValue('time_window'))
      ->set('cloudflare_email', $form_state->getValue('cloudflare_email'))
      ->set('cloudflare_api_key', $form_state->getValue('cloudflare_api_key'))
      ->set('cloudflare_zone_id', $form_state->getValue('cloudflare_zone_id'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
