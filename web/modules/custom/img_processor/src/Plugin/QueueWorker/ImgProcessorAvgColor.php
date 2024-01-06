<?php
// phpcs:ignoreFile

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
    $index_colors = $this->config->get('color_bins');
    $media = $this->mediaStorage->load($item['mid']);

    // Bail if entity doesn't exist.
    if (!$media) {
      return;
    }

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

    // Get Distances.
    $distances = [];
    foreach ($media->img_processor_data as $data) {
      $distances[$data['bin_color'].$data['media_color']] = $data;
    }

    foreach ($index_colors as $bin_color) {
      $bin_pixel = new \ImagickPixel($bin_color['color']);

      $bin_color = $this->iMagickColorToHEX($bin_pixel);
      $media_color = $this->iMagickColorToHEX($pixel);
      $distances[$bin_color.$media_color] = [
        'bin_color' => $bin_color,
        'media_color' => $media_color,
        'color_distance' => $this->getColorDistance($pixel->getColor(), $bin_pixel->getColor()),
        'hue_distance' => $this->getHueDistance($pixel->getHSL(), $bin_pixel->getHSL()),
      ];

    }
    $media->img_processor_data = array_values($distances);

    $media->set($field, "#" . dechex($pixel_color['r']) . dechex($pixel_color['g']) . dechex($pixel_color['b']));
    $media->fromImgProcessor = TRUE;

    $media->save();

    // Set proper state.
    $this->imgProcState[$item['mid']]["avg_color"] = FALSE;
    $this->state->set('img_processor.data', $this->imgProcState);
  }

}
