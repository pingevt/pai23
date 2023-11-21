<?php

namespace Drupal\img_processor\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\State;
use Drupal\img_processor\Event\MediaPresaveEvent;
use Drupal\img_processor\Event\MediaSourcePath;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to Media Presave and process an imgage for color related funcs.
 */
class ImgProcessorSubscriber implements EventSubscriberInterface {

  /**
   * The state store.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Current Module State.
   *
   * @var array
   */
  protected $imgProcState;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new instance.
   */
  public function __construct(State $state, ConfigFactoryInterface $config_factory, QueueFactory $queue_factory, FileSystem $file_system, EntityTypeManagerInterface $entity_type_manager) {
    $this->state = $state;
    $this->imgProcState = $this->state->get('img_processor.data', []);
    $this->configFactory = $config_factory;
    $this->queueFactory = $queue_factory;
    $this->fileSystem = $file_system;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
    return [
      MediaSourcePath::EVENT_NAME => 'mediaSourcePath',
      MediaPresaveEvent::EVENT_NAME => 'mediaPresave',
    ];
  }

  /**
   * Function to run on Media Presave event.
   */
  public function mediaSourcePath(MediaSourcePath $event) {
    // $config = $this->configFactory->get('img_processor.settings');
    $media = $event->entity;

    $source = $media->getSource();

    // Set path for media module's image source.
    if ($source->getPluginId() == "image") {
      $fid = $source->getSourceFieldValue($media);
      $file = $this->entityTypeManager->getStorage('file')->load($fid);
      $file_uri = $file->getFileUri();

      $absolute_path = $this->fileSystem->realpath($file_uri);

      $event->setPath($absolute_path);
    }
  }

  /**
   * Function to run on Media Presave event.
   */
  public function mediaPresave(MediaPresaveEvent $event) {
    $config = $this->configFactory->get('img_processor.settings');
    $media = $event->entity;

    // Check if we are saving from img processor.
    if (isset($media->fromImgProcessor) && $media->fromImgProcessor === TRUE) {
      return;
    }

    // Check that we are the correct bundle.
    if (!in_array($media->bundle(), $config->get('bundle_options'))) {
      return;
    }

    // First Queue up Luminance.
    if ($config->get('process_luminance') && $this->shouldPorcessMedia($media, "luminance")) {
      // Queue it!
      $queue = $this->queueFactory->get('img_processor.luminance');
      $queue->createItem(['mid' => $media->id()]);

      $this->imgProcState[$media->id()]["luminance"] = TRUE;
    }

    // Queue up Std Deviation.
    if ($config->get('process_std_deviation') && $this->shouldPorcessMedia($media, "std_deviation")) {
      // Queue it!
      $queue = $this->queueFactory->get('img_processor.std_deviation');
      $queue->createItem(['mid' => $media->id()]);

      $this->imgProcState[$media->id()]["std_deviation"] = TRUE;
    }

    // Queue up histogram data.
    if ($config->get('process_histogram_string') && $this->shouldPorcessMedia($media, "histogram")) {
      // Queue it!
      $queue = $this->queueFactory->get('img_processor.histogram');
      $queue->createItem(['mid' => $media->id()]);

      $this->imgProcState[$media->id()]["histogram"] = TRUE;
    }

    // Queue up Avg Color.
    if ($config->get('process_avg_color') && $this->shouldPorcessMedia($media, "avg_color")) {
      // Queue it!
      $queue = $this->queueFactory->get('img_processor.avg_color');
      $queue->createItem(['mid' => $media->id()]);

      $this->imgProcState[$media->id()]["avg_color"] = TRUE;
    }

    // Queue up Color Palette.
    if ($config->get('process_color_palette') && $this->shouldPorcessMedia($media, "color_palette")) {
      // Queue it!
      $queue = $this->queueFactory->get('img_processor.color_palette');
      $queue->createItem(['mid' => $media->id()]);

      $this->imgProcState[$media->id()]["color_palette"] = TRUE;
    }

    // Reset state variable.
    $this->state->set('img_processor.data', $this->imgProcState);
  }

  /**
   * Check if this media is queued up or not to process.
   */
  public function shouldPorcessMedia($media, string $type):bool {
    if (!isset($this->imgProcState[$media->id()]) || !isset($this->imgProcState[$media->id()][$type])) {
      return TRUE;
    }

    return !$this->imgProcState[$media->id()][$type];
  }

}
