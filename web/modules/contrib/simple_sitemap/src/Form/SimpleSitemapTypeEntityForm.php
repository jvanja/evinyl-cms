<?php

namespace Drupal\simple_sitemap\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\SitemapGeneratorManager;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for sitemap type edit forms.
 */
class SimpleSitemapTypeEntityForm extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\simple_sitemap\Entity\SimpleSitemapTypeInterface
   */
  protected $entity;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The SitemapGenerator plugin manager.
   *
   * @var \Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\SitemapGeneratorManager
   */
  protected $sitemapGeneratorManager;

  /**
   * The UrlGenerator plugin manager.
   *
   * @var \Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager
   */
  protected $urlGeneratorManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.simple_sitemap.sitemap_generator'),
      $container->get('plugin.manager.simple_sitemap.url_generator')
    );
  }

  /**
   * SimpleSitemapTypeEntityForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   Entity type manager service.
   * @param \Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\SitemapGeneratorManager $sitemap_generator_manager
   *   The SitemapGenerator plugin manager.
   * @param \Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager $url_generator_manager
   *   The UrlGenerator plugin manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, SitemapGeneratorManager $sitemap_generator_manager, UrlGeneratorManager $url_generator_manager) {
    $this->entityTypeManager = $entity_manager;
    $this->sitemapGeneratorManager = $sitemap_generator_manager;
    $this->urlGeneratorManager = $url_generator_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#disabled' => !$this->entity->isNew(),
      '#maxlength' => EntityTypeInterface::ID_MAX_LENGTH,
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => '\Drupal\simple_sitemap\Entity\SimpleSitemapType::load',
      ],
    ];

    $form['sitemap_generator'] = [
      '#type' => 'select',
      '#title' => $this->t('Sitemap generator'),
      '#options' => array_map(function ($sitemap_generator) {
        return $sitemap_generator['label'];
      }, $this->sitemapGeneratorManager->getDefinitions()),
      '#default_value' => !$this->entity->isNew() ? $this->entity->get('sitemap_generator') : NULL,
      '#required' => TRUE,
      '#description' => $this->t('Sitemaps of this type will be built according to the sitemap generator plugin chosen here.'),
    ];

    $form['url_generators'] = [
      '#type' => 'select',
      '#title' => $this->t('URL generators'),
      '#options' => array_map(function ($url_generator) {
        return $url_generator['label'];
      }, $this->urlGeneratorManager->getDefinitions()),
      '#default_value' => !$this->entity->isNew() ? $this->entity->get('url_generators') : NULL,
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#description' => $this->t('Sitemaps of this type will be populated with URLs generated by these URL generator plugins.'),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#default_value' => $this->entity->get('description'),
      '#title' => $this->t('Administrative description'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $return = $this->entity->save();

    if ($return === SAVED_UPDATED) {
      $this->messenger()->addStatus($this->t('Sitemap type %label has been updated.', ['%label' => $this->entity->label()]));
    }
    else {
      $this->messenger()->addStatus($this->t('Sitemap type %label has been created.', ['%label' => $this->entity->label()]));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $return;
  }

}
