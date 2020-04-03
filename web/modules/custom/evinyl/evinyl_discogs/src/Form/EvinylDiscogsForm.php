<?php
namespace Drupal\evinyl_discogs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\evinyl_discogs\Controller\EvinylDiscogsController;

/**
 * Configure example settings for this site.
 */
class EvinylDiscogsForm extends FormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'discogs.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'evinyl_discogs_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['ids'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Discogs IDs'),
      '#default_value' => '',
      '#description' => 'Enter Discogs IDs. One per line.'
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (strlen($form_state->getValue('ids')) < 1) {
      $form_state->setErrorByName('ids', $this->t('Need at least one ID.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cleanIds = trim($form_state->getValue('ids'));
    $ids = explode('\n', $cleanIds);

    $importController = new EvinylDiscogsController;
    $releases = $importController->posts($ids);

    $this->messenger()->addStatus($this->t('Your import is completed. Please moderate the new content. @albums', ['@albums' => $releases]));
  }

}
