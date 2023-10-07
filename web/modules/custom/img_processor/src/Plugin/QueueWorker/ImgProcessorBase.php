<?php

namespace Drupal\img_processor\Plugin\QueueWorker;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\State\State;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process Image Base.
 */
class ImgProcessorBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Queue data item.
   *
   * @var object
   */
  protected $item;

  /**
   * Entity Type Manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity Type Manager.
   *
   * @var Drupal\node\NodeStorage
   */
  protected $nodeStorage;

  /**
   * Entity Type Manager.
   *
   * @var Drupal\media\MediaStorage
   */
  protected $mediaStorage;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Drupal State obj.
   *
   * @var Drupal\Core\State\State
   */
  protected $state = [];

  /**
   * This modules state Data.
   *
   * @var array
   */
  protected $imgProcState = [];

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * This modules config.
   *
   * @var array
   */
  protected $config = [];

  /**
   * The event dispatcher.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventDispatcher = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $factory,
    ConfigFactoryInterface $config_factory,
    State $drupal_state,
    ContainerAwareEventDispatcher $event_dispatcher
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Set entity Type manager.
    $this->entityTypeManager = $entity_type_manager;
    $this->mediaStorage = $this->entityTypeManager->getStorage('media');

    $this->loggerFactory = $factory;

    $this->state = $drupal_state;
    $this->imgProcState = $this->state->get('img_processor.data', []);

    $this->configFactory = $config_factory;
    $this->config = $this->configFactory->get('img_processor.settings');

    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('state'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {

  }


  /**
   * Color distances.
   *
   * https://en.wikipedia.org/wiki/Color_difference
   */
  protected function getColorDistance($color1, $color2) {

    $sum_of_squares = 0;
    foreach (['r', 'g', 'b'] as $i) {
      $sum_of_squares += pow((($color2[$i] / 255) - ($color1[$i] / 255)), 2);
    }

    return sqrt($sum_of_squares);
  }

  protected function getHueDistance($color1, $color2) {

    $sum_of_squares = 0;
    foreach (['hue'] as $i) {
      $sum_of_squares += pow(($color2[$i] - $color1[$i]), 2);
    }

    return sqrt($sum_of_squares);
  }

  protected function iMagickColorToHEX($pixel) {
    $color = $pixel->getColor();

    return sprintf('%s%s%s',
        str_pad(dechex($color['r']), 2, "0", STR_PAD_LEFT),
        str_pad(dechex($color['g']), 2, "0", STR_PAD_LEFT),
        str_pad(dechex($color['b']), 2, "0", STR_PAD_LEFT)
    );
  }

}
