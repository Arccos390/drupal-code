<?php

namespace Drupal\eshipyard_email\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;

/**
 *
 * @Action(
 *   id = "eshipyard_email_send_notification_report",
 *   label = @Translation("Email Notification"),
 *   type = "node"
 * )
 */
class CEmailNotificationReport extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($node = NULL) {
    $user_id = $node->get('field_entity_ref_user')->target_id;
    if(!empty($user_id)){
      $account = User::load($user_id);
      if(isset($account)){
        // Skip blocking user if they are already blocked.
        if ($account !== FALSE && $account->isActive()) {
          if (!empty($account->get('mail')->value)) {
            $mailManager = \Drupal::service('plugin.manager.mail');
            $to = $account->get('mail')->value;
            $module = 'eshipyard_email';
            $key = 'email_notification_message';
            usleep(1000000);
            $params['message'] = $this->message();
            if(isset($params['message'])){
              $result = $mailManager->mail($module, $key, $to, NULL, $params, NULL, $send = TRUE);
            }else{
              $result['result'] = FALSE;
            }
            if ($result['result'] !== TRUE) {
              \Drupal::logger('email_notification')
                ->error("There was a problem sending your email to {$to} and it was not sent.");
            }
            else {
              \Drupal::logger('email_notification')
                ->info("Your email (notification) to {$to} has been sent.");
            }
          }
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

  /**
   *  If you do any change in this function consider to also make the same
   * changes in class CEmailNotification.
   */
  public function message(){
    try {
      $config_message = \Drupal::config('eshipyard_email.settings')
        ->get('message');
      $text = "<div class='mail-wrapper' style='padding-top:50px'>
                <h3>{$config_message}</h3>
                <div class='mail-footer'>           
                    <span><b>Tel:</b> +30 27540 61409</span><span><b>Fax:</b> +30 27540 61023</span><span><b>E-mail:</b> info@bsg.com.gr</span>
                    <div class='address'>GR-213 00, Kilada Ermionidas, Argolida, Greece</div>            
                </div>
             </div>";
    }catch (\Exception $e){
      $text = NULL;
    }
    return $text;
  }
}
