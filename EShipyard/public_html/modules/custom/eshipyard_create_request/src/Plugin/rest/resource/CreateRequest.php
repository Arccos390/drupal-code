<?php

namespace Drupal\eshipyard_create_request\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides a Create Request Resource
 *
 * @RestResource(
 *   id = "create_request_resource",
 *   label = @Translation("Create Request Resource"),
 *   uri_paths = {
 *     "canonical" = "//api/request/create",
 *	   "drupal.org/link-relations/create" = "//api/request/create"
 *   }
 * )
 */
class CreateRequest extends ResourceBase {
 /**
   * Responds to POST requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param $node_type
   * @param $data
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   * Throws exception expected.
   */

 /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;
  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
	  
 


  public function get() {
  	$response = ['message' => 'Hello, this is a rest service GET'];
    return new ResourceResponse($response);
  }

  public function post() {
    // $node = Node::create(
    //   array(
    //     'type' => $node_type,
    //     'title' => $data->title->value,
    //     'body' => [
    //       'summary' => '',
    //       'value' => $data->body->value,
    //       'format' => 'full_html',
    //     ],
    //   )
    // );
    //$node->save();

    $response = ['message' => 'Hello, this is a rest service POST'];
    return new ResourceResponse($response);
  }
}