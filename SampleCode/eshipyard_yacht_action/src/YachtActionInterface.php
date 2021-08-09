<?php

namespace Drupal\eshipyard_yacht_action;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a yacht action entity type.
 */
interface YachtActionInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the yacht action creation timestamp.
   *
   * @return int
   *   Creation timestamp of the yacht action.
   */
  public function getCreatedTime();

  /**
   * Sets the yacht action creation timestamp.
   *
   * @param int $timestamp
   *   The yacht action creation timestamp.
   *
   * @return \Drupal\eshipyard_yacht_action\YachtActionInterface
   *   The called yacht action entity.
   */
  public function setCreatedTime($timestamp);

}
