<?php

namespace Drupal\img_processor\Plugin\QueueWorker;

use Drupal\file\Entity\File;
use League\ColorExtractor\Color;
use League\ColorExtractor\ColorExtractor;
use League\ColorExtractor\Palette;

/**
 * Process Image media for Average color.
 *
 * @QueueWorker(
 *   id = "img_processor.color_palette",
 *   title = @Translation("Img Processor: Color Palette"),
 * )
 */
class ImgProcessorColorPalette extends ImgProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    // Get Media Entity.
    $field = $this->config->get('color_palette_field');
    $count = $this->config->get('color_palette_count');
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

    // Color Pallettes.
    $palette = Palette::fromFilename($absolute_path);
    $extractor = new ColorExtractor($palette);
    $colors = $extractor->extract($count);

    foreach ($colors as $c) {
      $p_value[] = Color::fromIntToHex($c);
    }

    $media->set($field, $p_value);
    $media->fromImgProcessor = TRUE;

    $media->save();

    // Set proper state.
    $this->imgProcState[$item['mid']]["color_palette"] = FALSE;
    $this->state->set('img_processor.data', $this->imgProcState);
  }

}
