<?php

namespace Drupal\pai_utility\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Render\Markup;

/**
 * Plugin implementation of the 'SVG Icon' formatter.
 *
 * @FieldFormatter(
 *   id = "svg_icon",
 *   label = @Translation("SVG Icon"),
 *   field_types = {
 *     "string",
 *     "string_long",
 *   }
 * )
 */
class SvgIcon extends StringFormatter {

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return array
   *   The textual output generated as a render array.
   */
  protected function viewValue(FieldItemInterface $item) {
    return [
      '#markup' => Markup::create($item->value),
    ];
  }

}
