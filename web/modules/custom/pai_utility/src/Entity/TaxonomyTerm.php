<?php

namespace Drupal\pai_utility\Entity;

use Drupal\taxonomy\Entity\Term;

/**
 * Provides a custom entity class for taxonomy terms.
 */
class TaxonomyTerm extends Term {

  /**
   * Display a public label.
   */
  public function publicLabel() {

    if ($this->hasField('field_display_name') && !$this->field_display_name->isEmpty()) {
      return $this->field_display_name->value;
    }

    return parent::label();
  }

}
