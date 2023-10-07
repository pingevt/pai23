<?php

namespace Drupal\img_processor\Plugin\QueueWorker;

use Drupal\file\Entity\File;
use Drupal\img_processor\Event\MediaSourcePath;

/**
 * Process Image media for Average color.
 *
 * @QueueWorker(
 *   id = "img_processor.avg_color",
 *   title = @Translation("Img Processor: Avg Color"),
 * )
 */
class ImgProcessorAvgColor extends ImgProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {

    // Get Media Entity.
    $field = $this->config->get('avg_color_field');
    $media = $this->mediaStorage->load($item['mid']);

    // $source = $media->getSource();
    // $fid = $source->getSourceFieldValue($media);
    // $file = File::load($fid);
    // $file_uri = $file->getFileUri();

    // $absolute_path = \Drupal::service('file_system')->realpath($file_uri);

    // Instantiate our event.
    $event = new MediaSourcePath($media);
    // Dispatch the event.
    $this->eventDispatcher->dispatch($event, MediaSourcePath::EVENT_NAME);
    // Grab the path, internal or external.
    $absolute_path = $event->getPath();

    // Grab image to process.
    $im_avg_color = new \Imagick();
    $im_avg_color->readImage($absolute_path);

    $im_avg_color->resizeImage(1, 1, \Imagick::FILTER_HERMITE, 0.5, FALSE, FALSE);
    $pixel = $im_avg_color->getImagePixelColor(0, 0);
    $pixel_color = $pixel->getColor();

    $media->field_average_color = "#" . dechex($pixel_color['r']) . dechex($pixel_color['g']) . dechex($pixel_color['b']);

    $media->set($field, "#" . dechex($pixel_color['r']) . dechex($pixel_color['g']) . dechex($pixel_color['b']));
    $media->fromImgProcessor = TRUE;

    $media->save();

    // Set proper state.
    $this->imgProcState[$item['mid']]["avg_color"] = FALSE;
    $this->state->set('img_processor.data', $this->imgProcState);
  }

}
