<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElementBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\webform\Element\WebformCodeMirror;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Utility\WebformDateHelper;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\WebformMessageManagerInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Prepares variables for webform help templates.
 *
 * Default template: webform_help.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - title: Help title.
 *   - content: Help content.
 */
function template_preprocess_webform_help(array &$variables) {
  /** @var \Drupal\webform\WebformHelpManagerInterface $help_manager */
  $help_manager = \Drupal::service('webform.help_manager');

  $help_info = $variables['info'];

  $variables += $help_info;

  $help = [];

  // Content.
  if (is_array($help_info['content'])) {
    $help['content'] = $help_info['content'];
  }
  else {
    $help['content'] = [
      '#markup' => $help_info['content'],
    ];
  }

  // Video.
  $video_info = (isset($help_info['video_id'])) ? $help_manager->getVideo($help_info['video_id']) : $help_info;
  if (isset($video_info['id'])) {
    $help['link'] = $help_manager->buildVideoLink($video_info['id']);
  }

  $variables['help'] = $help;
}

/**
 * Prepares variables for webform templates.
 *
 * Default template: webform.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #action, #method, #attributes, #webform_children.
 */
function template_preprocess_webform(array &$variables) {
  template_preprocess_form($variables);
}

/**
 * Prepares variables for webform actions templates.
 *
 * Default template: webform-actions.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties and buttons.
 *
 * @see \Drupal\webform\WebformSubmissionForm::actionsElement
 * @see \Drupal\webform\WebformSubmissionForm::actions
 */
function template_preprocess_webform_actions(array &$variables) {
  $element = $variables['element'];
  // Buttons include submit, previous, next, and draft.
  foreach (Element::children($element) as $key) {
    $variables[$key] = $element[$key];
  }
}

/**
 * Prepares variables for webform confirmation templates.
 *
 * Default template: webform-confirmation.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform: A webform.
 *   - webform_submission: A webform submission.
 *   - source_entity: A webform submission source entity.
 */
function template_preprocess_webform_confirmation(array &$variables) {
  /** @var \Drupal\webform\WebformInterface $webform */
  $webform = $variables['webform'];
  /** @var \Drupal\Core\Entity\EntityInterface $source_entity */
  $source_entity = $variables['source_entity'];
  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $variables['webform_submission'];

  /** @var \Drupal\webform\WebformMessageManagerInterface $message_manager */
  $message_manager = \Drupal::service('webform.message_manager');
  $message_manager->setWebformSubmission($webform_submission);
  // Must set webform and source entity because webform submission could be
  // NULL.
  $message_manager->setWebform($webform);
  $message_manager->setSourceEntity($source_entity);

  // Assets: Add custom shared and webform specific CSS and JS.
  // @see webform_css_alter()
  // @see webform_js_alter()
  $assets = $webform->getAssets();
  foreach ($assets as $type => $value) {
    if ($value) {
      $variables['#attached']['library'][] = "webform/webform.assets.$type";
      $variables['#attached']['drupalSettings']['webform']['assets'][$type][$webform->id()] = Crypt::hashBase64($value);
    }
  }

  $settings = $webform->getSettings();

  // Set progress.
  if ($webform->getPages() && $settings['wizard_confirmation'] && ($settings['wizard_progress_bar'] || $settings['wizard_progress_pages'] || $settings['wizard_progress_percentage'])) {
    $variables['progress'] = [
      '#theme' => 'webform_progress',
      '#webform' => $webform,
      '#webform_submission' => $webform_submission,
      '#current_page' => 'webform_confirmation',
    ];
  }

  // Set message.
  $variables['message'] = $message_manager->build(WebformMessageManagerInterface::SUBMISSION_CONFIRMATION_MESSAGE);

  // Set attributes.
  $variables['attributes'] = new Attribute($settings['confirmation_attributes']);

  // Set back.
  $variables['back'] = $settings['confirmation_back'];
  $variables['back_label'] = $settings['confirmation_back_label'] ?: \Drupal::config('webform.settings')->get('settings.default_confirmation_back_label');
  $variables['back_attributes'] = new Attribute($settings['confirmation_back_attributes']);

  // Get query string parameters.
  $query = \Drupal::request()->query->all();

  // Add Ajax trigger to back link except for webform with unique limits which
  // break the ajax callback.
  // @see \Drupal\webform\WebformSubmissionForm::getCustomForm
  // @see Drupal.behaviors.webformConfirmationBackAjax (js/webform.ajax.js)
  $is_ajax = (!empty($query['ajax_form'])) ? TRUE : FALSE;
  $is_limit_unique = ($webform  && ($webform->getSetting('limit_total_unique') || $webform->getSetting('limit_user_unique')));
  if (!empty($is_ajax) && !$is_limit_unique) {
    $variables['back_attributes']->addClass('js-webform-confirmation-back-link-ajax');
  }

  // Apply all passed query string parameters to the 'Back to form' link.
  unset($query['webform_id'], $query['ajax_form'], $query['_wrapper_format'], $query['token'], $query['page']);
  $options = ($query) ? ['query' => $query] : [];

  // Set back_url.
  if ($source_entity && $source_entity->hasLinkTemplate('canonical')) {
    $source_entity = \Drupal::service('entity.repository')->getTranslationFromContext($source_entity);
    $variables['back_url'] = $source_entity->toUrl('canonical', $options)->toString();
  }
  elseif ($webform_submission) {
    $source_url = $webform_submission->getSourceUrl();
    $query = $source_url->getOption('query') ?: [];
    unset($query['webform_id'], $query['ajax_form'], $query['_wrapper_format'], $query['token'], $query['page']);
    $source_url->setOption('query', $query);
    $variables['back_url'] = $source_url->toString();
  }
  else {
    $variables['back_url'] = $webform->toUrl('canonical', $options)->toString();
  }

  $webform->invokeHandlers('preprocessConfirmation', $variables);
}

/**
 * Prepares variables for webform submission navigation templates.
 *
 * Default template: webform-submission-navigation.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform_submission: A webform submission.
 *   - rel: Webform submission link template.
 *          (canonical, edit-form, resend-form, html, text, or yaml).
 */
function template_preprocess_webform_submission_navigation(array &$variables) {
  /** @var \Drupal\webform\WebformRequestInterface $request_handler */
  $request_handler = \Drupal::service('webform.request');
  /** @var \Drupal\webform\WebformSubmissionStorageInterface $webform_submission_storage */
  $webform_submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');

  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $variables['webform_submission'];
  $webform = $webform_submission->getWebform();

  // Webform id and title for context.
  $variables['webform_id'] = $webform->id();
  $variables['webform_title'] = $webform->label();

  // Get the route name, parameters, and source entity for the current page.
  // This ensures that the user stays within their current context as they are
  // paging through submission.
  $route_name = \Drupal::routeMatch()->getRouteName();
  $route_parameters = \Drupal::routeMatch()->getRawParameters()->all();
  $source_entity = $request_handler->getCurrentSourceEntity('webform_submission');

  if (strpos(\Drupal::routeMatch()->getRouteName(), 'webform.user.submission') !== FALSE) {
    $account = \Drupal::currentUser();
    $options = ['in_draft' => FALSE];
  }
  else {
    $account = NULL;
    $options = ['in_draft' => NULL];
  }

  if ($previous_submission = $webform_submission_storage->getPreviousSubmission($webform_submission, $source_entity, $account, $options)) {
    $variables['prev_url'] = Url::fromRoute($route_name, ['webform_submission' => $previous_submission->id()] + $route_parameters)->toString();
  }
  if ($next_submission = $webform_submission_storage->getNextSubmission($webform_submission, $source_entity, $account, $options)) {
    $variables['next_url'] = Url::fromRoute($route_name, ['webform_submission' => $next_submission->id()] + $route_parameters)->toString();
  }

  $variables['#attached']['library'][] = 'webform/webform.navigation';

  // Never cache navigation because previous and next submission will change
  // as submissions are added and deleted.
  $variables['#cache'] = ['max-age' => 0];
}

/**
 * Prepares variables for webform submission templates.
 *
 * Default template: webform-submission.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An array of elements to display in view mode.
 *   - webform_submission: The webform submissions object.
 *   - view_mode: View mode; e.g., 'html', 'text', 'table', 'yaml', etc.
 */
function template_preprocess_webform_submission(array &$variables) {
  $variables['view_mode'] = $variables['elements']['#view_mode'];

  $variables['navigation'] = $variables['elements']['navigation'];
  $variables['information'] = $variables['elements']['information'];
  $variables['submission'] = $variables['elements']['submission'];

  $variables['webform_submission'] = $variables['elements']['#webform_submission'];
  if ($variables['webform_submission'] instanceof WebformSubmissionInterface) {
    $variables['webform'] = $variables['webform_submission']->getWebform();
  }
}

/**
 * Prepares variables for webform submission data templates.
 *
 * Default template: webform-submission-data.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the properties of the element.
 *     Properties used: #view_mode, #webform_submission.
 */
function template_preprocess_webform_submission_data(array &$variables) {
  $variables['view_mode'] = $variables['elements']['#view_mode'];
  $variables['webform_submission'] = $variables['elements']['#webform_submission'];
  if ($variables['webform_submission'] instanceof WebformSubmissionInterface) {
    $variables['webform'] = $variables['webform_submission']->getWebform();
  }

  $variables['content'] = [];
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

/**
 * Prepares variables for webform submission information template.
 *
 * Default template: webform-submission-information.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform_submission: A webform submission.
 */
function template_preprocess_webform_submission_information(array &$variables) {
  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $variables['webform_submission'];
  $webform = $webform_submission->getWebform();

  $variables['serial'] = $webform_submission->serial();
  $variables['sid'] = $webform_submission->id();
  $variables['uuid'] = $webform_submission->uuid();
  $variables['is_draft'] = $webform_submission->isDraft() ? t('Yes') : t('No');
  $variables['current_page'] = $webform_submission->getCurrentPageTitle();
  $variables['remote_addr'] = $webform_submission->getRemoteAddr();
  $variables['submitted_by'] = $webform_submission->getOwner()->toLink();
  $variables['webform'] = $webform->toLink();
  $variables['created'] = WebformDateHelper::format($webform_submission->getCreatedTime());
  $variables['completed'] = WebformDateHelper::format($webform_submission->getCompletedTime());
  $variables['changed'] = WebformDateHelper::format($webform_submission->getChangedTime());
  $variables['sticky'] = $webform_submission->isSticky() ? t('Yes') : '';
  $variables['locked'] = $webform_submission->isLocked() ? t('Yes') : '';
  $variables['notes'] = $webform_submission->getNotes();

  // @see \Drupal\Core\Field\Plugin\Field\FieldFormatter\LanguageFormatter::viewValue()
  $languages = \Drupal::languageManager()->getNativeLanguages();
  $langcode = $webform_submission->get('langcode')->value;
  $variables['language'] = isset($languages[$langcode]) ? $languages[$langcode]->getName() : $langcode;

  if ($source_url = $webform_submission->getSourceUrl()) {
    $variables['uri'] = Link::fromTextAndUrl($source_url->setAbsolute(FALSE)->toString(), $source_url);
  }

  $token_operations = ['view', 'update', 'delete'];
  foreach ($token_operations as $token_operation) {
    if ($webform->getSetting('token_' . $token_operation)) {
      $token_url = $webform_submission->getTokenUrl($token_operation);
      $variables['token_' . $token_operation] = Link::fromTextAndUrl($token_url->setAbsolute(FALSE)->toString(), $token_url);
    }
  }

  if (($source_entity = $webform_submission->getSourceEntity()) && $source_entity->hasLinkTemplate('canonical')) {
    $variables['submitted_to'] = $source_entity->toLink();
  }

  $variables['submissions_view'] = FALSE;
  if ($webform->access('submission_view_any')) {
    $variables['submissions_view'] = TRUE;
  }
  elseif ($source_entity) {
    $entity_type = $source_entity->getEntityTypeId();
    if (\Drupal::currentUser()->hasPermission("view webform submissions any $entity_type")) {
      $variables['submissions_view'] = TRUE;
    }
    elseif (\Drupal::currentUser()->hasPermission("view webform submissions own $entity_type")
      && method_exists($source_entity, 'getOwnerId')
      && (int) $source_entity->getOwnerId() === (int) \Drupal::currentUser()->id()
    ) {
      $variables['submissions_view'] = TRUE;
    }
  }

  if ($webform_submission->access('delete')) {
    /** @var \Drupal\webform\WebformRequestInterface $request_handler */
    $request_handler = \Drupal::service('webform.request');

    $current_url = Url::fromRoute('<current>')->toString();
    $source_entity_url = $webform_submission->getSourceUrl()->setAbsolute(FALSE)->toString();

    $route_options = [];
    if ($current_url === $source_entity_url) {
      $base_route_name = 'webform.user.submission.delete';
      $route_options = ['query' => \Drupal::destination()->getAsArray()];
    }
    elseif (strpos(\Drupal::routeMatch()->getRouteName(), 'webform.user.submission') !== FALSE
      || !$webform->access('submission_view_any')) {
      $base_route_name = 'webform.user.submission.delete';
    }
    else {
      $base_route_name = 'webform_submission.delete_form';
    }
    // Append token to delete URL.
    $token = \Drupal::request()->query->get('token');
    if ($webform->getSetting('token_delete') && $token === $webform_submission->getToken()) {
      $route_options['query']['token'] = $token;
    }
    $url = $request_handler->getUrl($webform_submission, $source_entity, $base_route_name, $route_options);
    $variables['delete'] = [
      '#type' => 'link',
      '#title' => t('Delete submission'),
      '#url' => $url,
      '#attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW, ['button', 'button--danger']),
    ];

    WebformDialogHelper::attachLibraries($variables['delete']);
  }
}

/**
 * Prepares variables for webform CodeMirror templates.
 *
 * Default template: webform-codemirror.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - code: The code.
 *   - type: The type of code.
 */
function template_preprocess_webform_codemirror(array &$variables) {
  $variables['mode'] = WebformCodeMirror::getMode($variables['type']);
  if (is_string($variables['code'])) {
    // Encode all HTML entities include tags.
    $variables['code'] = Markup::create(htmlentities($variables['code']));
  }
}

/**
 * Prepares variables for webform element base HTML templates.
 *
 * Default template: webform-element-base-html.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The webform element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *     - multiline: Flag to determine if value spans multiple lines.
 *     - email: Flag to determine if element is for an email.
 */
function template_preprocess_webform_element_base_html(array &$variables) {
  $element = $variables['element'];

  // Set title.
  _template_progress_webform_set_title($variables);

  // Build form (item) element for HTML display.
  // @see form-element.html.twig
  // @see template_preprocess_form_element
  if (empty($variables['options']['email']) && isset($element['#type'])) {
    $type = $element['#type'];

    $attributes = $element['#format_attributes'] ?? [];
    $attributes += ['class' => []];
    // Use wrapper attributes for the id instead of #id,
    // this stops the <label> from having a 'for' attribute.
    $attributes += [
      'id' => $element['#webform_id'],
    ];
    $attributes['class'][] = 'webform-element';
    $attributes['class'][] = 'webform-element-type-' . str_replace('_', '-', $type);

    $variables['item'] = [
      '#type' => 'item',
      '#title' => $variables['title'],
      '#name' => $element['#webform_key'],
      '#wrapper_attributes' => $attributes,
    ];
    if (is_array($variables['value'])) {
      $variables['item']['value'] = $variables['value'];
    }
    else {
      $variables['item']['#markup'] = $variables['value'];
    }
  }
  else {
    $variables['title'] = ['#markup' => $variables['title']];
  }
}

/**
 * Prepares variables for webform element base text templates.
 *
 * Default template: webform-element-base-text.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The webform element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *     - multiline: Flag to determine if value spans multiple lines.
 *     - email: Flag to determine if element is for an email.
 */
function template_preprocess_webform_element_base_text(array &$variables) {
  // Set title.
  _template_progress_webform_set_title($variables, TRUE);
}

/**
 * Set variables title to element #title or #admin_title.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The webform element.
 * @param bool $strip_tags
 *   Remove HTML tags from title.
 */
function _template_progress_webform_set_title(array &$variables, $strip_tags = FALSE) {
  $element = $variables['element'];

  // Set title.
  $variables['title'] = (WebformElementHelper::isTitleDisplayed($element)) ? $element['#title'] : NULL;
  if (empty($variables['title']) && !empty($element['#admin_title'])) {
    $variables['title'] = $element['#admin_title'];
  }

  // Strip all HTML tags from the title.
  if ($strip_tags && !empty($variables['title'])) {
    $variables['title'] = strip_tags($variables['title']);
  }
}

/* ************************************************************************** */
// Preprocess templates.
/* ************************************************************************** */

/**
 * Prepares variables for webform 'wizard' progress templates.
 *
 * Default template: webform-progress.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform: A webform.
 *   - current_page: The current wizard page.
 */
function template_preprocess_webform_progress(array &$variables) {
  /** @var \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager */
  $libraries_manager = \Drupal::service('webform.libraries_manager');

  /** @var \Drupal\webform\WebformInterface $webform */
  $webform = $variables['webform'];
  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $variables['webform_submission'];
  $current_page = $variables['current_page'];
  $operation = $variables['operation'];

  $pages = $variables['pages'] ?: $webform->getPages($operation, $webform_submission);

  $page_keys = array_keys($pages);
  $page_indexes = array_flip($page_keys);
  $current_index = $page_indexes[$current_page];

  $total = count($page_keys);

  $variables['index'] = ($current_index + 1);
  $variables['total'] = $total;

  if ($webform->getSetting('wizard_progress_bar')) {
    $variables['bar'] = [
      '#theme' => ($libraries_manager->isIncluded('progress-tracker')) ? 'webform_progress_tracker' : 'webform_progress_bar',
      '#webform' => $webform,
      '#webform_submission' => $webform_submission,
      '#current_page' => $current_page,
      '#operation' => $operation,
      '#pages' => $variables['pages'],
    ];
  }

  if ($webform->getSetting('wizard_progress_pages')) {
    $variables['summary'] = t('@start of @end', ['@start' => $current_index + 1, '@end' => $total]);
  }

  if ($webform->getSetting('wizard_progress_percentage')) {
    $variables['percentage'] = ($total > 1)
      ? number_format(($current_index / ($total - 1)) * 100, 0) . '%'
      : '0%';
  }
}

/**
 * Prepares variables for webform 'wizard' progress bar templates.
 *
 * Default template: webform-progress-bar.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform: A webform.
 *   - current_page: The current wizard page.
 */
function template_preprocess_webform_progress_bar(array &$variables) {
  _template_preprocess_webform_progress($variables);
}

/**
 * Prepares variables for webform 'wizard' progress tracker templates.
 *
 * Default template: webform-progress-tracker.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform: A webform.
 *   - current_page: The current wizard page.
 */
function template_preprocess_webform_progress_tracker(array &$variables) {
  _template_preprocess_webform_progress($variables);
}

/**
 * Prepares variables for webform 'wizard' progress bar & tracker templates.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform: A webform.
 *   - current_page: The current wizard page.
 */
function _template_preprocess_webform_progress(array &$variables) {
  /** @var \Drupal\webform\WebformInterface $webform */
  $webform = $variables['webform'];
  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $variables['webform_submission'];
  $current_page = $variables['current_page'];
  $operation = $variables['operation'];

  $pages = $variables['pages'] ?: $webform->getPages($operation, $webform_submission);

  $page_keys = array_keys($pages);
  $page_indexes = array_flip($page_keys);
  $current_index = $page_indexes[$current_page];
  $variables['current_index'] = $current_index;

  // Reset the pages variable.
  $variables['progress'] = [];
  foreach ($pages as $key => $page) {
    $variables['progress'][] = [
      'name' => $key,
      'title' => $page['#title'] ?? '',
      'type' => $page['#type'] ?? 'page',
    ];
  }
}

/* ************************************************************************** */
// Element templates.
/* ************************************************************************** */

/**
 * Prepares variables for webform message templates.
 *
 * Default template: webform-message.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #id, #attributes, #children.
 *
 * @see template_preprocess_container()
 */
function template_preprocess_webform_message(array &$variables) {
  $variables['has_parent'] = FALSE;
  $element = $variables['element'];
  // Ensure #attributes is set.
  $element += ['#attributes' => []];

  // Special handling for webform elements.
  if (isset($element['#array_parents'])) {
    // Assign an html ID.
    if (!isset($element['#attributes']['id'])) {
      $element['#attributes']['id'] = $element['#id'];
    }
    $variables['has_parent'] = TRUE;
  }

  $variables['message'] = $element['#message'];
  $variables['attributes'] = $element['#attributes'];
  if (isset($element['#closed'])) {
    $variables['closed'] = $element['#closed'];
  }
}

/**
 * Prepares variables for webform HTML Editor markup templates.
 *
 * Default template: webform-html-editor-markup.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - markup: HTML markup.
 *   - allowed_tags: Allowed tags.
 */
function template_preprocess_webform_html_editor_markup(array &$variables) {
  $variables['content'] = [
    '#markup' => $variables['markup'],
    '#allowed_tags' => $variables['allowed_tags'],
  ];
}

/**
 * Prepares variables for webform horizontal rule templates.
 *
 * Default template: webform-horizontal-rule.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #id, #attributes.
 */
function template_preprocess_webform_horizontal_rule(array &$variables) {
  $element = $variables['element'];

  if (!empty($element['#id'])) {
    $variables['attributes']['id'] = $element['#id'];
  }
}

/**
 * Prepares variables for webform section element templates.
 *
 * Default template: webform-section.html.twig.
 *
 * Copied from: template_preprocess_fieldset()
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #attributes, #children, #description, #id, #title,
 *     #value.
 */
function template_preprocess_webform_section(array &$variables) {
  $element = $variables['element'];
  Element::setAttributes($element, ['id']);
  RenderElementBase::setAttributes($element);
  $variables['attributes'] = $element['#attributes'] ?? [];
  $variables['prefix'] = $element['#field_prefix'] ?? NULL;
  $variables['suffix'] = $element['#field_suffix'] ?? NULL;
  $variables['title_display'] = $element['#title_display'] ?? NULL;
  $variables['title_tag'] = $element['#title_tag'] ?? 'h2';
  $variables['title_attributes'] = $element['#title_attributes'] ?? [];
  $variables['description_display'] = $element['#description_display'] ?? 'before';
  $variables['children'] = $element['#children'];
  $variables['required'] = !empty($element['#required']) ? $element['#required'] : NULL;

  // Allow markup in title.
  if (isset($element['#title']) && $element['#title'] !== '') {
    $variables['title'] = ['#markup' => $element['#title']];
  }

  // Add 'visually-hidden' class to title attributes.
  if ($variables['title_display'] === 'invisible') {
    $variables['title_attributes']['class'][] = 'visually-hidden';
  }
  $variables['title_attributes'] = new Attribute($variables['title_attributes']);

  if (!empty($element['#description'])) {
    $description_id = $element['#attributes']['id'] . '--description';
    $description_attributes['id'] = $description_id;
    $variables['description']['attributes'] = new Attribute($description_attributes);
    $variables['description']['content'] = $element['#description'];

    // Add the description's id to the fieldset aria attributes.
    $variables['attributes']['aria-describedby'] = $description_id;
  }

  // Suppress error messages.
  $variables['errors'] = NULL;

  // Setup description, help, and more.
  _webform_preprocess_element($variables);
}

/* ************************************************************************** */
// Composite templates.
/* ************************************************************************** */

/**
 * Prepares variables for webform composite templates.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 */
function _template_preprocess_webform_composite(array &$variables) {
  $element = $variables['element'];

  // Copy all accessible element children to content.
  foreach (Element::children($element) as $key) {
    if (!isset($element[$key]['#access']) || $element[$key]['#access']) {
      $variables['content'][$key] = $element[$key];
    }
  }

  // Set flexbox variable used for multi column element layout.
  $variables['flexbox'] = $element['#flexbox'] ?? FALSE;
}

/**
 * Prepares variables for composite address templates.
 *
 * Default template: webform-composite-address.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 */
function template_preprocess_webform_composite_address(array &$variables) {
  _template_preprocess_webform_composite($variables);
}

/**
 * Prepares variables for composite contact templates.
 *
 * Default template: webform-composite-contact.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 */
function template_preprocess_webform_composite_contact(array &$variables) {
  _template_preprocess_webform_composite($variables);
}

/**
 * Prepares variables for composite link templates.
 *
 * Default template: webform-composite-link.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 */
function template_preprocess_webform_composite_link(array &$variables) {
  $variables['content'] = $variables['element'];
}

/**
 * Prepares variables for composite location templates.
 *
 * Default template: webform-composite-location.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 */
function template_preprocess_webform_composite_location(array &$variables) {
  $variables['content'] = $variables['element'];
}

/**
 * Prepares variables for composite name templates.
 *
 * Default template: webform-composite-name.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 */
function template_preprocess_webform_composite_name(array &$variables) {
  _template_preprocess_webform_composite($variables);
}

/**
 * Prepares variables for composite telephone templates.
 *
 * Default template: webform-composite-telephone.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 */
function template_preprocess_webform_composite_telephone(array &$variables) {
  _template_preprocess_webform_composite($variables);
}

/* ************************************************************************** */
// Element templates.
/* ************************************************************************** */

/**
 * Prepares variables for webform element help templates.
 *
 * Default template: webform-element-help.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The webform element.
 *   - help: The help content.
 *   - attributes: The help attributes.
 */
function template_preprocess_webform_element_help(array &$variables) {
  $attributes = $variables['attributes'] ?? [];
  $attributes['class'][] = 'webform-element-help';
  $attributes['class'][] = 'js-webform-element-help';
  $attributes['role'] = 'tooltip';
  $attributes['tabindex'] = '0';
  $attributes['aria-label'] = $variables['help_title'] ?: t('Help tooltip');

  $content = (is_array($variables['help'])) ? \Drupal::service('renderer')->render($variables['help']) : $variables['help'];

  $help = '';
  if (!empty($variables['help_title'])) {
    $help .= '<div class="webform-element-help--title">' . WebformHtmlEditor::stripTags($variables['help_title']) . '</div>';
  }
  $help .= '<div class="webform-element-help--content">' . WebformHtmlEditor::stripTags($content) . '</div>';
  $attributes['data-webform-help'] = $help;

  $variables['attributes'] = new Attribute($attributes);
}

/**
 * Prepares variables for webform element more templates.
 *
 * Default template: webform-element-more.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The webform element.
 */
function template_preprocess_webform_element_more(array &$variables) {
  if (empty($variables['more_title'])) {
    $variables['more_title'] = \Drupal::config('webform.settings')->get('element.default_more_title');
  }

  if (!is_array($variables['more'])) {
    $variables['more'] = [
      '#markup' => $variables['more'],
      '#allowed_tags' => WebformHtmlEditor::getAllowedTags(),
    ];
  }

  $variables['attributes'] = new Attribute($variables['attributes']);

  // Make sure there is a unique id.
  if (empty($variables['id'])) {
    $variables['id'] = Html::getUniqueId('webform-element-more');
  }

  // Make sure attributes id is set.
  if (!isset($variables['attributes']['id'])) {
    $variables['attributes']['id'] = $variables['id'];
  }
}

/**
 * Prepares variables for webform element managed file templates.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The webform element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *   - file: The element's File object.
 */
function template_preprocess_webform_element_managed_file(array &$variables) {
  if (!empty($variables['file'])) {
    /** @var \Drupal\file\FileInterface $file */
    $file = $variables['file'];
    $variables['uri'] = $file->createFileUrl(FALSE);
    $variables['extension'] = strtolower(pathinfo($variables['uri'], PATHINFO_EXTENSION));
    $variables['type'] = \Drupal::service('file.mime_type.guesser')->guessMimeType($variables['uri']);
    $variables['file_link'] = [
      '#theme' => 'file_link',
      '#file' => $file,
    ];
  }
}

/**
 * Prepares variables for webform element audio file templates.
 *
 * Default template: webform-element-audio-file.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The webform element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *   - file: The element's File object.
 */
function template_preprocess_webform_element_audio_file(array &$variables) {
  template_preprocess_webform_element_managed_file($variables);
}

/**
 * Prepares variables for webform element document file templates.
 *
 * Default template: webform-element-document-file.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The webform element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *   - file: The element's File object.
 */
function template_preprocess_webform_element_document_file(array &$variables) {
  template_preprocess_webform_element_managed_file($variables);
}

/**
 * Prepares variables for webform element image file templates.
 *
 * Default template: webform-element-image-file.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The webform element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *   - file: The element's File object.
 *   - style_name: An image style name.
 *   - format: Image formatting (link or modal)
 */
function template_preprocess_webform_element_image_file(array &$variables) {
  if (!empty($variables['file'])) {
    /** @var \Drupal\file\FileInterface $file */
    $file = $variables['file'];

    $style_name = $variables['style_name'];
    $format = $variables['format'];

    $uri = $file->getFileUri();
    $url = \Drupal::service('file_url_generator')->generate($uri)->setAbsolute();

    $extension = pathinfo($uri, PATHINFO_EXTENSION);
    $is_image = in_array($extension, ['gif', 'png', 'jpg', 'jpeg']);

    // Build image.
    if ($is_image && \Drupal::moduleHandler()->moduleExists('image') && $style_name && ImageStyle::load($style_name)) {
      $variables['image'] = [
        '#theme' => 'image_style',
        '#style_name' => $variables['style_name'],
      ];
    }
    else {
      // Note: The 'image' template uses root-relative paths.
      // The 'image' is preprocessed to use absolute URLs.
      // @see webform_preprocess_image().
      $variables['image'] = [
        '#theme' => 'image',
      ];
    }
    $variables['image'] += [
      '#uri' => $uri,
      '#attributes' => [
        'class' => ['webform-image-file'],
        'alt' => $file->getFilename(),
        'title' => $file->getFilename(),
      ],
    ];

    // For the Results table always display the file name as a tooltip.
    if (strpos(\Drupal::routeMatch()->getRouteName(), 'webform.results_submissions') !== FALSE) {
      $variables['attached']['library'][] = 'webform/webform.tooltip';
      $variables['image']['#attributes']['class'][] = 'js-webform-tooltip-link';
    }

    // Wrap 'image' in a link/modal.
    if ($format && $format !== 'image') {
      $variables['image'] = [
        '#type' => 'link',
        '#title' => $variables['image'],
        '#url' => $url,
      ];
      switch ($format) {
        case 'modal':
          $variables['image'] += [
            '#attributes' => ['class' => ['js-webform-image-file-modal', 'webform-image-file-modal']],
            '#attached' => ['library' => ['webform/webform.element.image_file.modal']],
          ];
          break;

        case 'link':
          $variables['image'] += ['#attributes' => ['class' => ['webform-image-file-link']]];
          break;
      }
    }

  }
}

/**
 * Prepares variables for webform element video file templates.
 *
 * Default template: webform-element-video-file.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The webform element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *   - file: The element's File object.
 */
function template_preprocess_webform_element_video_file(array &$variables) {
  template_preprocess_webform_element_managed_file($variables);
}
