<?php
namespace Drupal\evinyl_combined\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\evinyl_combined\Controller\EvinylCombinedController;

/**
 * Configure example settings for this site.
 */
class EvinylCombinedForm extends FormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'combined.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'evinyl_combined_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['discogsId'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Discogs ID'),
      '#default_value' => '',
      '#description' => 'Enter single Discogs ID.'
    ];

    $form['deezerId'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Deezer ID'),
      '#default_value' => '',
      '#description' => 'Enter single Deezer ID.'
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
    if (strlen($form_state->getValue('discogsId')) < 1) {
      $form_state->setErrorByName('discogsId', $this->t('Need one ID.'));
    }
    if (strlen($form_state->getValue('deezerId')) < 1) {
      $form_state->setErrorByName('deezerId', $this->t('Need one ID.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    global $base_url;
    $edit_unpublish_url = $base_url . '/admin/content?type=album&status=2';

    $discogsId = trim($form_state->getValue('discogsId'));
    $deezerId = trim($form_state->getValue('deezerId'));

    $importController = new EvinylcombinedController;
    $releases = $importController->posts(array(
      'discogsId' => $discogsId,
      'deezerId' => $deezerId
    ));

    // var_dump($releases);
    // die;

    if ($releases['status'] == TRUE) {
      $this->messenger()->addStatus($this->t('Your import is completed. Please moderate the <a href="'.$edit_unpublish_url.'">new content</a>. '));
    } else {
      $this->messenger()->addWarning($this->t('Your import FAILED. Please double check your IDs. @message', ['@message' => $releases]));
    // $this->messenger()->addStatus($this->t('Your import is completed. Please moderate the new content. @albums', ['@albums' => $releases]));
    }
  }

}
