<?php

namespace Drupal\adv_varnish\Form;

use Drupal\adv_varnish\RequestHandler;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure varnish settings for this site.
 */
class DeflateForm extends FormBase {

  /**
   * Stores the state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * User account interface.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a AdvancedVarnishCacheDeflateForm.php object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current account.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(StateInterface $state,
                              AccountProxyInterface $account,
                              RendererInterface $renderer) {
    $this->state = $state;
    $this->account = $account;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('current_user'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adv_varnish_deflate';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['adv_varnish.deflate'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['adv_varnish'] = [
      '#tree' => TRUE,
    ];

    // Get info for progress bar.
    $account = $this->account;
    $deflate_info = $this->state->get('adv_varnish_deflate_info');
    $deflate_ids = $this->state->get('adv_varnish_deflate_ids');

    if (!empty($deflate_info)) {
      $form['deflate_info'] = [
        '#title' => $this->t('Deflate cache info'),
        '#type' => 'details',
        '#tree' => TRUE,
        '#open' => TRUE,
      ];

      $form['deflate_info']['deflate_info1'] = [
        '#type' => 'item',
        '#title' => $this->t('Last deflation Info'),
        '#markup' => $this->t('User = @name (@uid), Date = @date, Step = @step%, Key = @key', [
          '@name' => $account->getDisplayName(),
          '@uid' => $deflate_info['uid'],
          '@date' => date('c', $deflate_info['time']),
          '@step' => $deflate_info['step'],
          '@key' => $deflate_info['key'],
        ]),
      ];
      $progress = 100 - count($deflate_ids);

      if ($progress < 100) {
        $build = [
          '#theme' => 'progress_bar',
          '#percent' => $progress,
          '#message' => $this->t('Progress is not updated via ajax.'),
          '#label' => $this->t('Deflate progress.'),
        ];
        $progress_bar = $this->renderer->renderPlain($build);
      }
      else {
        $progress_bar = $this->t('Completed');
      }

      $form['deflate_info']['deflate_info2'] = [
        '#type' => 'item',
        '#title' => $this->t('Last deflation progress'),
        '#markup' => $progress_bar,
        '#suffix' => '<br />',
      ];
    }

    // Step size.
    $options = [
      '1' => '1%',
      '2' => '2%',
      '5' => '5%',
      '10' => '10%',
      '20' => '20%',
      '50' => '50%',
      '100' => '100',
    ];
    $form['deflate'] = [
      '#title' => $this->t('Deflate cache'),
      '#type' => 'details',
      '#description' => $this->t('Deflation is a process that will slowly invalidate all Varnish cache on cron runs.'),
      '#tree' => TRUE,
      '#collapsible' => TRUE,
      '#open' => TRUE,
    ];
    $form['deflate']['step'] = [
      '#title' => $this->t('Step size'),
      '#description' => $this->t('Amount of cache that will be invalidated on each deflation step.'),
      '#type' => 'select',
      '#default_value' => '10',
      '#options' => $options,
    ];
    $form['deflate']['start'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start deflation'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('deflate');

    // Get current user.
    $account = $this->account;

    $deflate_info = [
      'key' => $this->state->get('adv_varnish_deflate_key', hash('sha256', RequestHandler::HEADER_CACHE_TAG)),
      'time' => time(),
      'uid' => $account->id(),
      'step' => $values['step'],
    ];

    $deflate_ids = [];
    for ($i = 0; $i < 100; $i++) {
      $deflate_ids[] = str_pad($i, 2, '0', STR_PAD_LEFT);
    }

    $this->state->set('adv_varnish_deflate_key', $this->uniqueId());
    $this->state->set('adv_varnish_deflate_info', $deflate_info);
    $this->state->set('adv_varnish_deflate_ids', $deflate_ids);

  }

  /**
   * Generated unique id based on time.
   *
   * @return string
   *   Unique id.
   */
  private function uniqueId() {
    $id = uniqid(time(), TRUE);
    return substr(md5($id), 5, 10);
  }

}
