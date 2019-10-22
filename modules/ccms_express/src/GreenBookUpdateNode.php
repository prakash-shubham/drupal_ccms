<?php

namespace Drupal\ccms_express;

use Drupal\node\Entity\Node;

class GreenBookUpdateNode {

  public static function updateNode($nid, &$context){

    $message = 'Updating Node...';
    $node = Node::load($nid);
    $mappings = [ 3850 => 8673,
                  1798 => 8674,
                  1807 => 8675,
                  2560 => 8676,
                  2562 => 8677,
                  2561 => 8678,
                  4890 => 8679,
                  4174 => 8676 ];
    $node = Node::load($nid);
    $updated_cat = [];
    $categories = $node->get('field_article_category')->getValue();
    foreach ($categories as $category) {
      $updated_cat[] = $category['target_id'];
      if (in_array($category['target_id'], array_keys($mappings))) {
        $updated_cat[] = $mappings[$category['target_id']];
      }
    }
    $node->set('field_article_category', $updated_cat);
    try {
      $node->save();
    }
    catch (EntityStorageException $e) {
      print($e->getMessage());
    }
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