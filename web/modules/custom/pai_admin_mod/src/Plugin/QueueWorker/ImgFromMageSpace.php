<?php
// phpcs:ignoreFile

namespace Drupal\pai_admin_mod\Plugin\QueueWorker;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\State\State;
use Drupal\file\Entity\File;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process Image from Mage.
 *
 * @QueueWorker(
 *   id = "sync_data.mage_images",
 *   title = @Translation("Sync Data from Mage Space Images"),
 *   cron = {"time" = 60}
 * )
 */
class ImgFromMageSpace extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Queue data item.
   *
   * @var object
   */
  protected $item;

  /**
   * Database Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

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

  protected $httpClient;

  protected $queue;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Connection $connection,
    EntityTypeManagerInterface $entity_type_manager,
    FileSystemInterface $file_system,
    LoggerChannelFactoryInterface $factory,
    ConfigFactoryInterface $config_factory,
    State $drupal_state,
    ContainerAwareEventDispatcher $event_dispatcher,
    ClientInterface $http_client,
    $queue
  ) {

    $this->connection = $connection;

    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Set entity Type manager.
    $this->entityTypeManager = $entity_type_manager;
    $this->mediaStorage = $this->entityTypeManager->getStorage('media');

    $this->loggerFactory = $factory;

    $this->state = $drupal_state;

    $this->configFactory = $config_factory;

    $this->fileSystem = $file_system;

    $this->eventDispatcher = $event_dispatcher;
    $this->httpClient = $http_client;
    $this->queue = $queue;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('state'),
      $container->get('event_dispatcher'),
      $container->get('http_client'),
      $container->get('queue')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {

    $mage_id = $item->data['id'];

    // Check if media exists.
    $query = $this->connection->select("media__field_magespace_id", "m");
    $query->fields("m");
    $query->condition("m.field_magespace_id_value", $mage_id);

    $results = $query->execute()->fetchAll();

    // Use exisiting Media.
    if (!empty($results)) {
      return;
    }

    // Download Image.
    $image_url = $item->data['enhanced_image_url']?? $item->data['image_url'];
    $img_file = $this->downloadImage($image_url);

    if ($img_file) {
      $caption = "<p>prompt: " . $item->data['metadata']['prompt'] . "</p>";
      $caption .= $item->data['metadata']['negative_prompt']? "<p>negative prompt: " . $item->data['metadata']['negative_prompt'] . "</p>" : "";
      $alt = "AI: " . $item->data['metadata']['prompt'];

      $media = $this->mediaStorage->create([
        'bundle' => 'image',
        'status' => TRUE,
        'uid' => 1,
        'title' => "",
        'field_media_image' => [
          'target_id' => $img_file->id(),
          'alt' => $alt,
        ],
        'field_magespace_id' => $mage_id,
        'field_sync_data' => json_encode($item->data),
        'field_media_caption' => [
          'value' => $caption,
          'format' => 'full_html',
        ],
      ]);

      $media->save();
    }

  //   {
  //     "id": "7b8328134074412ab395e897637943e8",
  //     "uid": "YrKWQFSuoqRjHKTVXItXrCG1jbF2",
  //     "image_url": "https://cdn2.mage.space/content/YrKWQFSuoqRjHKTVXItXrCG1jbF2/c/7b8328134074412ab395e897637943e8/7b8328134074412ab395e897637943e8.jpg",
  //     "enhanced_image_url": null,
  //     "width": 1024,
  //     "height": 1024,
  //     "blurhash": "L9F}[z%19ZM|^%WXo#fl~9IoNeWB",
  //     "is_nsfw": false,
  //     "is_public": true,
  //     "is_enhanced": false,
  //     "metadata": {
  //         "seed": 2238389463209786,
  //         "width": 1024,
  //         "height": 1024,
  //         "prompt": "corgi Puppy in armor, medieval background, depth of field",
  //         "scheduler": "euler",
  //         "use_refiner": true,
  //         "model_version": "sdxl",
  //         "denoising_frac": 0.87,
  //         "guidance_scale": 5.6,
  //         "negative_prompt": " ",
  //         "num_inference_steps": 23
  //     },
  //     "tags": [],
  //     "model_name": "stable-diffusion",
  //     "model_version": "sdxl",
  //     "created_at": "2024-02-20T02:38:24.062546+00:00",
  //     "updated_at": null
  // },
  }

  /**
   * Download Image and save file.
   */
  public function downloadImage(string $ext_url) {

    $parsed_url = parse_url($ext_url);
    $path_info = pathinfo($parsed_url['path']);


    $directory = "public://mage" . $path_info['dirname'];

    // The local thumbnail doesn't exist yet, so try to download it. First,
    // ensure that the destination directory is writable, and if it's not,
    // log an error and bail out.
    if (!$this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      $this->loggerFactory->warning('Could not prepare mage destination directory @dir for mage.', [
        '@dir' => $directory,
      ]);
      return NULL;
    }

    // The image doesn't exist yet, so we need to download it.
    $file = NULL;
    try {
      $response = $this->httpClient->request('GET', $ext_url);

      if ($response->getStatusCode() === 200) {
        $local_thumbnail_uri = $directory . DIRECTORY_SEPARATOR . $path_info['basename'];
        $this->fileSystem->saveData((string) $response->getBody(), $local_thumbnail_uri, FileSystemInterface::EXISTS_REPLACE);

        $file = File::create([
          'filename' => basename($local_thumbnail_uri),
          'uri' => $local_thumbnail_uri,
          'status' => 1,
          'uid' => 1,
        ]);
        $file->save();
      }
    }
    catch (TransferException $e) {
      // ksm('e1', $e);
      $this->loggerFactory->warning('Failed to download remote thumbnail file due to "%error".', [
        '%error' => $e->getMessage(),
      ]);
    }
    catch (FileException $e) {
      // ksm('e2', $e);
      $this->loggerFactory->warning('Could not download remote thumbnail from {url}.', [
        'url' => $remote_thumbnail_url,
      ]);
    }

    return $file;
  }

}
