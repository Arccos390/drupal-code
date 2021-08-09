<?php

namespace Drupal\eshipyard_custom_changes\Controller;

use Drupal\Core\Controller\ControllerBase;
//use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\Entity\Node;

class UpdateYachtFields extends ControllerBase {

  public function set_remain_ashore_field(){
    $yacht_id = \Drupal::request()->get('yacht_id');
    $answer = \Drupal::request()->get('answer');
    $message = 'NULL uid given';
    if(isset($yacht_id)) {
      $node = Node::load($yacht_id);
      $message = 'No yacht found';
      if(!empty($node)) {
        if ($answer != 'Yes' && $answer != 'No' && $answer != 'Maybe') {
          $message = 'Wrong value given';
        }else{
          $node->set('field_remain_ashore', $answer);
          $node->save();
          $message = "Update yacht {$node->getTitle()}, field_remain_ashore to {$answer}";
        }
      }
    }
    exit($message);
  }

}