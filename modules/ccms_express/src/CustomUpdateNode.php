<?php

namespace Drupal\ccms_express;

use Drupal\node\Entity\Node;

class CustomUpdateNode {

  public static function updateNode($nid, &$context){
    $maping_Json = \Drupal::config('ccms_article_author_maping.settings')->get('maping');
    $map = json_decode($maping_Json, TRUE);
    $message = 'Updating Node...';
    $node = Node::load($nid);
    $guest_auth_target_id = $node->get('field_guest_author_name')->getValue()[0]['target_id'];
    if ($guest_auth_target_id) {
      if (array_key_exists($guest_auth_target_id, $map)) {
        $node->set('field_author', $map[$guest_auth_target_id]);
        $node->save();
      }
      else {
        $author_name = Node::load($guest_auth_target_id)->label();
        $author = user_load_by_name($author_name);
        if ($author) {
          $node->set('field_author', $author);
        }
      }
    }

    $node->save();
  }

  function updateContentFinishedCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = t('Updation Processed');
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }
}