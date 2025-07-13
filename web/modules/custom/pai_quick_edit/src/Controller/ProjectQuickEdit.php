<?php

namespace Drupal\pai_quick_edit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilder;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class MediaQuickEdit. Simple controller to quickly edit Media entities.
 */
class ProjectQuickEdit extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\node\NodeStorage
   */
  protected $nodeStorage;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Form Builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * Constructor.
   */
  public function __construct(ContainerInterface $container, EntityTypeManagerInterface $entity_type_manager, EntityFieldManager $entity_field_manager, FormBuilder $form_builder) {
    $this->entityTypeManager = $entity_type_manager;
    $this->nodeStorage = $this->entityTypeManager->getStorage('node');
    // $this->fileStorage = $this->entityTypeManager->getStorage('file');
    $this->entityFieldManager = $entity_field_manager;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('form_builder')
    );
  }

  /**
   * Callback for quick edit project page.
   */
  public function quickEditProject(Request $request) {
    $params = $request->query->all();

    $fields = [
      'field_display_title',
      'field_short_description',
      'field_project_number',
      'field_owner',
      'field_sale_price',
      'field_labor',
    ];

    $bundle_fields = [];

    $build = [
      'form' => $this->formBuilder->getForm('Drupal\pai_quick_edit\Form\ProjectQuickEditForm'),
      'table' => [
        '#type' => 'table',
        '#header' => ['nid', 'type', 'title'],
        '#rows' => [],
        '#footer' => [],
        '#sticky' => TRUE,
        '#empty' => "There are no project entities to edit.",
        '#attributes' => [
          'data-api-slug' => 'project',
        ],
        '#attached' => [
          'library' => ['pai_quick_edit/quick-edit'],
        ],
      ],
    ];

    $header = &$build['table']['#header'];

    foreach ($fields as $f) {
      $header[] = $f;
    }

    // Build query and get list.
    $q = $this->nodeStorage->getQuery();
    $q->accessCheck(TRUE);

    if (isset($params['title'])) {
      $q->condition('title', '%%' . $params['title'] . '%%', 'LIKE');
    }

    if (isset($params['type'])) {
      $q->condition('type', $params['type'], "IN");
    }
    else {
      $params['type'] = ['project', 'project_series'];
      $q->condition('type', ['project', 'project_series'], "IN");
    }

    $sort = $params['sort'] ?? 'nid';
    $sort_order = $params['sort_order'] ?? 'ASC';

    $q->sort($sort, $sort_order);

    $q->pager(25);

    $r = $q->execute();

    $nodes = $this->nodeStorage->loadMultiple($r);

    // Get field defs.
    foreach ($params['type'] as $b) {
      $bundle_fields[$b] = $this->entityFieldManager->getFieldDefinitions('node', $b);
    }

    foreach ($nodes as $node) {
      $row = [];

      // Mid.
      $row[] = ['data' => $node->id()];

      // Type/Bundle.
      $row[] = ['data' => $node->bundle()];

      // Name.
      $row[] = [
        'data' => [
          '#type' => "textfield",
          '#value' => $node->title->value,
          '#attributes' => [
            'class' => ['qe-element'],
            'data-entity-id' => $node->id(),
            'data-field' => 'name',
          ],
        ],
      ];

      foreach ($fields as $f) {
        if ($node->hasField($f)) {
          $field_def = ($bundle_fields[$node->bundle()][$f]) ?? [];

          $form_field_type = $this->getFormFieldType($field_def->getType());

          $field_value = current($node->{$f}->getValue());
          $cell = [
            'data' => [
              [
                '#type' => $form_field_type,
                '#value' => $field_value['value'] ?? "",
                '#attributes' => [
                  'class' => ['qe-element'],
                  'data-entity-id' => $node->id(),
                  'data-field' => $f,
                  'data-property' => 'value',
                ],
              ],
            ],
          ];

          // Add secondary fields like "format".
          if ($adds = $this->getSecondaryFormFields($f, $field_def, $node)) {
            $cell['data'][] = $adds;
          }

          $row[] = $cell;
        }
        else {
          $row[] = [];
        }
      }

      $build['table']['#rows'][] = $row;
    }

    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

  /**
   * Callback for quick edit media api to edit the media entity.
   */
  public function quickEditProjectApi(Request $request) {
    $postData = json_decode($request->getContent());
    $node = $this->nodeStorage->load($postData->id);

    $field_value = $node->{$postData->field}->getValue();
    $field_value[0][($postData->property ?? "value")] = $postData->value;
    $node->set($postData->field, $field_value);

    $node->save();

    return new JsonResponse($postData);
  }

  /**
   * Internal func to get the form type from the given field type.
   */
  private function getFormFieldType(string $field_type) {
    $form_field_type = 'textfield';

    switch ($field_type) {
      case 'text_long':
        $form_field_type = 'textarea';

        break;
    }

    return $form_field_type;
  }

  private function getSecondaryFormFields($f, $field_def, $node) {
    $adds = [];
    switch ($field_def->getType()) {
      // Need to add format here.
      case 'text':
      case 'text_long':

        $field_value = current($node->{$f}->getValue());
        $field_settings = $field_def->getSettings();

        // Text Format options.
        $formats = filter_formats();
        $format_options = [];

        foreach ($formats as $f_id => $format_obj) {
          $format_options[$f_id] = $format_obj->label();
        }

        if (!empty($field_settings['allowed_formats'])) {
          $format_options = array_intersect_key($format_options, array_flip($field_settings['allowed_formats']));
        }

        $format_options = ['' => '-- Select --'] + $format_options;

        $item = [
          '#type' => 'select',
          // '#title' => 'Format',
          '#value' => $field_value['format'] ?? "",
          '#options' => $format_options,
          '#attributes' => [
            'class' => ['qe-element'],
            'data-entity-id' => $node->id(),
            'data-field' => $f,
            'data-property' => 'format',
          ],
        ];

        $adds = $item;
        break;
    }

    return $adds;
  }

}
