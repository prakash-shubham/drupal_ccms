<?php

namespace Drupal\ccms_express\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ccms_express\Controller\GetSessionToken;

/**
 * Class CCMSSettingsForm.
 *
 * @package Drupal\ccms_express\Form
 */
class CCMSAuthenticationForm extends ConfigFormBase {

  /**
   * CCMSSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ccms_express_auth_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ccms_express.auth'];
  }

  /**
   * Configuration form for connection with Express App.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ccms_express.auth');

    drupal_get_messages($type = NULL, $clear_queue = TRUE);

    if ($config->get('token') != NULL) {
      drupal_set_message(t('CCMS is connected to the Express App.'));
    }
    else {
      drupal_set_message(t('CCMS connection NOT valid.'), 'error');
    }

    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CCMS Express URL (Backend Node App).'),
      '#default_value' => $config->get('url'),
    ];
    $form['dashboard_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CCMS Dashboard URL.'),
      '#default_value' => $config->get('dashboard_url'),
    ];
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter CCMS Express Username.'),
      '#default_value' => $config->get('username'),
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Enter CCMS Express Password.'),
      '#description' => 'You need not enter the password, if you have entered it before.',
    ];
    $form['ccms_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CCMS 1.0 URL.'),
      '#default_value' => $config->get('ccms_url'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save configurations.
    $config = $this->config('ccms_express.auth')
      ->set('url', $form_state->getValue('url'))
      ->set('username', $form_state->getValue('username'))
      ->set('dashboard_url', $form_state->getValue('dashboard_url'))
      ->set('ccms_url', $form_state->getValue('ccms_url'));
    if ($form_state->getValue('password') != NULL) {
      $this->config('ccms_express.auth')->set('password', $form_state->getValue('password'));
    }
    $this->config('ccms_express.auth')->set('token', '');
    $this->config('ccms_express.auth')->set('expire', '');
    $config->save();
    $sessionToken = new GetSessionToken();
    $token = $sessionToken->gettoken();
  }

}
