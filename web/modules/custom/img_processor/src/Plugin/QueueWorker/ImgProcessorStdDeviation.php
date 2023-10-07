<?php

namespace Drupal\img_processor\Plugin\QueueWorker;

use Drupal\file\Entity\File;
use Drupal\img_processor\Event\MediaSourcePath;

/**
 * Process Image media for std_deviation.
 *
 * @QueueWorker(
 *   id = "img_processor.std_deviation",
 *   title = @Translation("Img Processor: Std Deviation"),
 * )
 */
class ImgProcessorStdDeviation extends ImgProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {

    // Get Media Entity.
    $field = $this->config->get('std_deviation_field');
    $media = $this->mediaStorage->load($item['mid']);

    // Instantiate our event.
    $event = new MediaSourcePath($media);
    // Dispatch the event.
    $this->eventDispatcher->dispatch($event, MediaSourcePath::EVENT_NAME);
    // Grab the path, internal or external.
    $absolute_path = $event->getPath();

    // Grab image to process.
    $q_range = \Imagick::getQuantumRange();
    $im = new \Imagick();
    $im->readImage($absolute_path);

    $mean = $im->getImageChannelMean(\Imagick::CHANNEL_ALL);
    $std_deviation = $mean['standardDeviation'] / $q_range['quantumRangeLong'];

    $media->set($field, $std_deviation);

    $media->save();

    // Set proper state.
    $this->imgProcState[$item['mid']]["std_deviation"] = FALSE;
    $this->state->set('img_processor.data', $this->imgProcState);
  }

}
