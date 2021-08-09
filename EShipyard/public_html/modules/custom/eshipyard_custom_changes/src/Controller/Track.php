<?php

namespace Drupal\eshipyard_custom_changes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

class Track extends ControllerBase {

  public function tracker(){

    $imgLink = drupal_get_path('module', 'eshipyard_custom_changes') . '/image/img.png';

    $uid = $_GET['uid'];
    if(isset($uid)) {
      $account = \Drupal\user\Entity\User::load($uid);
      if (!empty($account)) {
        $account->set('field_user_track_email', 1);
        $account->save();
      }
    }
    return new RedirectResponse(Url::fromUri('https://bsg.e-shipyard.gr/'. $imgLink)->toString());
  }

}