<?php

namespace Drupal\ccms_express\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 */
class CreateExpressUser extends FormBase {

  /**
   *
   */
  public function getFormId() {
    return 'create_user_form';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter Username to be created.'),
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Enter User Password.'),
    ];
    $form['create_user'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create User'),
    ];
    return $form;
  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config_factory = \Drupal::configFactory();
    $ccms_auth_config = $config_factory->getEditable('ccms_express.auth');
    $host = \Drupal::request()->getSchemeAndHttpHost();
    try {
      $client = \Drupal::httpClient();
      $request = $client->post($ccms_auth_config->get('url') . '/user/signup', [
        'headers' => ['origin' => $host],
        'json' => [
          'UserName' => $form_state->getValue('username'),
          'password' => $form_state->getValue('password'),
        ],
      ]);
    }
    catch (RequestException $e) {
      watchdog_exception('ccms_express', $e);
      return FALSE;
    }
  }
}
