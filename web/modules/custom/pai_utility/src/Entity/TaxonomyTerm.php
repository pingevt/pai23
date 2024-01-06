<?php

namespace Drupal\pai_utility\Entity;

use Drupal\taxonomy\Entity\Term;

/**
 *
 */
class TaxonomyTerm extends Term {

  /**
   * Display a public label.
   */
  public function publicLabel() {

    ksm($this);

    if ($this->hasField('field_display_name') && !$this->field_display_name->isEmpty()) {
      return $this->field_display_name->value;
    }

    return parent::label();
  }

}
