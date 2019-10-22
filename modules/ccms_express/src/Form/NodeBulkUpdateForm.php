<?php
/**
 * @file
 * Contains \Drupal\ccms_express\Form\NodeBulkUpdateForm.
 */

namespace Drupal\ccms_express\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\node\Entity\Node;

/**
 * Contribute form.
 */
class NodeBulkUpdateForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
     return 'node_bulk_update_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['content'] = array(
       '#type' => 'select',
       '#title' => t('Select Content Type'),
       '#options' => array(
          'article' => t('Article'),
          'blog' =>  t('Blogs'),
          'magazine' => t('Magazine'),
          'events' => t('Events'),
          'slideshow' => t('Slideshow'),
       ),
       '#default_value' => 'article',
    );
    $form['start_date'] = array(
      '#type' => 'date', 
      '#title' => 'Start Date',  
    );
    $form['end_date'] = array(
      '#type' => 'date', 
      '#title' => 'End Date',  
    );
    $form['categories'] = array(
      '#type' => 'textfield',
      '#title' => t('Provide the Parent Category ID to be updated seperated by commas. Categories in case of GB.'),
    );
    $form['greenbook'] = [
      '#type' => 'checkbox',
      '#title' => t('Check if running Greenbook Migrations.'),
    ];
    $form['get_count'] = [
      '#type' => 'checkbox',
      '#title' => t('Check to get the count of Nodes.'),
    ];
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );
    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('greenbook') == true) {
      $this->processGreenbook($form_state);
    }
    else {
      $start = strtotime($form_state->getValue('start_date'));
      $end = strtotime($form_state->getValue('end_date'));
      $categories = $form_state->getValue('categories');
      $content = $form_state->getValue('content');
      $categories = explode(',', $categories);
      $categories = array_map('trim', $categories);
      $vocabulary_tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('category');

      $childrens = [];
      foreach ($vocabulary_tree as $term_item) {
        if (in_array($term_item->parents[0], $categories)) {
          $childrens[] = $term_item->tid;
        }
      }

      if(!empty($content)){
        $query = \Drupal::entityQuery('node')->condition('type', $content);
        if(!empty($start)){
          $query->condition('created', $start, '>=');
        }
        if(!empty($end)){
          $query->condition('created', $end, '<=');
        }
        $query->condition('status', 1);
        if(!empty($childrens)) {
          $and = $query->andConditionGroup();
          $and->condition('field_article_category', $childrens, 'IN');
          $query->condition($and);
        }
        $nids = $query->execute();
        $batch = [
          'title' => t('Updating Content...'),
          'operations' => [],
          'init_message' => t('Updating'),
          'progress_message' => t('Processed @current out of @total.'),
          'error_message' => t('An error occurred during processing'),
          'finished' => 'Drupal\ccms_express\CustomUpdateNode::updateContentFinishedCallback'
        ];

        foreach ($nids as $key => $nid) {
          $batch['operations'][] = [
            'Drupal\ccms_express\CustomUpdateNode::updateNode',
            [$nid]
          ];
        }

        batch_set($batch);
        drupal_set_message(t('Content has been updated succesfully.'));
      }
    }
  }

  public function processGreenbook(FormStateInterface $form_state) {
    $start = strtotime($form_state->getValue('start_date'));
    $end = strtotime($form_state->getValue('end_date'));
    $categories = $form_state->getValue('categories');
    $content = $form_state->getValue('content');
    $categories = explode(',', $categories);
    $categories = array_map('trim', $categories);

    if(!empty($content)){
      $query = \Drupal::entityQuery('node')->condition('type', $content);
      if(!empty($start)){
        $query->condition('created', $start, '>=');
      }
      if(!empty($end)){
        $query->condition('created', $end, '<=');
      }
      $query->condition('status', 1);
      if(!empty($categories)) {
        $and = $query->andConditionGroup();
        $and->condition('field_article_category', $categories, 'IN');
        $query->condition($and);
      }
      $nids = $query->execute();

      if ($form_state->getValue('get_count') == true) {
        drupal_set_message('Count is ' . count($nids)) ;
        drupal_set_message(print_r($nids, 1));
      }
      else {
        $batch = [
          'title' => t('Updating Content...'),
          'operations' => [],
          'init_message' => t('Updating'),
          'progress_message' => t('Processed @current out of @total.'),
          'error_message' => t('An error occurred during processing'),
          'finished' => 'Drupal\ccms_express\GreenBookUpdateNode::updateContentFinishedCallback'
        ];

        foreach ($nids as $key => $nid) {
          $batch['operations'][] = [
            'Drupal\ccms_express\GreenBookUpdateNode::updateNode',
            [$nid]
          ];
        }

        batch_set($batch);
        drupal_set_message(t('Content has been updated succesfully.'));
      }
    }
  }
}
