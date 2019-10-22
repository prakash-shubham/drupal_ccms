<?php

namespace Drupal\ccms_express\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 *
 */
class ExpressLogin extends ControllerBase {

  /**
   *
   */
  public function authenticate() {
    $config_factory = \Drupal::configFactory();
    $ccms_auth_config = $config_factory->getEditable('ccms_express.auth');

    $expire = $ccms_auth_config->get('expire');

    $userCurrent = \Drupal::currentUser()->getUsername();
    try {
      $client = \Drupal::httpClient();
      $request = $client->post($ccms_auth_config->get('url') . '/user/authenticate', [
        'json' => [
          'UserName' => $ccms_auth_config->get('username'),
          'password' => $ccms_auth_config->get('password'),
        ],
      ]);
    }
    catch (RequestException $e) {
      watchdog_exception('ccms_express', $e);
      return [
        '#markup' => "<p class='messages messages--error'>Can't Connect to Express App at this moment. Please try again later.</p>",
        '#allowed_tags' => ['p'],
      ];
    }
    catch (ConnectException $e) {
      watchdog_exception('ccms_express', $e);
      return [
        '#markup' => "<p class='messages messages--error'>Can't Connect to Express App at this moment. Please try again later.</p>",
        '#allowed_tags' => ['p'],
      ];
    }

    $response = json_decode($request->getBody());
    $url = $ccms_auth_config->get('dashboard_url') . '?token=' . $response->token;
    return new TrustedRedirectResponse($url);
  }

}
