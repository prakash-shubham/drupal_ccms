<?php
/**
 * @file
 * Contains \Drupal\ccms_express\Controller\SanityChecker
 */

namespace Drupal\ccms_express\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;
use Drupal\ccms_express\Controller\SendExpressRequest;
use Drupal\node\Entity\Node;


class SanityChecker extends ControllerBase
{
  /**
   *
   */
  public function returnNids()
  {  
    $allNids = []; 
    $contentTypes = [ 'article', 'events', 'slideshow', 'magazine', 'blog' ]; 
    foreach ($contentTypes as $type) {
      $nids = $this->returnContentTypeNids($type);
      if(!empty($nids)){
        $allNids[$type] = $nids;
      }
    }
    return $allNids;
  }

  /**
   *
   */
  public function returnContentTypeNids($type)
  {   
    $entityQuery = \Drupal::entityQuery('node');
    $entityQuery->condition('type', $type);
    $entityQuery->condition('changed', strtotime('-60 min'), '>=');
    $entityQuery->condition('status', 1);
    //List of Nids Updated in last 60 mins.
    $nids = $entityQuery->execute();
 
    $data = [];
    foreach ($nids as $nid) {
      //Check if Nid exist on express app.
      $node = Node::load($nid);
      $data[$nid] = ($node->changed->value)*1000;
    }
    return $data;
  }
}


