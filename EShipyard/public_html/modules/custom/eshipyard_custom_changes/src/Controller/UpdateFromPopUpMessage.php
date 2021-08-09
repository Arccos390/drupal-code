<?php

namespace Drupal\eshipyard_custom_changes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

class UpdateFromPopUpMessage extends ControllerBase {

  public function set_next_year_field_value(){
    $uid = \Drupal::request()->get('uid');
    $answer = \Drupal::request()->get('answer');
    $message = 'NULL uid given';
    if(isset($uid)) {
      $account = \Drupal\user\Entity\User::load($uid);
      $message = 'No user found';
      if(!empty($account)) {
        if ($answer != '2' && $answer != '1' && $answer != '0') {
          $message = 'Wrong value given';
        }else{
          $account->set('field_next_year', $answer);
          $account->save();
          $message = "Update account {$account->getAccountName()}, field_next_year to {$answer}";
        }
      }
    }
    exit($message);
  }

  public function get_next_year_field_value($uid){
    $message = [];
    $message['field_next_year'] = NULL;
    if(isset($uid)) {
      $account = \Drupal\user\Entity\User::load($uid);
      if(!empty($account)) {
        $message['field_next_year'] = $account->get('field_next_year')->value;
      }
    }
    json_encode($message);
    return new JsonResponse($message, 200, ['Content-Type'=> 'application/json']);
  }

}