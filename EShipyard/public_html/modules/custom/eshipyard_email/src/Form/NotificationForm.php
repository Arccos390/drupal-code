<?php

namespace Drupal\eshipyard_email\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
* Implements an example form.
*/
class NotificationForm extends ConfigFormBase {

  /**
  * {@inheritdoc}
  */
  public function getFormId() {
    return 'notification_email_form';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['eshipyard_email.settings'];
  }

  /**
  * {@inheritdoc}
  */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['notification_message'] = array(
      '#type' => 'textarea',
      '#title' => t('Message'),
      '#default_value' => $this->config('eshipyard_email.settings')->get('message'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
  * {@inheritdoc}
  */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('eshipyard_email.settings')->set('message', $form_state->getValue('notification_message'))->save();
  }

}