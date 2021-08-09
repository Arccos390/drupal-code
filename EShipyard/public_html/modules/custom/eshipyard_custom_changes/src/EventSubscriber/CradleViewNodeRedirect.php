<?php


namespace Drupal\eshipyard_custom_changes\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CradleViewNodeRedirect implements EventSubscriberInterface {

  public function checkForRedirection(GetResponseEvent $event) {

    $baseUrl = $event->getRequest()->getBaseUrl();
    $attr = $event->getRequest()->attributes;
    if(null !== $attr &&
      null !== $attr->get('node') &&
      $attr->get('_entity_form') !== 'node.edit' &&
      $attr->get('node')->get('type')->getString() == 'cradle') {
      $event->setResponse(new RedirectResponse($baseUrl."/admin/cradle_log_page/{$attr->get('node')->get('nid')->value}"));
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('checkForRedirection');
    return $events;
  }

}