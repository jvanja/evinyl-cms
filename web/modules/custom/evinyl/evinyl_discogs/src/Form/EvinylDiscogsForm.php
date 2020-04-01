<?php
namespace Drupal\evinyl_discogs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class EvinylDiscogsForm extends ConfigFormBase {

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
    return 'evinyl_discogs_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['ids'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Discogs Ids'),
      '#default_value' => '',
      '#description' => 'Enter Discogs IDs each on a new line'
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cleanIds = trim($form_state->getValue('ids'));
    $ids = explode('\n', $cleanIds);


    parent::submitForm($form, $form_state);
  }

}
