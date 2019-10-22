<?php

namespace Drupal\ccms_express\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;

/**
 *
 */
class GetSessionToken extends ControllerBase {

  /**
   *
   */
  public function gettoken() {
    $config_factory = \Drupal::configFactory();
    $ccms_auth_config = $config_factory->getEditable('ccms_express.auth');

    $expire = $ccms_auth_config->get('expire');

    if (empty($ccms_auth_config->get('expire')) || $expire <= strtotime('now')) {
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
        return FALSE;
      }
      catch (ConnectException $e) {
        watchdog_exception('ccms_express', $e);
        return FALSE;
      }
      $response = json_decode($request->getBody());
      $now = strtotime('now');

      $ccms_auth_config->set('token', $response->token);
      $ccms_auth_config->set('expire', strtotime('+1 day', $now));
      $ccms_auth_config->save();

      $output = $response->token;
    }
    else {
      $output = $ccms_auth_config->get('token');
    }
    return $output;
  }

}
