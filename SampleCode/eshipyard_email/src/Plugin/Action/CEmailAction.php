<?php

namespace Drupal\eshipyard_email\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 *
 * @Action(
 *   id = "eshipyard_email_invitation_action",
 *   label = @Translation("Email Invitation"),
 *   type = "user"
 * )
 */
class CEmailAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL) {
    // Skip blocking user if they are already blocked.
    if ($account !== FALSE && $account->isActive()) {
      if (!empty($account->get('mail')->value)) {
        $mailManager = \Drupal::service('plugin.manager.mail');
        $to = $account->get('mail')->value;
        $module = 'eshipyard_email';
        $key = 'email_invitation';
        usleep(1000000);
        $params['message'] = $this->message($account->id());
        $result = $mailManager->mail($module, $key, $to, NULL, $params, NULL, $send = TRUE);
        if ($result['result'] !== TRUE) {
          \Drupal::logger('email_notification')
            ->error("There was a problem sending your email to {$to} and it was not sent.");
        }
        else {
          $account->get('field_invitation_sent')->value = 1;
          $account->save();
          \Drupal::logger('email_notification')
            ->info("Your email (invitation) to {$to} has been sent.");
        }
      }
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

  private function message($uid){
    $destination = "user/{$uid}";
    $alu_service = \Drupal::service('auto_login_url.create');
    $auto_login_url = $alu_service->create($uid, $destination, TRUE);
    $text = "<div class='mail-wrapper'>
                <h2>Please join our new portal, E-Shipyard(https://bsg.e-shipyard.gr), in order to check our availability and declare your preferable date of launching / hauling .</h2> 
                <h3 class='subheader'>When so, do not miss to declare also the date of your arrival in the Yard in order to make sure that everything will be ready, especially for the yacht owners who left us service orders to be carried .</h3> <br> 
                <div class='benefits'>
                    <p>The advantages of using our new portal to organize your yacht movements will be :</p> 
                    <ul>
                        <li> a. The yacht owner will have a live view of the Yard Travel lift (haul out /launching) schedule and in this way he will be able to organize his holiday plan easier and faster  .</li>
                        <li> b. Both the Yard and the Yacht owner will save much (communication) time for organizing hauling and launching appointments .</li>
                    </ul>
                </div> <br> 
                <div class='action-wrapper'>
                    <span class='action'>In order to complete your sign in click on the following link </span><a class='btn' href='{$auto_login_url}'>LOGIN</a>
                </div>
                <div class='next-login-wrapper'>
                  <div class='next-login-text'>Please note that in your next login your e-mail address is also your password, unless you have changed it.</div>
                </div>
                <div class='mail-footer'>           
                            
                </div>
            </div>";
    return $text;
  }
}
