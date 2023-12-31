<?php

namespace Drupal\img_processor\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\media\MediaInterface;

/**
 * Event that is fired when a Media Entity is saved.
 */
class MediaSourcePath extends Event {

  // This makes it easier for subscribers to reliably use our event name.
  const EVENT_NAME = 'img_processor.media_source_path';

  /**
   * The media entity.
   *
   * @var \Drupal\media\Entity\Media
   */
  public $entity;

  /**
   * The path to the image.
   *
   * @var string
   */
  protected $path = "";

  /**
   * Constructs the object.
   */
  public function __construct(MediaInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * Setter for path.
   */
  public function setPath($path) {
    $this->path = $path;
  }

  /**
   * Getter for path.
   */
  public function getPath() {
    return $this->path;
  }

}
