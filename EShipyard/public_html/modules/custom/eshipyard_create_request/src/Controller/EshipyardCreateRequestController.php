<?php

namespace Drupal\eshipyard_create_request\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for eshipyard_create_request routes.
 */
class EshipyardCreateRequestController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
