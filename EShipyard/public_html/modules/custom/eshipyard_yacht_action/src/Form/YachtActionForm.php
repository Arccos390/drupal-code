<?php

namespace Drupal\eshipyard_yacht_action\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the yacht action entity edit forms.
 */
class YachtActionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => render($link)];

    if ($result == SAVED_NEW) {
      drupal_set_message($this->t('New yacht action %label has been created.', $message_arguments));
      $this->logger('eshipyard_yacht_action')->notice('Created new yacht action %label', $logger_arguments);
    }
    else {
      drupal_set_message($this->t('The yacht action %label has been updated.', $message_arguments));
      $this->logger('eshipyard_yacht_action')->notice('Yacht action has been updated %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.yacht_action.canonical', ['yacht_action' => $entity->id()]);
  }

}
