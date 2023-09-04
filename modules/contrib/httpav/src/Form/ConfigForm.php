<?php

namespace Drupal\httpav\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ConfigForm extends ConfigFormBase {
  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'httpav.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'httpav_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('If files should be checked'),
      '#default_value' => $config->get('enabled') ?: TRUE,
    ];

    $form['allow_unchecked'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow unchecked'),
      '#description' => $this->t('If the HTTP request fails, assume success.'),
      '#default_value' => $config->get('allow_unchecked') ?: FALSE,
    ];

    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The HTTP endpoint to submit files to'),
      '#description' => $this->t('A fully-qualified URL that can receive files and present a status, can be set with the environment variable HTTPAV_ENDPOINT.'),
      '#default_value' => \Drupal::service('httpav.scanner')->getEndpoint(),
    ];

    $form['api_details'] = [
      '#type' => 'details',
      '#title' => $this->t('API Configuration'),
      '#description' => $this->t('Configure how the file will be sent to your service and how it will respond.'),
      '#tree' => FALSE,
    ];

    $payload_key = $config->get('payload_key') ?: 'malware';

    $form['api_details']['payload_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Payload Key'),
      '#description' => $this->t('The key to use when sending the request payload'),
      '#default_value' => $payload_key,
    ];

    $payload = [
      'headers' => [
        'content-type' => 'multipart/form-data',
      ],
      'multipart' => [
        [
          'name' => $payload_key,
          'contents' => '@file',
          'fileanme' => 'testfile.jpg',
        ],
      ],
    ];

    $form['api_details']['request'] = [
      '#markup' => '<p>Sent request:</p><pre><code>' . json_encode($payload, JSON_PRETTY_PRINT) . '</code></pre>',
    ];

    $return_key = $config->get('return_key') ?: 'results';

    $form['api_details']['return_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Return key'),
      '#description' => $this->t('The return key from the response.'),
      '#default_value' => $return_key,
    ];

    $response = [$return_key => ['infected' => TRUE]];

    $form['api_details']['return_response'] = [
      '#markup' => '<p>Expected response:</p><pre><code>' . json_encode($response, JSON_PRETTY_PRINT) . '</code></pre>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('allow_unchecked', $form_state->getValue('allow_unchecked'))
      ->set('endpoint', $form_state->getValue('endpoint'))
      ->set('payload_key', $form_state->getValue('payload_key'))
      ->set('return_key', $form_state->getValue('return_key'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
