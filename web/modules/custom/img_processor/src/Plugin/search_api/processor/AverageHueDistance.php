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
 *   id = "avg_hue_distance",
 *   label = @Translation("Average Hue Distance"),
 *   description = @Translation("Adds Hue data not fielded to the Media item."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class AverageHueDistance extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if ($datasource && $datasource->getEntityTypeId() == "media") {
      $definition = [
        'label' => $this->t('Average Hue Distance'),
        'description' => $this->t('Adds Hue data not fielded to the Media item.'),
        'type' => 'float',
        'is_list' => TRUE,
        'processor_id' => $this->getPluginId(),
      ];
      $properties['avg_hue_distance'] = new ProcessorProperty($definition);
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

      foreach ($media->img_processor_data as $data) {
        $color_data[$data['bin_color']][] = $data['hue_distance'];
      }

      // Set Value.
      $fields = $item->getFields(FALSE);
      $fields = $this->getFieldsHelper()->filterForPropertyPath($fields, "entity:media", 'avg_hue_distance');

      foreach ($fields as $field_id => &$field) {
        $exploded_ids = explode("_", $field_id);
        $index_hex = end($exploded_ids);
        if (isset($color_data[$index_hex])) {
          foreach ($color_data[$index_hex] as $v) {
            $field->addValue((float) $v);
          }
        }
      }
    }
  }

}
