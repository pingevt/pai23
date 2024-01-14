<?php

namespace Drupal\pai_quick_edit\Controller;

use Drupal\Core\Controller\ControllerBase;
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
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FormBuilder $form_builder) {
    $this->entityTypeManager = $entity_type_manager;
    $this->mediaStorage = $this->entityTypeManager->getStorage('media');
    $this->fileStorage = $this->entityTypeManager->getStorage('file');
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('form_builder')
    );
  }

  /**
   * Callback for quick edit media page.
   */
  public function quickEditMedia(Request $request) {
    $params = $request->query->all();

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
      $q->condition('bundle', 'image');
    }

    $q->sort('mid', 'ASC');
    $q->pager(25);

    $r = $q->execute();

    $medias = $this->mediaStorage->loadMultiple($r);

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
          ],
        ],
      ];

      // Credit.
      if ($media->hasField('field_media_credit')) {
        $credit_value = current($media->field_media_credit->getValue());
        $row[] = [
          'data' => [
            '#type' => "textarea",
            '#id' => $media->id() . '-alt',
            '#value' => $credit_value['value'] ?? "",
            '#attributes' => [
              'class' => ['qe-element'],
              'data-entity-id' => $media->id(),
              'data-field' => 'field_media_credit',
            ],
          ],
        ];
      }
      else {
        $row[] = [];
      }

      // Caption.
      if ($media->hasField('field_media_caption')) {
        $caption_value = current($media->field_media_caption->getValue());
        $row[] = [
          'data' => [
            '#type' => "textarea",
            '#id' => $media->id() . '-alt',
            '#value' => $caption_value['value'] ?? "",
            '#attributes' => [
              'class' => ['qe-element'],
              'data-entity-id' => $media->id(),
              'data-field' => 'field_media_caption',
            ],
          ],
        ];
      }
      else {
        $row[] = [];
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
      $field_value[0]['value'] = $postData->value;
      $media->set($postData->field, $field_value);
    }

    $media->save();

    return new JsonResponse($postData);
  }

}
