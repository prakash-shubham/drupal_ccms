<?php

namespace Drupal\ccms_express\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;

/**
 *
 */
class SendExpressRequest extends ControllerBase {

  /**
   *
   */
  public function sendRequest($nid, $method) {

    $sessionToken = new GetSessionToken();
    $token = $sessionToken->gettoken();
    $config_factory = \Drupal::configFactory();
    $ccms_auth_config = $config_factory->getEditable('ccms_express.auth');

    $article = new GetArticleNode();
    $data = $article->getarticle($nid);
    try {
      $client = \Drupal::httpClient();
      $request = $client->$method($ccms_auth_config->get('url') . '/ccms/' . $data->type[0]->target_id, [
        'headers' => ['x-access-token' => $token],
        'json' => $data,
      ]);
    }
    catch (RequestException $e) {
      watchdog_exception('ccms_express', $e);
    }
    catch (ConnectException $e) {
      watchdog_exception('ccms_express', $e);
    }
    return $data;
  }

}
