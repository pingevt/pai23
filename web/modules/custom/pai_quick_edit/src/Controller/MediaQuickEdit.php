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
class MediaQuickEdit extends ControllerBase implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\media\MediaStorage
   */
  protected $mediaStorage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\file\FileStorage
   */
  protected $fileStorage;

  /**
   * Form Builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManager $entity_field_manager, FormBuilder $form_builder) {
    $this->entityTypeManager = $entity_type_manager;
    $this->mediaStorage = $this->entityTypeManager->getStorage('media');
    $this->fileStorage = $this->entityTypeManager->getStorage('file');
    $this->entityFieldManager = $entity_field_manager;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('form_builder')
    );
  }

  /**
   * Callback for quick edit media page.
   */
  public function quickEditMedia(Request $request) {
    $params = $request->query->all();

    $fields = [
      'field_media_credit',
      'field_media_caption',
    ];

    $bundle_fields = [];

    $build = [
      'form' => $this->formBuilder->getForm('Drupal\pai_quick_edit\Form\MediaQuickEditForm'),
      'table' => [
        '#type' => 'table',
        '#header' => ['mid', 'Item', 'name', 'alt', 'credits', 'caption'],
        '#rows' => [],
        '#footer' => [],
        '#sticky' => TRUE,
        '#empty' => "There are no media entities to edit.",
        '#attributes' => [
          'data-api-slug' => 'media',
        ],
        '#attached' => [
          'library' => ['pai_quick_edit/quick-edit'],
        ],
      ],
    ];

    // Get list.
    $q = $this->mediaStorage->getQuery();
    $q->accessCheck(TRUE);

    if (isset($params['title'])) {
      $q->condition('name', '%%' . $params['title'] . '%%', 'LIKE');
    }

    // phpcs:disable
    // If (isset($params['alt']) && $params['alt']) {
    //   $q->condition('alt', '');
    // }
    // phpcs:enable

    if (isset($params['bundle'])) {
      $q->condition('bundle', $params['bundle']);
    }
    else {
      $params['bundle'] = ['image'];
      $q->condition('bundle', 'image');
    }

    $sort = $params['sort'] ?? 'mid';
    $sort_order = $params['sort_order'] ?? 'ASC';

    $q->sort($sort, $sort_order);
    $q->pager(25);

    $r = $q->execute();

    $medias = $this->mediaStorage->loadMultiple($r);

    // Get field defs.
    foreach ($params['bundle'] as $b) {
      $bundle_fields[$b] = $this->entityFieldManager->getFieldDefinitions('media', $b);
    }

    foreach ($medias as $media) {
      $source = $media->getSource();
      $source_field = $source->getSourceFieldDefinition($media->bundle->entity);
      $source_field_name = $source_field->getName();
      $row = [];

      // Mid.
      $row[] = ['data' => $media->id()];

      // Image thumbnail.
      $row[] = [
        'data' => [
          '#theme' => 'image_formatter',
          '#item' => $media->get('thumbnail')->first(),
          '#item_attributes' => [
            'loading' => 'lazy',
          ],
          '#image_style' => 'thumbnail',
        ],
      ];

      // Name.
      $row[] = [
        'data' => [
          '#type' => "textfield",
          '#id' => $media->id() . '-alt',
          '#value' => $media->name->value,
          '#attributes' => [
            'class' => ['qe-element'],
            'data-entity-id' => $media->id(),
            'data-field' => 'name',
            'data-property' => 'value',
          ],
        ],
      ];

      // Alt.
      $alt = "";
      $source_field_value = $media->{$source_field_name}->getValue();
      $alt = current($source_field_value)['alt'] ?? "";
      $row[] = [
        'data' => [
          '#type' => "textfield",
          '#id' => $media->id() . '-alt',
          '#value' => $alt,
          '#attributes' => [
            'class' => ['qe-element'],
            'data-entity-id' => $media->id(),
            'data-field' => 'alt',
            'data-property' => 'value',
          ],
        ],
      ];

      foreach ($fields as $f) {
        if ($media->hasField($f)) {
          $field_def = ($bundle_fields[$media->bundle()][$f]) ?? [];

          $form_field_type = $this->getFormFieldType($field_def->getType());

          $field_value = current($media->{$f}->getValue());
          $cell = [
            'data' => [
              [
                '#type' => $form_field_type,
                '#value' => $field_value['value'] ?? "",
                '#attributes' => [
                  'class' => ['qe-element'],
                  'data-entity-id' => $media->id(),
                  'data-field' => $f,
                  'data-property' => 'value',
                ],
              ],
            ],
          ];

          // Add secondary fields like "format".
          if ($adds = $this->getSecondaryFormFields($f, $field_def, $media)) {
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
  public function quickEditMediaApi(Request $request) {
    $postData = json_decode($request->getContent());
    $media = $this->mediaStorage->load($postData->id);

    if ($postData->field == "alt") {
      $source = $media->getSource();
      $source_field = $source->getSourceFieldDefinition($media->bundle->entity);
      $source_field_name = $source_field->getName();

      $source_field_value = $media->{$source_field_name}->getValue();

      $source_field_value[0]['alt'] = $postData->value;
      $media->set($source_field_name, $source_field_value);
    }
    else {
      $field_value = $media->{$postData->field}->getValue();
      $field_value[0][($postData->property ?? "value")] = $postData->value;
      $media->set($postData->field, $field_value);
    }

    $media->save();

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

  private function getSecondaryFormFields($f, $field_def, $entity) {
    $adds = [];
    switch ($field_def->getType()) {
      // Need to add format here.
      case 'text':
      case 'text_long':

        $field_value = current($entity->{$f}->getValue());
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
            'data-entity-id' => $entity->id(),
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
