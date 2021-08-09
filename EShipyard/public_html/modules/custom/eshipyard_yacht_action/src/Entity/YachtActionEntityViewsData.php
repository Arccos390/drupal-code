<?php

namespace Drupal\eshipyard_yacht_action\Entity;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\views\EntityViewsData;

/**
 * Class YachtActionEntityViewsData.
 *
 * @package Drupal\eshipyard_yacht_action\Entity
 */
class YachtActionEntityViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  protected function mapSingleFieldViewsData($table, $field_name, $field_type, $column_name, $column_type, $first, FieldDefinitionInterface $field_definition) {
    $views_field = parent::mapSingleFieldViewsData($table, $field_name, $field_type, $column_name, $column_type, $first, $field_definition);
    if ($field_type === 'datetime') {
      //$views_field['filter']['id'] = 'datetime';
    }
    return $views_field;
  }

}
