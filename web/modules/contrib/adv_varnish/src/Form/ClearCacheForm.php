<?php

namespace Drupal\adv_varnish\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\adv_varnish\CacheManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to clear varnish cache.
 */
class ClearCacheForm extends FormBase {

  /**
   * Constants that used for predefined options.
   */
  public const CACHE_CLEAR_TYPE_TAG = 'tag';
  public const CACHE_CLEAR_TYPE_URI = 'uri';
  public const CACHE_CLEAR_TYPE_FULL = 'full';

  /**
   * CacheManager service.
   *
   * @var \Drupal\adv_varnish\CacheManagerInterface
   */
  private $cacheManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adv_varnish_cache_clear';
  }

  /**
   * ClearCacheForm constructor.
   *
   * @param \Drupal\adv_varnish\CacheManagerInterface $cacheManager
   *   CacheManager Service.
   */
  public function __construct(CacheManagerInterface $cacheManager) {
    $this->cacheManager = $cacheManager;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['adv_varnish'] = [
      '#tree' => TRUE,
    ];

    $form['adv_varnish_cache_purge'] = [
      '#title' => t('Purge settings'),
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#open' => TRUE,
    ];

    $options = [
      self::CACHE_CLEAR_TYPE_TAG => $this->t('Tag'),
      self::CACHE_CLEAR_TYPE_URI => $this->t('URL'),
      self::CACHE_CLEAR_TYPE_FULL => $this->t('Full cache of site'),
    ];

    $form['adv_varnish_cache_purge']['type'] = [
      '#type' => 'radios',
      '#title' => t('Select purge type'),
      '#options' => $options,
      '#default_value' => $form_state->getValue('type') ?: self::CACHE_CLEAR_TYPE_URI,
      '#required' => TRUE,
    ];

    $form['adv_varnish_cache_purge']['arguments'] = [
      '#type' => 'textfield',
      '#title' => t('Tag or URL to purge.'),
      '#default_value' => $form_state->getValue('arguments'),
      '#description' => t("Examples: 'node:1', '/node/1', '/*/1'"),
      '#states' => [
        'invisible' => [
          ':input[name="type"]' => ['value' => self::CACHE_CLEAR_TYPE_FULL],
        ],
        'required' => [
          ':input[name="type"]' => [
            ['value' => self::CACHE_CLEAR_TYPE_URI],
            ['value' => self::CACHE_CLEAR_TYPE_TAG],
          ],
        ],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Run purge'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->disableRedirect();

    switch ($form_state->getValue('type')) {

      // Clear cache by particular tags.
      case self::CACHE_CLEAR_TYPE_TAG:
        $tags = explode(' ', trim($form_state->getValue('arguments')));
        $result = $this->cacheManager->purgeTags($tags);
        break;

      // Flush all cache of the site.
      case self::CACHE_CLEAR_TYPE_FULL:
        $result = $this->cacheManager->flushAllCaches();
        break;

      // Clear cache by URL.
      case self::CACHE_CLEAR_TYPE_URI:
        $result = $this->cacheManager->purgeUri($form_state->getValue('arguments'));
        break;
    }

    if (empty($result)) {
      $this->messenger()->addError($this->t("The Varnish cache wasn't banned. Please read Database logs by this channel: adv_varnish."));
    }
    else {
      $this->messenger()->addStatus($this->t('Varnish cache banned.'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('adv_vanish.cache_manager')
    );
  }

}
