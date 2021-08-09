<?php

namespace Drupal\eshipyard_email\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 *
 * @Action(
 *   id = "eshipyard_email_reset_invitation_action",
 *   label = @Translation("Reset Email Invitation"),
 *   type = "user"
 * )
 */
class CEmailReset extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL) {
    // Skip blocking user if they are already blocked.
    //@todo also reset the pop-up message.
    if ($account !== FALSE && $account->isActive()) {
      $account->get('field_invitation_sent')->value = 0;
      $account->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\user\UserInterface $object */
    $access = $object->status->access('edit', $account, TRUE)
      ->andIf($object->access('update', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }
}
