<?php

/**
 * @file
 * Fields UI Manage forms functions (display and fields).
 */

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityDisplayBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\field_group\FieldgroupUi;
use Drupal\field_group\FormatterHelper;
use Drupal\field_ui\Form\EntityDisplayFormBase;

/**
 * Getting form parameters.
 *
 * Helper function to get the form parameters to use while
 * building the fields and display overview form.
 *
 * @param array $form
 *   Form.
 * @param \Drupal\Core\Entity\EntityDisplayBase $display
 *   Display object.
 *
 * @return object
 *   Form parameters.
 */
function field_group_field_ui_form_params(array $form, EntityDisplayBase $display) {

  $params = new stdClass();
  $params->entity_type = $display->getTargetEntityTypeId();
  $params->bundle = $display->getTargetBundle();
  $params->mode = $display->getMode();
  $params->context = field_group_get_context_from_display($display);

  $params->groups = [];
  $params->groups = field_group_info_groups($params->entity_type, $params->bundle, $params->context, $params->mode);

  // Gather parenting data.
  $params->parents = [];
  foreach ($params->groups as $name => $group) {
    if (!empty($group->children)) {
      foreach ($group->children as $child) {
        // Field UI js sometimes can trigger an endless loop. Check if the
        // parent of this field is not a child.
        if ($child !== $group->parent_name) {
          $params->parents[$child] = $name;
        }
      }
    }
  }

  // Get possible regions.
  // @todo Remove the field layout part when it's remove from in core.
  // see https://www.drupal.org/project/field_group/issues/3086019
  $ds_info = $display->getThirdPartySettings('ds');
  $field_layout_info = $display->getThirdPartySettings('field_layout');
  /** @var \Drupal\Core\Layout\LayoutDefinition $layout */
  if (!empty($field_layout_info) && isset($field_layout_info['id'])) {
    $layout = \Drupal::service('plugin.manager.core.layout')->getDefinition($field_layout_info['id']);
    $params->available_regions = $layout->getRegionNames();
    $params->default_region = $layout->getDefaultRegion() ?: 'hidden';
  }
  elseif (!empty($ds_info['layout']['id'])) {
    $layout = \Drupal::service('plugin.manager.core.layout')->getDefinition($ds_info['layout']['id']);
    $params->available_regions = $layout->getRegionNames();
    // Hidden is an available region too, as weird as it may seems.
    $params->available_regions[] = 'hidden';
    $params->default_region = $layout->getDefaultRegion() ?: 'hidden';
  }
  else {
    $params->available_regions = ['content', 'hidden'];
    $params->default_region = 'hidden';
  }

  return $params;
}

/**
 * Helper function to get context from entity display.
 *
 * @param \Drupal\Core\Entity\EntityDisplayBase $display
 *   Display.
 *
 * @return string
 *   Context.
 */
function field_group_get_context_from_display(EntityDisplayBase $display) {
  if ($display instanceof EntityFormDisplayInterface) {
    return 'form';
  }
  elseif ($display instanceof EntityViewDisplayInterface) {
    return 'view';
  }

  throw new LogicException('Unknown display object.');
}

/**
 * Function to alter the display overview screens.
 */
function field_group_field_ui_display_form_alter(&$form, FormStateInterface $form_state) {
  // Only start altering the form if we need to.
  if (empty($form['#fields']) && empty($form['#extra'])) {
    return;
  }

  $entity_display_form = $form_state->getBuildInfo()['callback_object'];
  if (!$entity_display_form instanceof EntityDisplayFormBase) {
    throw new InvalidArgumentException('Unknown callback object.');
  }

  $display = $entity_display_form->getEntity();

  $params = field_group_field_ui_form_params($form, $display);
  $form['#fieldgroups'] = array_keys($params->groups);
  $form['#context'] = $display;

  $table = &$form['fields'];
  $form_state_values = $form_state->getValues();
  $field_group_form_state = $form_state->get('field_group');

  if ($field_group_form_state == NULL) {
    $field_group_form_state = $params->groups;
  }

  $table['#parent_options'] = [];

  // Extend available parenting options.
  foreach ($field_group_form_state as $name => $group) {
    $table['#parent_options'][$name] = $group->label;
  }

  // Update existing rows accordingly to the parents.
  foreach (Element::children($table) as $name) {
    $table[$name]['parent_wrapper']['parent']['#options'] = $table['#parent_options'];
    // Inherit the value of the parent when default value is empty.
    if (empty($table[$name]['parent_wrapper']['parent']['#default_value'])) {
      $table[$name]['parent_wrapper']['parent']['#default_value'] = $params->parents[$name] ?? '';
    }
  }

  $formatter_options = FormatterHelper::formatterOptions($params->context);

  $refresh_rows = $form_state_values['refresh_rows'] ?? ($form_state->getUserInput()['refresh_rows'] ?? NULL);

  // Create the group rows and check actions.
  foreach ($form['#fieldgroups'] as $name) {
    $group = &$field_group_form_state[$name];

    // Check the currently selected formatter, and merge persisted values for
    // formatter settings for the group.
    // Firstly updating all fields before creating form elements.
    if (isset($refresh_rows) && $refresh_rows == $name) {
      $settings = $form_state_values['fields'][$name] ?? ($form_state->getUserInput()['fields'][$name] ?? NULL);
      if (array_key_exists('settings_edit', $settings)) {
        $group = $field_group_form_state[$name];
      }
      field_group_formatter_row_update($group, $settings);
    }

    // Save the group when the configuration is submitted.
    if (!empty($form_state_values[$name . '_plugin_settings_update'])) {
      field_group_formatter_settings_update($group, $form_state_values['fields'][$name]);
    }
    // After all updates are finished, let the form_state know.
    $field_group_form_state[$name] = $group;

    $settings = field_group_format_settings_form($group, $form, $form_state);

    $id = strtr($name, '_', '-');
    // A group cannot be selected as its own parent.
    $parent_options = $table['#parent_options'];
    $region = isset($group->region) && in_array($group->region, $params->available_regions) ? $group->region : $params->default_region;
    unset($parent_options[$name]);
    $table[$name] = [
      '#attributes' => ['class' => ['draggable', 'field-group'], 'id' => $id],
      '#row_type' => 'group',
      '#region_callback' => 'field_group_display_overview_row_region',
      '#js_settings' => ['rowHandler' => 'group'],
      'human_name' => [
        '#markup' => $group->label,
        '#prefix' => '<span class="group-label">',
        '#suffix' => '</span>',
      ],
      'machine_name' => NULL,
      'weight' => [
        '#type' => 'textfield',
        '#default_value' => $group->weight,
        '#size' => 3,
        '#attributes' => ['class' => ['field-weight']],
      ],
      'parent_wrapper' => [
        'parent' => [
          '#type' => 'select',
          '#options' => $parent_options,
          '#empty_value' => '',
          '#default_value' => $params->parents[$name] ?? '',
          '#attributes' => ['class' => ['field-parent']],
          '#parents' => ['fields', $name, 'parent'],
        ],
        'hidden_name' => [
          '#type' => 'hidden',
          '#default_value' => $name,
          '#attributes' => ['class' => ['field-name']],
        ],
      ],
      'region' => [
        '#type' => 'select',
        '#options' => $entity_display_form->getRegionOptions(),
        '#default_value' => $region,
        '#attributes' => ['class' => ['field-region']],
      ],
    ];

    // In newer versions of Drupal Core, a machine name column is added.
    // @todo Remove this condition once 11.1 is oldest supported Drupal Core.
    $header_column_classes = array_column($table['#header'], 'class');
    if (!empty($header_column_classes)) {
      foreach ($header_column_classes as $classes) {
        if (in_array('machine-name', $classes)) {
          $table[$name]['machine_name'] = [
            '#markup' => $name,
            '#attributes' => ['class' => ['machine-name']],
          ];
          break;
        }
      }
    }

    // For view settings. Add a spacer cell. Can't use colspan because of JS.
    if ($params->context == 'view') {
      $table[$name] += [
        'spacer' => [
          '#markup' => '&nbsp;',
        ],
      ];
    }

    $table[$name] += [
      'format' => [
        'type' => [
          '#type' => 'select',
          '#options' => $formatter_options,
          '#default_value' => $group->format_type,
          '#attributes' => ['class' => ['field-group-type']],
        ],
      ],
    ];

    $base_button = [
      '#submit' => [
        [$form_state->getBuildInfo()['callback_object'], 'multistepSubmit'],
      ],
      '#ajax' => [
        'callback' => [
          $form_state->getBuildInfo()['callback_object'],
          'multistepAjax',
        ],
        'wrapper' => 'field-display-overview-wrapper',
        'effect' => 'fade',
      ],
      '#field_name' => $name,
    ];

    if ($form_state->get('plugin_settings_edit') == $name) {
      $table[$name]['format']['#cell_attributes'] = ['colspan' => 2];
      $table[$name]['format']['format_settings'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['field-plugin-settings-edit-form']],
        '#parents' => ['fields', $name, 'settings_edit_form'],
        '#weight' => -5,
        'label' => [
          '#markup' => t('Field group format:') . ' <span class="formatter-name">' . $group->format_type . '</span>',
        ],
        // Create a settings form where hooks can pick in.
        'settings' => $settings,
        'actions' => [
          '#type' => 'actions',
          'save_settings' => $base_button + [
            '#type' => 'submit',
            '#name' => $name . '_plugin_settings_update',
            '#value' => t('Update'),
            '#op' => 'update',
          ],
          'cancel_settings' => $base_button + [
            '#type' => 'submit',
            '#name' => $name . '_plugin_settings_cancel',
            '#value' => t('Cancel'),
            '#op' => 'cancel',
            // Do not check errors for the 'Cancel' button.
            '#limit_validation_errors' => [],
          ],
        ],
      ];
      $table[$name]['#attributes']['class'][] = 'field-formatter-settings-editing';
      $table[$name]['format']['type']['#wrapper_attributes']['class'] = ['visually-hidden'];
    }
    else {
      // After saving, the settings are updated here as well. First we create
      // the element for the table cell.
      $table[$name]['settings_summary'] = ['#markup' => ''];
      if (!empty($group->format_settings)) {
        $table[$name]['settings_summary'] = field_group_format_settings_summary($name, $group);
      }
      // Add the configure button.
      $table[$name]['settings_edit'] = $base_button + [
        '#type' => 'image_button',
        '#name' => $name . '_group_settings_edit',
        '#src' => 'core/misc/icons/787878/cog.svg',
        '#attributes' => [
          'class' => ['field-plugin-settings-edit'],
          'alt' => t('Edit'),
        ],
        '#op' => 'edit',
        // Do not check errors for the 'Edit' button, but make sure we get
        // the value of the 'plugin type' select.
        '#limit_validation_errors' => [['fields', $name, 'type']],
        '#prefix' => '<div class="field-plugin-settings-edit-wrapper">',
        '#suffix' => '</div>',
      ];

      $delete_route = FieldgroupUi::getDeleteRoute($group);

      $table[$name]['settings_edit']['#suffix'] .= Link::fromTextAndUrl(t('delete'), $delete_route)->toString();
    }

    $form_state->set('field_group', $field_group_form_state);

  }

  // Additional row: add new group.
  $parent_options = $table['#parent_options'];

  $form['#attached']['library'][] = 'field_group/field_ui';

  array_unshift($form['actions']['submit']['#submit'], 'field_group_field_overview_submit');

  // Create the settings for fieldgroup as vertical tabs (merged with DS).
  field_group_field_ui_create_vertical_tabs($form, $form_state, $params);

  // Show a warning if the user has not set up required containers.
  if ($form['#fieldgroups']) {

    $parent_requirements = [
      'accordion-item' => [
        'parent' => 'accordion',
        'message' => 'Each Accordion item element needs to have a parent Accordion group element.',
      ],
    ];

    // On display overview tabs need to be checked.
    if (field_group_get_context_from_display($display) == 'view') {
      $parent_requirements['tab'] = [
        'parent' => 'tabs',
        'message' => 'Each tab element needs to have a parent tabs group element.',
      ];
    }

    foreach ($form['#fieldgroups'] as $group_name) {
      $group_check = field_group_load_field_group($group_name, $params->entity_type, $params->bundle, $params->context, $params->mode);
      if (isset($parent_requirements[$group_check->format_type])) {
        if (!$group_check->parent_name || field_group_load_field_group($group_check->parent_name, $params->entity_type, $params->bundle, $params->context, $params->mode)->format_type != $parent_requirements[$group_check->format_type]['parent']) {
          \Drupal::messenger()->addMessage($parent_requirements[$group_check->format_type]['message'], 'warning', FALSE);
        }
      }
    }
  }
}

/**
 * Create vertical tabs.
 */
function field_group_field_ui_create_vertical_tabs(&$form, &$form_state, $params) {

  $form_state->set('field_group_params', $params);

  $existing_group_config = \Drupal::configFactory()->listAll('field_group.' . $params->entity_type . '.' . $params->bundle);
  $displays = [];
  foreach ($existing_group_config as $config) {
    $group = \Drupal::config($config)->get();
    if ($group['context'] == $params->context && $group['mode'] == $params->mode) {
      continue;
    }
    $displays[$group['context'] . '.' . $group['mode']] = $group['context'] . ':' . $group['mode'];
  }

  // No displays to clone.
  if (empty($displays)) {
    return;
  }

  // Add additional settings vertical tab.
  if (!isset($form['additional_settings'])) {
    $form['additional_settings'] = [
      '#type' => 'vertical_tabs',
      '#theme_wrappers' => ['vertical_tabs'],
      '#prefix' => '<div>',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];
  }

  // Add extra guidelines for webmaster.
  $form['field_group'] = [
    '#type' => 'details',
    '#group' => 'additional_settings',
    '#title' => t('Fieldgroups'),
    '#description' => t('<p class="fieldgroup-help">Fields can be dragged into groups with unlimited nesting. Each fieldgroup format comes with a configuration form, specific for that format type.<br />Note that some formats come in pair. These types have a html wrapper to nest its fieldgroup children. E.g. Place accordion items into the accordion, vertical tabs in vertical tab group and horizontal tabs in the horizontal tab group. There is one exception to this rule, you can use a vertical tab without a wrapper when the additional settings tabs are available. E.g. node forms.</p>'),
    '#collapsible' => TRUE,
    '#open' => TRUE,
  ];
  $form['field_group']['fieldgroup_clone'] = [
    '#title' => t('Select source display'),
    '#description' => t('Clone fieldgroups from selected display to the current display'),
    '#type' => 'select',
    '#options' => $displays,
    '#default_value' => 'none',
  ];
  $form['field_group']['fieldgroup_submit'] = [
    '#type' => 'submit',
    '#value' => t('Clone'),
    '#validate' => ['field_group_field_ui_clone_field_groups_validate'],
    '#submit' => ['field_group_field_ui_clone_field_groups'],
  ];

}

/**
 * Returns the region to which a row in the 'Manage display' screen belongs.
 *
 * @param array $row
 *   A field or field_group row.
 *
 * @return string
 *   The current region.
 */
function field_group_display_overview_row_region(array $row) {
  // We already cleaned region when building the form.
  return $row['region']['#value'];
}

/**
 * Submit handler for the overview screens.
 *
 * @param array $form
 *   The complete form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The state of the form.
 */
function field_group_field_overview_submit(array $form, FormStateInterface $form_state) {
  $form_values = $form_state->getValue('fields');

  /**
   * @var \Drupal\Core\Entity\EntityDisplayBase $display
   */
  $display = $form['#context'];
  $manager = Drupal::service('plugin.manager.field_group.formatters');

  $entity_type = $display->get('targetEntityType');
  $bundle = $display->get('bundle');
  $mode = $display->get('mode');
  $context = field_group_get_context_from_display($display);

  // Load field layout info.
  $field_group_params = $form_state->get('field_group_params');
  $layout_regions = $field_group_params->available_regions;
  $default_region = $field_group_params->default_region;

  // Collect children.
  $children = array_fill_keys($form['#fieldgroups'], []);
  foreach ($form_values as $name => $value) {
    if (!empty($value['parent'])) {
      $children[$value['parent']][$name] = $name;
    }
  }

  // Update existing groups.
  $groups = field_group_info_groups($entity_type, $bundle, $context, $mode);
  $field_group_form_state = $form_state->get('field_group');
  if (!empty($field_group_form_state)) {
    foreach ($form['#fieldgroups'] as $group_name) {

      // Only save updated groups.
      if (!isset($field_group_form_state[$group_name])) {
        continue;
      }

      $group = $groups[$group_name];
      $group->label = $field_group_form_state[$group_name]->label;

      // Sometimes field UI freaks a bit if people drag to fast when switching
      // nested values which results in an endless loop, so cleanup first.
      // unset($children[$group_name][$form_values[$group_name]['parent']]);.
      $group->children = array_keys($children[$group_name]);
      $group->parent_name = $form_values[$group_name]['parent'];
      $group->weight = $form_values[$group_name]['weight'];

      // If region is changed, make sure the group ends up in an existing
      // region.
      $group->region = !in_array($form_values[$group_name]['region'], $layout_regions) ? $default_region : $form_values[$group_name]['region'];

      $old_format_type = $group->format_type;
      $group->format_type = $form_values[$group_name]['format']['type'] ?? 'visible';
      if (isset($field_group_form_state[$group_name]->format_settings)) {
        $group->format_settings = $field_group_form_state[$group_name]->format_settings;
      }

      // If the format type is changed, make sure we have all required format
      // settings.
      if ($group->format_type != $old_format_type) {
        $group->format_settings += $manager->getDefaultSettings($group->format_type, $context);
      }

      /** @var \Drupal\Core\Entity\EntityFormInterface $entity_form */
      $entity_form = $form_state->getFormObject();

      /** @var \Drupal\Core\Entity\Display\EntityDisplayInterface $display */
      $display = $entity_form->getEntity();

      field_group_group_save($group, $display);
    }
  }

  \Drupal::cache()->invalidate('field_groups');
}

/**
 * Creates a form for field_group formatters.
 *
 * @param object $group
 *   The FieldGroup object.
 * @param array $form
 *   Nested array of form elements that comprise the form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The state of the form.
 */
function field_group_format_settings_form(&$group, array $form, FormStateInterface $form_state) {
  $manager = \Drupal::service('plugin.manager.field_group.formatters');
  $plugin = $manager->getInstance([
    'format_type' => $group->format_type,
    'configuration' => [
      'label' => $group->label,
      'settings' => $group->format_settings,
    ],
    'group' => $group,
  ]);

  if ($plugin) {
    return $plugin->settingsForm($form, $form_state);
  }

  return [];
}

/**
 * Update callback.
 *
 * Update the row so that the group variables are updated.
 * The rendering of the elements needs the updated defaults.
 *
 * @param object $group
 *   The group object.
 * @param array $settings
 *   Configuration settings.
 */
function field_group_formatter_row_update(&$group, array $settings) {
  // If the row has changed formatter type, update the group object.
  if (!empty($settings['format']['type']) && $settings['format']['type'] != $group->format_type) {
    $group->format_type = $settings['format']['type'];
    field_group_formatter_settings_update($group, $settings);
  }
}

/**
 * Update handler for field_group configuration settings.
 *
 * @param object $group
 *   The group object.
 * @param array $settings
 *   Configuration settings.
 */
function field_group_formatter_settings_update(&$group, array $settings) {

  // For format changes we load the defaults.
  if (empty($settings['settings_edit_form']['settings'])) {
    $group->format_settings = Drupal::service('plugin.manager.field_group.formatters')->getDefaultSettings($group->format_type, $group->context);
  }
  else {
    $group->format_type = $settings['format']['type'];
    $group->label = $settings['settings_edit_form']['settings']['label'];
    $group->format_settings = $settings['settings_edit_form']['settings'];
  }
}

/**
 * Creates a summary for the field format configuration summary.
 *
 * @param string $group_name
 *   The name of the group.
 * @param object $group
 *   The group object.
 *
 * @return array
 *   Render array.
 */
function field_group_format_settings_summary($group_name, $group) {

  $manager = \Drupal::service('plugin.manager.field_group.formatters');
  $plugin = $manager->getInstance([
    'format_type' => $group->format_type,
    'configuration' => [
      'label' => $group->label,
      'settings' => $group->format_settings,
    ],
    'group' => $group,
  ]);

  $summary = !empty($plugin) ? $plugin->settingsSummary() : [];

  return [
    '#markup' => '<div class="field-plugin-summary">' . implode('<br />', $summary) . '</div>',
    '#cell_attributes' => ['class' => ['field-plugin-summary-cell']],
  ];
}

/**
 * Validate handler.
 *
 * Validate when saving existing fieldgroups from one view mode or form to
 * another.
 */
function field_group_field_ui_clone_field_groups_validate($form, FormStateInterface $form_state) {

  $form_state_values = $form_state->getValues();
  $field_group_params = $form_state->get('field_group_params');

  [$context, $mode] = explode('.', $form_state_values['fieldgroup_clone']);
  $source_groups = field_group_info_groups($field_group_params->entity_type, $field_group_params->bundle, $context, $mode);

  // Check for types are not known in current mode.
  if ($field_group_params->context != 'form') {
    $non_existing_types = [];
  }
  else {
    $non_existing_types = ['html_element'];
  }

  foreach ($source_groups as $key => $group) {
    if (in_array($group->format_type, $non_existing_types)) {
      unset($source_groups[$key]);
      \Drupal::messenger()->addMessage(t('Skipping @group because this type does not exist in current mode', ['@group' => $group->label]), 'warning');
    }
  }

  if (empty($source_groups)) {
    // Report error found with selection.
    $form_state->setErrorByName('additional_settings][fieldgroup_clone', t('No field groups were found in selected view mode.'));
    return;
  }

  $form_state->set('#source_groups', $source_groups);

}

/**
 * Submit handler.
 *
 * Saving existing fieldgroups from one view mode or form to another.
 */
function field_group_field_ui_clone_field_groups($form, FormStateInterface $form_state) {

  $fields = array_keys($form_state->getValue('fields'));
  $source_groups = $form_state->get('#source_groups');
  if ($source_groups) {

    $field_group_params = $form_state->get('field_group_params');
    foreach ($source_groups as $source_group) {
      if (in_array($source_group->group_name, $fields)) {
        \Drupal::messenger()->addMessage(t('Fieldgroup @group is not cloned since a group already exists with the same name.', ['@group' => $source_group->group_name]), 'warning');
        continue;
      }

      $source_group->context = $field_group_params->context;
      $source_group->mode = $field_group_params->mode;
      $source_group->children = [];
      field_group_group_save($source_group);

      \Drupal::messenger()->addMessage(t('Fieldgroup @group cloned successfully.', ['@group' => $source_group->group_name]));

    }
  }

}
