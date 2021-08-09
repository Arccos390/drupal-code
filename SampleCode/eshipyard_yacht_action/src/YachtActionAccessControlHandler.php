<?php

namespace Drupal\eshipyard_yacht_action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the yacht action entity type.
 */
class YachtActionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view yacht action');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit yacht action', 'administer yacht action'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete yacht action', 'administer yacht action'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create yacht action', 'administer yacht action'], 'OR');
  }

}
