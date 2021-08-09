<?php


namespace Drupal\eshipyard_custom_changes\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

class Sync extends ControllerBase {

  public function sync_empty_cradle_yacht(){

    $nids = \Drupal::entityQuery('node')
      ->condition('type','yacht')
      ->condition('field_yacht_position',0)
      ->condition('field_entity_ref_cradle','','<>')
      ->execute();
    $nodes =  \Drupal\node\Entity\Node::loadMultiple($nids);

    $message = '';
    foreach ($nodes as $yacht){
      $cradle_id = $yacht->get('field_entity_ref_cradle')->target_id;
      $cradle = Node::load($cradle_id);
      if(!empty($cradle)) {
        if(!isset($cradle->get('field_cradle_yacht')->target_id)) {
          $cradle->get('field_cradle_status')->value = 0;
          $cradle->get('field_cradle_yacht')->target_id = $yacht->id();
          $cradle->get('field_area')->value = $yacht->get('field_area')->value;
          try {
            $cradle->save();
            $message .= "Cradle with name {$cradle->getTitle()} has been synced with yacht {$yacht->getTitle()}.<br>";
          }catch (\Exception $e){
            \Drupal::logger('Sync Failed')->error($e);
          }
        }
      }
    }

    if(!empty($message)) {
      \Drupal::logger('Successfully Synchronization')->info($message);
    }
    else{
      $message = 'No synchronization needed';
      \Drupal::logger('Successfully Synchronization')->info($message);
    }

    exit('Synchronization finished. Check logs.');
  }

}