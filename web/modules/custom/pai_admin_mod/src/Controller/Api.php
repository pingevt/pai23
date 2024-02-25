<?php

namespace Drupal\pai_admin_mod\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class Api extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Entity Type Manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * The Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  protected $queue;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_system, LoggerChannelFactoryInterface $factory, ConfigFactory $config_factory, $queue) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->loggerFactory = $factory;
    $this->configFactory = $config_factory;
    $this->queue = $queue;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('queue')
    );
  }

  /**
   * Save data from API callback.
   */
  public function queueImagesFromMageSpace(Request $request) {

    $data = [
      "status" => "OK",
      "data" => [],
    ];

    $payload = json_decode($request->getContent(), true);
    $objects = $payload['objects'] ?? [];

    // Object check.
    if (empty($objects)) {
      $data = [
        "status" => "ERROR",
        "message" => "No objects defined.",
      ];

      $bad_response = new JsonResponse($data, 400);
      return $bad_response;
    }

    $queue = $this->queue->get('sync_data.mage_images');
    foreach ($objects as $object) {
      $obj = new \stdClass();
      $obj->data = (array) $object;
      $queue->createItem($obj);
    }

    $data["data"]["count"] = count($objects);

    $response = new JsonResponse($data);

    return $response;
  }

}
