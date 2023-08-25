<?php

namespace Drupal\img_processor\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\media\MediaInterface;

/**
 * Event that is fired when a Media Entity is saved.
 */
class MediaPresaveEvent extends Event {

  // This makes it easier for subscribers to reliably use our event name.
  const EVENT_NAME = 'img_processor.media_presave';

  /**
   * The media entity.
   *
   * @var \Drupal\media\Entity\Media
   */
  public $entity;

  /**
   * Constructs the object.
   */
  public function __construct(MediaInterface $entity) {
    $this->entity = $entity;
  }

}
