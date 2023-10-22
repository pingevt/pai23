<?php

namespace Drupal\img_processor\Plugin\search_api\processor;

use Drupal\media\MediaInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds a boost to artwork and archives.
 *
 * @SearchApiProcessor(
 *   id = "avg_color_distance",
 *   label = @Translation("Average Color Distance"),
 *   description = @Translation("Adds color data not fielded to the Media item."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class AverageColorDistance extends  ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if ($datasource && $datasource->getEntityTypeId() == "media") {
      $definition = [
        'label' => $this->t('Average Color Distance'),
        'description' => $this->t('Adds color data not fielded to the Media item.'),
        'type' => 'float',
        'is_list' => TRUE,
        'processor_id' => $this->getPluginId(),
      ];
      $properties['avg_color_distance'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {

    $media = $item->getOriginalObject()->getEntity();

    if ($media instanceof MediaInterface && isset($media->img_processor_data)) {
      $color_data = [];
      // $average_color = $media->field_average_color->getValue();
      // $average_color_hex = str_replace("#", "", current($average_color)['color']);

      foreach ($media->img_processor_data as $data) {
        $color_data[$data['bin_color']][] = $data['color_distance'];
      }

      // Set Value.
      $fields = $item->getFields(FALSE);
      $fields = $this->getFieldsHelper()->filterForPropertyPath($fields, "entity:media", 'avg_color_distance');

      foreach ($fields as $field_id => &$field) {
        // ksm($field_id);
        // if ($field_id == "avg_color_distance") {
        //   if (isset($color_data[$average_color_hex])) {
        //     ksm($color_data[$average_color_hex]);
        //     foreach ($color_data[$average_color_hex] as $v) {
        //       $field->addValue((float) $v);
        //     }
        //   }
        // }
        // else {
          $index_hex = end(explode("_", $field_id));
          ksm($index_hex);
          if (isset($color_data[$index_hex])) {
            foreach ($color_data[$index_hex] as $v) {
              $field->addValue((float) $v);
            }
          }
        // }
      }
    }
  }

}
