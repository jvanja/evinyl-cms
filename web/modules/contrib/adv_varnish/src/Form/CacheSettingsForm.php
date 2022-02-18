<?php

namespace Drupal\adv_varnish\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatter;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Configure varnish settings for this site.
 */
class CacheSettingsForm extends ConfigFormBase {

  /**
   * DateFormatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Drupal request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The router builder service.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routerBuilder;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a AdvancedVarnishCacheSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   Date formatter used to set cache lifetime.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Request stack service.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $router_builder
   *   The router builder service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              DateFormatter $date_formatter,
                              RequestStack $request,
                              RouteBuilderInterface $router_builder,
                              StateInterface $state) {
    parent::__construct($config_factory);
    $this->dateFormatter = $date_formatter;
    $this->request = $request->getCurrentRequest();
    $this->routerBuilder = $router_builder;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('date.formatter'),
      $container->get('request_stack'),
      $container->get('router.builder'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adv_varnish_cache_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['adv_varnish.cache_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('adv_varnish.cache_settings');

    $form['adv_varnish'] = [
      '#tree' => TRUE,
    ];

    $form['adv_varnish']['general'] = [
      '#title' => $this->t('General settings'),
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['adv_varnish']['general']['varnish_purger'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use built-in Varnish purger'),
      '#default_value' => $config->get('general.varnish_purger'),
      '#description' => $this->t('Check, if you want to use built in purger
        (this also enable Flush all varnish cache and invalidation for tags/url).'),
    ];

    $form['adv_varnish']['general']['varnish_server'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Varnish server host'),
      '#default_value' => $config->get('general.varnish_server'),
      '#description' => $this->t('Provide Varnish server host details,
        multiple servers supported separate them by space ex: 127.0.0.1 127.0.0.2'),
      '#states' => [
        'visible' => [
          [':input[name="adv_varnish[general][varnish_purger]"]' => ['checked' => TRUE]],
        ],
        'required' => [
          [':input[name="adv_varnish[general][varnish_purger]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['adv_varnish']['general']['purger_maintenance_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prevent Varnish purge while in Maintenance Mode'),
      '#default_value' => $config->get('general.purger_maintenance_mode'),
      '#description' => $this->t('Check, if you want to
      prevent purge request being sent to Varnish server while the Drupal
      maintenance mode is enabled. It is useful to keep site pages in Varnish
      cache during deploy or any action in maintenance_mode.'),
      '#states' => [
        'visible' => [
          [':input[name="adv_varnish[general][varnish_purger]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['adv_varnish']['general']['logging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Logging'),
      '#default_value' => $config->get('general.logging'),
      '#description' => $this->t('Check, if you want to log vital actions to watchdog.'),
    ];

    $form['adv_varnish']['general']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug mode'),
      '#default_value' => $config->get('general.debug'),
      '#description' => $this->t('Check if you want to add debug info.'),
    ];

    $form['adv_varnish']['general']['noise'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hashing Noise'),
      '#default_value' => $config->get('general.noise'),
      '#description' => $this->t('This works as private key, you can change it at any time.'),
    ];

    $host = $this->request->getHost();
    $secret = $config->get('general.secret') ?: hash('sha256', time() . $host);
    $form['adv_varnish']['general']['secret'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Value for Varnish secret header.'),
      '#default_value' => $secret,
      '#description' => $this->t('This will be set to all objects in Varnish,
        therefore it will be used to clear all Varnish cache
        and invalidate Varnish cache by tags.'),
    ];

    $options = [10, 30, 60, 120, 300, 600, 900, 1800, 3600];
    $options = array_map([$this->dateFormatter, 'formatInterval'], array_combine($options, $options));
    $options[0] = $this->t('No Grace (bad idea)');
    $grace_hint = $this->t("Grace in the scope of Varnish means delivering otherwise
      expired objects when circumstances call for it.
      This can happen because the backend-director selected is down or a
      different thread has already made a request to the backend
      that's not yet finished."
    );
    $form['adv_varnish']['general']['grace'] = [
      '#title' => $this->t('Grace'),
      '#type' => 'select',
      '#options' => $options,
      '#description' => $grace_hint,
      '#default_value' => $config->get('general.grace'),
    ];

    // Cache time for Varnish.
    $period = [0, 60, 180, 300, 600, 900, 1800,
      2700, 3600, 10800, 21600, 32400, 43200, 86400, 604800, 2629746,
    ];
    $period = array_map([$this->dateFormatter, 'formatInterval'], array_combine($period, $period));
    $period[0] = $this->t('no caching');
    $form['adv_varnish']['general']['page_cache_maximum_age'] = [
      '#type' => 'select',
      '#title' => $this->t('Page cache maximum age'),
      '#default_value' => $config->get('general.page_cache_maximum_age'),
      '#options' => $period,
      '#description' => $this->t('The maximum time a page can be cached by varnish.'),
    ];

    // Availability settings.
    $form['adv_varnish']['available'] = [
      '#title' => $this->t('Availability settings'),
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['adv_varnish']['available']['enable_cache'] = [
      '#title' => $this->t('Enable varnish caching'),
      '#type' => 'checkbox',
      '#description' => $this->t('Check if you want enable Varnish cache for the site.'),
      '#default_value' => $config->get('available.enable_cache'),
    ];

    $form['adv_varnish']['available']['authenticated_users'] = [
      '#title' => $this->t('Enable varnish for authenticated users'),
      '#type' => 'checkbox',
      '#description' => $this->t('Check if you want enable Varnish support for authenticated users.'),
      '#default_value' => $config->get('available.authenticated_users'),
    ];

    $form['adv_varnish']['available']['esi'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Varnish ESI support'),
      '#default_value' => $config->get('available.esi'),
    ];

    $form['adv_varnish']['available']['esi_purge_user_blocks'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Purge user blocks on POST request.'),
      '#default_value' => $config->get('available.esi_purge_user_blocks'),
      '#description' => $this->t('If this is checked "user:id" tag will be purged on POST request.
         Can be useful if there any user related info can be changed via this request.'
      ),
      '#states' => [
        'visible' => [
          [':input[name="adv_varnish[available][esi]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['adv_varnish']['available']['excluded_urls'] = [
      '#title' => $this->t('Excluded URLs'),
      '#type' => 'textarea',
      '#description' => $this->t('Specify excluded request urls @format.', ['@format' => '<SERVER_NAME>|<partial REQUEST_URI *>']),
      '#default_value' => $config->get('available.excluded_urls'),
    ];

    $form['adv_varnish']['cache_control'] = [
      '#title' => $this->t('Cache Control'),
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['adv_varnish']['cache_control']['anonymous'] = [
      '#title' => $this->t('Cache control headers for anonymous users.'),
      '#type' => 'textarea',
      '#description' => $this->t('Cache control headers for anonymous users.'),
      '#default_value' => $config->get('cache_control.anonymous'),
    ];

    $form['adv_varnish']['cache_control']['authenticated'] = [
      '#title' => $this->t('Cache control headers for logged in users.'),
      '#type' => 'textarea',
      '#description' => $this->t('Cache control headers for logged in users.'),
      '#default_value' => $config->get('cache_control.authenticated'),
    ];

    $form['adv_varnish']['redirect'] = [
      '#title' => $this->t('Redirect'),
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['adv_varnish']['redirect']['redirect_forbidden'] = [
      '#title' => $this->t('Prevent redirect after cookie update'),
      '#type' => 'checkbox',
      '#description' => $this->t('Cookies may be disabled for resource files, so no need to redirect in such a case.'),
      '#default_value' => $this->state->get('adv_varnish__redirect_forbidden', FALSE),
    ];

    $form['adv_varnish']['redirect']['redirect_forbidden_nocookie'] = [
      '#title' => $this->t('Prevent redirect if cookie is empty after update'),
      '#type' => 'checkbox',
      '#description' => $this->t("This one is important as search engines don't have cookie support and we don't want them to enter infinite loop. </br> Also images may have their cookies be stripped at Varnish level."),
      '#default_value' => $this->state->get('adv_varnish__redirect_forbidden_nocookie', TRUE),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('adv_varnish.cache_settings');
    $values = $form_state->getValue('adv_varnish');

    // If usage of varnish terminal is changed we need to rebuild routes
    // in order to show/hide Varnish purge page.
    $need_rebuild = FALSE;
    $stored_settings = $config->get('general.varnish_purger');
    $new_settings = $values['general']['varnish_purger'];
    if ($stored_settings !== $new_settings) {
      $need_rebuild = TRUE;
    }

    $this->config('adv_varnish.cache_settings')
      ->set('general', $values['general'])
      ->set('available', $values['available'])
      ->set('cache_control', $values['cache_control'])
      ->save();

    $this->state->set('adv_varnish__redirect_forbidden', $values['redirect']['redirect_forbidden']);
    $this->state->set('adv_varnish__redirect_forbidden_nocookie', $values['redirect']['redirect_forbidden_nocookie']);

    parent::submitForm($form, $form_state);
    if ($need_rebuild) {
      $this->routerBuilder->rebuild();
    }
  }

}
