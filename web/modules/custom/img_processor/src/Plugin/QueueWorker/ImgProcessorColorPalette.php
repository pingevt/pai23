<?php

namespace Drupal\img_processor\Plugin\QueueWorker;

use Drupal\file\Entity\File;
use League\ColorExtractor\Color;
use League\ColorExtractor\ColorExtractor;
use League\ColorExtractor\Palette;
use Drupal\img_processor\Event\MediaSourcePath;

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
    $index_colors = $this->config->get('color_bins');
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

    // Get Distances.
    $distances = [];
    foreach ($media->img_processor_data as $data) {
      $distances[$data['bin_color'] . $data['media_color']] = $data;
    }

    // Color Pallettes.
    $palette = Palette::fromFilename($absolute_path);
    $extractor = new ColorExtractor($palette);
    $colors = $extractor->extract($count);

    foreach ($colors as $c) {
      $color = Color::fromIntToHex($c);
      $p_value[] = $color;

      $pixel = new \ImagickPixel($color);

      foreach ($index_colors as $bin_color) {
        $bin_pixel = new \ImagickPixel($bin_color['color']);

        $bin_color = $this->iMagickColorToHEX($bin_pixel);
        $media_color = $this->iMagickColorToHEX($pixel);
        $distances[$bin_color . $media_color] = [
          'bin_color' => $bin_color,
          'media_color' => $media_color,
          'color_distance' => $this->getColorDistance($pixel->getColor(), $bin_pixel->getColor()),
          'hue_distance' => $this->getHueDistance($pixel->getHSL(), $bin_pixel->getHSL()),
        ];
      }
    }

    // Set Distances.
    $media->img_processor_data = array_values($distances);

    $media->set($field, $p_value);
    $media->fromImgProcessor = TRUE;

    $media->save();

    // Set proper state.
    $this->imgProcState[$item['mid']]["color_palette"] = FALSE;
    $this->state->set('img_processor.data', $this->imgProcState);
  }

}
