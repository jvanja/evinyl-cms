<?php

namespace Drupal\evinyl_discord\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\evinyl_discord\Form
 *
 * @ingroup evinyl_discord
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'evinyl_discord_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['evinyl_discord.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('evinyl_discord.settings');

     $form['discord_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Discord Bot Token'),
      '#default_value' => $config->get('discord_token'), // MTI4MzM2NjI0NjE2Njk1ODEzMQ.GpYGSR.kCIF_OY9JmEc8Ryx929Vm9-JNyfD-dPxnN0r_o
    ];

    $form['discord_channel_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Discord Channel ID'),
      '#default_value' => $config->get('discord_channel_id'), // 1276826215906279489
    ];

    if (empty($config->get('discord_token')) || empty($config->get('discord_channel_id'))) {
      $this->messenger()->addWarning($this->t('Discord messages page will be available after you fill "<strong>Discord Token and Channel ID</strong>" fields.'));
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('evinyl_discord.settings')
      ->set('discord_token', $form_state->getValue('discord_token'))
      ->set('discord_channel_id', $form_state->getValue('discord_channel_id'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
