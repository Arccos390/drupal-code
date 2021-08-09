<?php

namespace Drupal\eshipyard_create_request\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides an example block.
 *
 * @Block(
 *   id = "eshipyard_create_request_example",
 *   admin_label = @Translation("Example"),
 *   category = @Translation("eshipyard_create_request")
 * )
 */
class ExampleBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['content'] = [
      '#markup' => $this->t('It works!'),
    ];
    return $build;
  }

}
