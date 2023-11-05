<?php

namespace Drupal\img_processor\Plugin\QueueWorker;

use Drupal\img_processor\Event\MediaSourcePath;

/**
 * Process Image media for histogram.
 *
 * @QueueWorker(
 *   id = "img_processor.histogram",
 *   title = @Translation("Img Processor: Histogram"),
 * )
 */
class ImgProcessorHistogram extends ImgProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {

    // Get Media Entity.
    $field = $this->config->get('histogram_string_field');
    $media = $this->mediaStorage->load($item['mid']);

    // Instantiate our event.
    $event = new MediaSourcePath($media);
    // Dispatch the event.
    $this->eventDispatcher->dispatch($event, MediaSourcePath::EVENT_NAME);
    // Grab the path, internal or external.
    $absolute_path = $event->getPath();

    // Grab image to process.
    $im = new \Imagick();
    $im->readImage($absolute_path);

    // Resize image to 250 x 250 = 62,500 colors max.
    $im->adaptiveResizeImage(250, 250, TRUE);

    // Remap to custom pallete.
    $remaped_im = clone $im;
    $remaped_im->quantizeImage(216, \Imagick::COLORSPACE_YIQ, 0, FALSE, FALSE);

    $web_safe_image = \Drupal::service('extension.list.module')->getPath('img_processor') . "/assets/dist/images/web_safe_pallette.png";
    $ws = new \Imagick();
    $ws->readImage($web_safe_image);
    $remaped_im->remapImage($ws, \Imagick::DITHERMETHOD_NO);

    // Get Histogram.
    $histogram_elements = $remaped_im->getImageHistogram();

    // ksm($histogram_elements);
    $index_str = $this->getHistogramColorStringIndex($histogram_elements);

    $media->set($field, $index_str);
    $media->fromImgProcessor = TRUE;

    $media->save();

    // Set proper state.
    $this->imgProcState[$item['mid']]["histogram"] = FALSE;
    $this->state->set('img_processor.data', $this->imgProcState);
  }

  /**
   *
   */
  protected function getHistogramColorStringIndex($histogram_elements):string {
    $colors = [];

    foreach ($histogram_elements as $histogram_element) {
      $hex_val = $this->iMagickColorToHEX($histogram_element);
      $count = $histogram_element->getColorCount();

      $colors = array_merge($colors, array_fill(0, $count, $hex_val));
    }

    return implode(" ", $colors);
  }

}
