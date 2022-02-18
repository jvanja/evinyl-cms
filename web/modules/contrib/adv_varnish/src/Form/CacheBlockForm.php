<?php

namespace Drupal\adv_varnish\Form;

use Drupal\adv_varnish\RequestHandler;
use Drupal\block\BlockForm;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Override form for block instance forms.
 */
class CacheBlockForm extends BlockForm {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a CacheBlockForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $manager
   *   The ConditionManager for building the visibility UI.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language
   *   The language manager.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $plugin_form_manager
   *   The plugin form manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              ExecutableManagerInterface $manager,
                              ContextRepositoryInterface $context_repository,
                              LanguageManagerInterface $language,
                              ThemeHandlerInterface $theme_handler,
                              PluginFormFactoryInterface $plugin_form_manager,
                              DateFormatterInterface $date_formatter) {
    parent::__construct($entity_type_manager, $manager, $context_repository, $language, $theme_handler, $plugin_form_manager);
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.condition'),
      $container->get('context.repository'),
      $container->get('language_manager'),
      $container->get('theme_handler'),
      $container->get('plugin_form.factory'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $block = $form_state->getFormObject()->getEntity();
    $settings = $block->get('settings');

    $form['settings']['adv_varnish'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced Varnish cache'),
      '#tree' => TRUE,
    ];

    // Add ESI block support.
    $form['settings']['adv_varnish']['cache']['esi'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('ESI block'),
      '#default_value' => $settings['cache']['esi'] ?? FALSE,
    ];

    // Cache time for Varnish.
    $period = [
      0,
      60,
      180,
      300,
      600,
      900,
      1800,
      2700,
      3600,
      10800,
      21600,
      32400,
      43200,
      86400,
    ];
    $period = array_map([$this->dateFormatter, 'formatInterval'], array_combine($period, $period));
    $period[0] = t('No caching');

    $form['settings']['adv_varnish']['cache']['ttl'] = [
      '#type' => 'select',
      '#title' => t('TTL'),
      '#default_value' => $settings['cache']['ttl'] ?? 0,
      '#options' => $period,
      '#description' => t('The maximum time a page can be cached by varnish.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[adv_varnish][cache][esi]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $options = [
      RequestHandler::CACHE_PER_PAGE => $this->t('Shared'),
      RequestHandler::CACHE_PER_ROLE => $this->t('Per User Roles'),
      RequestHandler::CACHE_PER_USER => $this->t('Per User ID'),
    ];

    $form['settings']['adv_varnish']['cache']['cachemode'] = [
      '#title' => $this->t('Cache granularity (Cache bin)'),
      '#description' => $this->t('Choosing those will increase cache relevance, but reduce performance.'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $settings['cache']['cachemode'] ?? RequestHandler::CACHE_PER_ROLE,
      '#states' => [
        'visible' => [
          ':input[name="settings[adv_varnish][cache][esi]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $block = $this->getEntity();
    $adv_varnish = $form_state->getValue('settings')['adv_varnish'];
    $settings = $block->get('settings');
    $settings['cache'] = $adv_varnish['cache'];
    $block->set('settings', $settings);
  }

}
