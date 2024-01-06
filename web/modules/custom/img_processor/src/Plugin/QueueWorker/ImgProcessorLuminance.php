<?php
// phpcs:ignoreFile

namespace Drupal\img_processor\Plugin\QueueWorker;

use Drupal\file\Entity\File;
use Drupal\img_processor\Event\MediaSourcePath;

/**
 * Process Image media for luminance.
 *
 * @QueueWorker(
 *   id = "img_processor.luminance",
 *   title = @Translation("Img Processor: Luminance"),
 * )
 */
class ImgProcessorLuminance extends ImgProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {

    // Get Media Entity.
    $field = $this->config->get('luminance_field');
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
    $q_range = \Imagick::getQuantumRange();
    $im = new \Imagick();
    $im->readImage($absolute_path);

    $mean = $im->getImageChannelMean(\Imagick::CHANNEL_ALL);
    $mean_perc = $mean['mean'] / $q_range['quantumRangeLong'];

    $media->set($field, $mean_perc);

    // Quad.
    $geo = $im->getImageGeometry();

    $geo['width_half'] = (int) ($geo['width'] / 2);
    $geo['height_half'] = (int) ($geo['height'] / 2);

    $im_tl = clone $im;
    $im_tr = clone $im;
    $im_bl = clone $im;
    $im_br = clone $im;

    $im_tl->cropImage($geo['width_half'], $geo['height_half'], 0, 0);
    $mean_tl = $im_tl->getImageChannelMean(\Imagick::CHANNEL_ALL);
    $mean_perc_tl = $mean_tl['mean'] / $q_range['quantumRangeLong'];

    $im_tr->cropImage($geo['width_half'], $geo['height_half'], $geo['width_half'], 0);
    $mean_tr = $im_tr->getImageChannelMean(\Imagick::CHANNEL_ALL);
    $mean_perc_tr = $mean_tr['mean'] / $q_range['quantumRangeLong'];

    $im_bl->cropImage($geo['width_half'], $geo['height_half'], 0, $geo['height_half']);
    $mean_bl = $im_bl->getImageChannelMean(\Imagick::CHANNEL_ALL);
    $mean_perc_bl = $mean_bl['mean'] / $q_range['quantumRangeLong'];

    $im_br->cropImage($geo['width_half'], $geo['height_half'], $geo['width_half'], $geo['height_half']);
    $mean_br = $im_br->getImageChannelMean(\Imagick::CHANNEL_ALL);
    $mean_perc_br = $mean_br['mean'] / $q_range['quantumRangeLong'];

    $q_val = [
      $mean_perc_tl,
      $mean_perc_tr,
      $mean_perc_bl,
      $mean_perc_br,
    ];

    $q_field = $this->config->get('quad_luminance_field');
    $media->set($q_field, $q_val);
    $media->fromImgProcessor = TRUE;

    $media->save();

    // Set proper state.
    $this->imgProcState[$item['mid']]["luminance"] = FALSE;
    $this->state->set('img_processor.data', $this->imgProcState);

  }

}
