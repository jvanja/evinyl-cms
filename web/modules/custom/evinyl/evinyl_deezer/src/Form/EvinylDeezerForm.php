<?php

namespace Drupal\evinyl_deezer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\evinyl_deezer\Controller\EvinylDeezerController;

/**
 * Configure example settings for this site.
 */
class EvinylDeezerForm extends FormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  public const SETTINGS = 'deezer.settings';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['ids'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Deezer IDs'),
      '#default_value' => '',
      '#description' => 'Enter Deezer IDs. One per line.',
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start importing'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'evinyl_deezer_import';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    global $base_url;
    $edit_unpublish_url = $base_url . '/admin/content?type=album&status=2';

    $cleanIds = trim($form_state->getValue('ids'));
    $ids = explode(\PHP_EOL, $cleanIds);

    $importController = new EvinylDeezerController();
    $releases = $importController->posts($ids);

    if ($releases) {
      $this->messenger()->addStatus($this->t('Your import is completed. Please moderate the <b><a href="' . $edit_unpublish_url . '">new content</a></b>. '));
    }
    else {
      $this->messenger()->addWarning($this->t('Your import FAILED. Probably you put a wrong Deezer release ID'));
    }
    // $this->messenger()->addStatus($this->t('Your import is completed. Please moderate the new content. @albums', ['@albums' => $releases]));
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('ids') === '') {
      $form_state->setErrorByName('ids', $this->t('Need at least one ID.'));
    }
  }

}
