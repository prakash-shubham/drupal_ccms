<?php

namespace Drupal\ccms_express\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;

/**
 *
 */
class PopulateCategories extends ControllerBase {

  /**
   *
   */
  public function populate($vocabulary) {
    $config_factory = \Drupal::configFactory();
    $ccms_auth_config = $config_factory->getEditable('ccms_express.auth');

    $vocabulary_tree = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree($vocabulary);
    $term = [];

    $sessionToken = new GetSessionToken();
    $token = $sessionToken->gettoken();

    foreach ($vocabulary_tree as $term_item) {
      $current_time = strtotime('-15 min');
      if ($current_time < $term_item->changed) {
        $updated = TRUE;
      }
      else {
        $updated = FALSE;
      }
      $term['depth'] = $term_item->depth;
      $term['tid'] = $term_item->tid;
      $term['vid'] = $term_item->vid;
      $term['name'] = $term_item->name;
      $term['description_value'] = $term_item->description__value;
      $term['description_format'] = $term_item->description__format;
      $term['changed'] = $term_item->changed;
      $term['parent'] = $term_item->parents;
      $term['updated'] = $updated;
      try {
        $client = \Drupal::httpClient();
        $request = $client->post($ccms_auth_config->get('url') . '/' . $vocabulary, [
          'headers' => ['x-access-token' => $token],
          'json' => $term,
        ]);
      }
      catch (RequestException $e) {
        watchdog_exception('ccms_express', $e);
      }
      catch (ConnectException $e) {
        watchdog_exception('ccms_express', $e);
      }
    }
    return $term;

  }

}
