<?php

namespace Drupal\pai_quick_edit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
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

  protected $mediaStorage;

  protected $fileStorage;

  protected $formBuilder;

  /**
   *
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, $form_builder) {
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
   *
   */
  public function quickEditMedia(Request $request) {
    ksm($request->query);

    $params = $request->query->all();

    $build = [
      'form' => $this->formBuilder->getForm('Drupal\pai_quick_edit\Form\MediaQuickEditForm'),
      'table' => [
        '#type' => 'table',
        '#header' => ['mid', 'Item', 'name', 'alt', 'credits', 'caption'],
        '#rows' => [],
        '#footer' => [],
        '#sticky' => TRUE,
        '#empty' => "There are no media etities to edit.",
      ],
    ];

    // Get list.
    $q = $this->mediaStorage->getQuery();
    $q->accessCheck(TRUE);

    if (isset($params['title'])) {
      $q->condition('name', '%%' . $params['title'] . '%%', 'LIKE');
    }
    // if (isset($params['alt']) && $params['alt']) {
    //   $q->condition('alt', '');
    // }
    if (isset($params['bundle'])) {
      $q->condition('bundle', $params['bundle']);
    }

    $q->sort('mid', 'ASC');
    // $q->range(0, 5);
    $q->pager(25);

    $r = $q->execute();

    $medias = $this->mediaStorage->loadMultiple($r);

    // If ($source->getPluginId() == "image") {
    //   $fid = $source->getSourceFieldValue($media);
    //   $file = $this->entityTypeManager->getStorage('file')->load($fid);
    //   $file_uri = $file->getFileUri();
    // $absolute_path = $this->fileSystem->realpath($file_uri);
    // $event->setPath($absolute_path);
    // }
    // ksm(current($medias), current($medias)->getSource(), current($medias)->getSource()->getSourceFieldValue(current($medias)), current($medias)->name, current($medias)->field_media_caption, current($medias)->field_media_image->getValue());
    foreach ($medias as $media) {
      $source = $media->getSource();
      // ksm($media->getEntityType());
      // ksm($media->bundle);
      $source_field = $source->getSourceFieldDefinition($media->bundle->entity);
      $source_field_name = $source_field->getName();
      // ksm($source_field, $source_field->getName());
      // If ($source->getPluginId() == "image") {
      //   $fid = $source->getSourceFieldValue($media);
      //   $file = $this->fileStorage->load($fid);
      //   // ksm($file);
      // }
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
          // '#url' => $this->getMediaThumbnailUrl($media, $items->getEntity()),
        ],
      ];

      // Name.
      $row[] = ['data' => $media->name->value];

      // Alt.
      $alt = "";
      $source_field_value = $media->{$source_field_name}->getValue();
      // ksm($source_field_value);
      $alt = current($source_field_value)['alt'] ?? "";
      $row[] = [
        'data' => [
          '#type' => "textfield",
          '#id' => $media->id() . '-alt',
          '#value' => $alt,
        ],
      ];

      // Credit.
      if ($media->hasField('field_media_credit')) {
        $credit_value = current($media->field_media_credit->getValue());
        $row[] = [
          'data' => [
            '#type' => "textfield",
            '#id' => $media->id() . '-alt',
            '#value' => $credit_value['value'] ?? "",
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
            '#type' => "textfield",
            '#id' => $media->id() . '-alt',
            '#value' => $caption_value['value'] ?? "",
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

    ksm($build);
    return $build;
  }

  /**
   *
   */
  public function quickEditMediaApi() {

  }

}
