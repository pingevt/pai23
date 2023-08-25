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
    $media = $this->mediaStorage->load($item['mid']);

    $source = $media->getSource();
    $fid = $source->getSourceFieldValue($media);
    $file = File::load($fid);
    $file_uri = $file->getFileUri();

    $absolute_path = \Drupal::service('file_system')->realpath($file_uri);

    // Color Pallettes.
    $palette = Palette::fromFilename($absolute_path);
    $extractor = new ColorExtractor($palette);
    $colors6 = $extractor->extract(6);
    $p_value = [
      Color::fromIntToHex($colors6[0]),
      Color::fromIntToHex($colors6[1]),
      Color::fromIntToHex($colors6[2]),
      Color::fromIntToHex($colors6[3]),
      Color::fromIntToHex($colors6[4]),
      Color::fromIntToHex($colors6[5]),
    ];

    $media->set($field, $p_value);
    $media->fromImgProcessor = TRUE;

    $media->save();

    // Set proper state.
    $this->imgProcState[$item['mid']]["color_palette"] = FALSE;
    $this->state->set('img_processor.data', $this->imgProcState);
  }

}
