<?php

namespace Drupal\eshipyard_custom_changes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\Entity\Node;
use Drupal\field\Entity\FieldConfig;
use Drupal\Component\Render\FormattableMarkup;

class CradleLog extends ControllerBase {

  public function get_cradle_log_history($yacht_id){
    $message = [];
    $query = \Drupal::database()
      ->query('SELECT cradle_id, type
               FROM {yacht_action} 
               WHERE yacht_id = :yacht_id
               ORDER BY created DESC',
        [
          'yacht_id' => $yacht_id,
        ]
      );
    $result = $query->fetchAll();
    $allowed_values_cradle_type = FieldConfig::load('node.cradle.field_cradle_type')->getFieldStorageDefinition()->getSettings()['allowed_values'];
    $json_key = 0;
    foreach ($result as $value){
      if(isset($value->cradle_id)){
        $cradle = Node::load($value->cradle_id);
        $message[$json_key]['yacht_action_type'] = $value->type;
        $message[$json_key]['cradle_title'] = $cradle->get('field_cradle_no')->value . $allowed_values_cradle_type[$cradle->get('field_cradle_type')->value] . $cradle->get('field_cradle_dimensions')->value;
        $message[$json_key]['cradle_id'] = $value->cradle_id;
        $json_key++;
      }
    }
    json_encode($message);
    return new JsonResponse($message, 200, ['Content-Type'=> 'application/json']);
  }

  public function cradle_view_history($cradle_id){

    $query = \Drupal::database()
      ->query('SELECT yacht_id, type, approved_date
               FROM {yacht_action} 
               WHERE cradle_id = :cradle_id AND status = :completed
               ORDER BY created DESC',
        [
          'cradle_id' => $cradle_id,
          'completed' => 'completed',
        ]
      );
    $results = $query->fetchAll();

    $header = array(
      array('data' => 'Name'),
      array('data' => 'Approved Date'),
      array('data' => 'Type of action'),
    );

    $row = [];
    foreach ($results as $result) {
      $yacht_node = Node::load($result->yacht_id);
      $row[] = array(
          'name' => new FormattableMarkup('<a href=":link">@name</a>',
            [
              ':link' => "/node/{$result->yacht_id}",
              '@name' => $yacht_node->getTitle(),
            ]
          ),
          'approved_date' => $result->approved_date,
          'type' => $result->type,
      );
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $row,
    ];

  }

}