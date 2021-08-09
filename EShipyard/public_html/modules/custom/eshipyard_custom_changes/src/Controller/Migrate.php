<?php

namespace Drupal\eshipyard_custom_changes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\Entity\Node;

class Migrate extends ControllerBase {

  public function next_year_migrate(){
    
    $query = \Drupal::database()
      ->query('select users_field_data.name, users_field_data.uid as uid, user__field_next_year.field_next_year_value as next_year
                     from {users_field_data}
                     inner join {user__field_next_year} on uid = entity_id
                     where 1 
                     ',
        []
      );
    $result = $query->fetchAll();

    foreach ($result as $key => $value){
//      $value->uid;
      $answer = 'Maybe';
      if($value->next_year == 1){
        $answer = 'Yes';
      }elseif($value->next_year == 0){
        $answer = 'No';
      }elseif($value->next_year == 2){
        $answer = 'Maybe';
      }
      $nodes = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadByProperties(['field_entity_ref_user' => $value->uid]);

      if(count($nodes) > 1){
        dump($value->name);
      }

//      if(!empty($nodes)){
//        $node = array_values($nodes)[0];
//        $node->get('field_remain_ashore')->value = $answer;
//        $node->save();
//      }
    }
    exit('OK');
  }

}