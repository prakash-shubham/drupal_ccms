<?php
/**
 * @file
 * Contains \Drupal\ccms_express\Controller\GetArticleNode
 */

namespace Drupal\ccms_express\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use \Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermStorage;
use Drupal\brightcove\Entity\BrightcoveVideo;
use Drupal\Component\Serialization\Json;

class GetArticleNode extends ControllerBase
{
    /**
     *
     */
    public function getarticle($nid)
    {   
        $serializer = \Drupal::service('serializer');
        $data = [];
        $output = [];

        $node = Node::load($nid);
        $owner = $node->getOwner();

        $type = $node->getType();
        if($type == 'slideshow'){
          $paragraph = $node->get('field_slide')->referencedEntities();
          $slides = json_decode($serializer->serialize($paragraph, 'json'));
          foreach ($slides as $key => $slide) {
            $image_media_object = \Drupal::entityTypeManager()
              ->getStorage('media')
              ->load($slide->field_image[0]->target_id);
            $image_data = $serializer->serialize($image_media_object, 'json');
            $image_media_object = json_decode($image_data);
            $slides[$key]->field_image[0]->object = $image_media_object;
          }
        }

        $data = $serializer->serialize($node, 'json');
        $node = json_decode($data);
        $node->field_slide = $slides;

        //Adding the author object to the uid.
        $owner = $serializer->serialize($owner, 'json');
        $owner = json_decode($owner);
        $node->uid[0]->object = $owner;
        //Author Field data for Articles.
        $field_author_id = $node->field_author[0]->target_id;
        $field_author = \Drupal\user\Entity\User::load($field_author_id);
        $field_author = $serializer->serialize($field_author, 'json');
        $field_author = json_decode($field_author);
        $node->field_author[0]->object = $field_author;
        //Array for all the image fields in the Article.
        $imagefields = ['field_featured_image', 'field_image_ccms', 'field_teaser_image', 'field_magazine_image'];

        $taxonomyfields = ['field_agweb_category', 'field_article_category', 'field_category', 'field_brand_promote_to_agweb', 'field_brand'];

        foreach ($taxonomyfields as $field) {
          # code...
            if(isset($node->$field[0])){
              foreach ($node->$field as $key => $term) {
                # code...
                $taxonomy_object = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->target_id);
                $taxonomy_data = $serializer->serialize($taxonomy_object, 'json');
                $taxonomy_object = json_decode($taxonomy_data);
                //Attaching parent information in taxonomies.
                $parent = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($taxonomy_object->parent[0]->target_id);
                $parent_data = $serializer->serialize($parent, 'json');
                $parent_object = json_decode($parent_data);
                $parent_object->taxonomy_parent = $parent_object->parent;
                unset($parent_object->parent);
                $taxonomy_object->parent[0]->object = $parent_object;
                $taxonomy_object->taxonomy_parent = $taxonomy_object->parent;
                unset($taxonomy_object->parent);
                $node->$field[$key]->object = $taxonomy_object;
              }
          }
        }

        if($type == 'slideshow'){
          $node->field_article_category = $node->field_category;
          unset($node->field_category);
          unset($node->field_slides);
        }

        //Loads all imageobjects and adds it to the node object.
        foreach ($imagefields as $field) {
          # code...
            if(isset($node->$field[0])){
              $image_media_object = \Drupal::entityTypeManager()
              ->getStorage('media')
              ->load($node->$field[0]->target_id);
              $image_data = $serializer->serialize($image_media_object, 'json');
              $image_media_object = json_decode($image_data);
              $node->$field[0]->object = $image_media_object;
            }
        }
        //Extracts Inline images from the bodu field.
        $a = "/<img\s+[^>]*src=\"([^\"]*)\"[^>]*>/";
        preg_match_all($a, $node->body[0]->value, $inline_images);
        $inline_image_url = array();
        foreach ($inline_images[1] as $inline_image) {
            $inline_image_url[] = \Drupal::request()->getHttpHost() . $inline_image;
        }
        // Extracts Inline Files from the body.
        $regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>";
        preg_match_all("/$regexp/siU", $node->body[0]->value, $inline_files);
        $inline_file_url = array();
        foreach ($inline_files[2] as $inline_file) {
            if (strpos($inline_file, 'pdf') !== false) {
                $inline_file_url[] = \Drupal::request()->getHttpHost() . $inline_file;
            }
        }
        //A
        if(isset($inline_image_url)){
          $node->body[0]->inline_image = $inline_image_url;
        }
        if(isset($inline_file)){
          $node->body[0]->inline_file = $inline_file_url;
        }
        
        //Include the Brightcove Video Objects
        if (is_array($node->field_brightcove_video)) {
          foreach ($node->field_brightcove_video as $index => $vid) {
            $video_data = $this->getVideo($vid->target_id);
            $node->field_brightcove_video[$index]->object = $video_data;
          }
        }
        //print_r(json_encode($node)); die;
        return $node;
    }

    public function getVideo($target_id){
      $video_load = BrightcoveVideo::load($target_id);
      $poster_image_url = NULL;
      if ($video_load) {
        if (!empty($video_load->get('poster')->target_id)) {
          $poster_file = File::load($video_load->get('poster')->target_id);
          $poster_image_url = file_create_url($poster_file->get('uri')->value);
        }
        $thumbnail_image_url = NULL;
        if (!empty($video_load->get('thumbnail')->target_id)) {
          $thumbnail_file = File::load($video_load->get('thumbnail')->target_id);
          $thumbnail_image_url = file_create_url($thumbnail_file->get('uri')->value);
        }
        $term_name = [];
        if ($video_load->get('tags')->getValue()) {
          foreach ($video_load->get('tags')->getValue() as $term_data) {
            $bc_term = Term::load($term_data['target_id']);
            if (!empty($bc_term)) {
              $term_name[] = $bc_term->getName();
            }
          }
        }
        $video_data = [
          'bcvid' => $video_load->id(),
          'created' => $video_load->get('created')->value,
          'changed' => $video_load->get('changed')->value,
          'langcode' => $video_load->get('langcode')->value,
          'api_client' => $video_load->get('api_client')->target_id,
          'player' => $video_load->get('player')->target_id,
          'status' => $video_load->get('status')->value,
          'name' => $video_load->get('name')->value,
          'video_id' => $video_load->get('video_id')->value,
          'duration' => $video_load->get('duration')->value,
          'description' => $video_load->get('description')->value,
          'related_link' => $video_load->get('related_link')->uri,
          'reference_id' => $video_load->get('reference_id')->value,
          'long_description' => $video_load->get('long_description')->value,
          'economics' => $video_load->get('economics')->value,
          'custom_field_values' => $video_load->get('custom_field_values')->getValue(),
          'poster' => $poster_image_url,
          'thumbnail' => $thumbnail_image_url,
          'schedule_starts_at' => $video_load->get('schedule_starts_at')->value,
          'schedule_ends_at' => $video_load->get('schedule_ends_at')->value,
          'uid' => $video_load->get('uid')->target_id,
          'tags' => $term_name,
        ];
      }
      return $video_data;
    }
}
