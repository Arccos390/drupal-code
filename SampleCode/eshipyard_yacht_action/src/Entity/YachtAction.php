<?php

namespace Drupal\eshipyard_yacht_action\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\eshipyard_yacht_action\YachtActionInterface;
use Drupal\user\UserInterface;


/**
 * Defines the yacht action entity class.
 *
 * @ContentEntityType(
 *   id = "yacht_action",
 *   label = @Translation("Yacht action"),
 *   label_collection = @Translation("Yacht actions"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\eshipyard_yacht_action\YachtActionListBuilder",
 *     "views_data" = "Drupal\eshipyard_yacht_action\Entity\YachtActionEntityViewsData",
 *     "access" = "Drupal\eshipyard_yacht_action\YachtActionAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\eshipyard_yacht_action\Form\YachtActionForm",
 *       "edit" = "Drupal\eshipyard_yacht_action\Form\YachtActionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "yacht_action",
 *   data_table = "yacht_action_field_data",
 *   admin_permission = "administer yacht action",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/content/yacht-action/add",
 *     "canonical" = "/calendar",
 *     "edit-form" = "/admin/content/yacht-action/{yacht_action}/edit",
 *     "delete-form" = "/admin/content/yacht-action/{yacht_action}/delete",
 *     "collection" = "/admin/content/yacht-action"
 *   },
 *   field_ui_base_route = "entity.yacht_action.settings"
 * )
 */
class YachtAction extends ContentEntityBase implements YachtActionInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * When a new yacht action entity is created, set the uid entity reference to
   * the current user as the creator of the entity.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += ['uid' => \Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getYachtId() {
    return $this->get('yacht_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['type'] = BaseFieldDefinition::create('list_string')
      ->setSetting('allowed_values', [
        'hauling' => 'Hauling',
        'launching' => 'Launching',
      ])
      ->setLabel(t('Type'))
      ->setDescription(t('The type of the action.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    
    $fields['arrival_date'] = BaseFieldDefinition::create('datetime')
      ->setSetting('datetime_type', 'date')
      ->setLabel(t('Arrival date'))
      ->setDescription(t('The date that the Owner will arrive in case of Launching'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);


    $fields['approved_date'] = BaseFieldDefinition::create('datetime')
      ->setSetting('datetime_type', 'date')
      ->setLabel(t('Approved date'))
      ->setDescription(t('The date that the yacht action was approved.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['suggested_date'] = BaseFieldDefinition::create('datetime')
      ->setSetting('datetime_type', 'date')
      ->setLabel(t('Suggested date'))
      ->setDescription(t('The date that the yacht action was suggested by the user.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setSetting('allowed_values', [
        'pending' => 'Pending',
        'progress' => 'In Progress',
        'approved' => 'Approved',
        'completed' => 'Completed',
      ])
      ->setLabel(t('Status'))
      ->setDescription(t('The status of the action.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('The user ID of the yacht action author.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['yacht_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Yacht'))
      ->setDescription(t('The Yacht ID of the yacht action author.'))
      //->setSetting('handler_settings',['target_bundles'=>['yacht'=>'yacht']] )
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['yacht' => 'yacht']])
      ->setDisplayOptions('form', [
        'type' => 'inline_entity_form_simple',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'yacht',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['cradle_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Cradle'))
      ->setDescription(t('The Cradle ID of the completed yacht action.'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['cradle' => 'cradle']])
      ->setDisplayOptions('form', [
        'weight' => 16,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the yacht action was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the yacht action was last edited.'));

    return $fields;
  }




}
