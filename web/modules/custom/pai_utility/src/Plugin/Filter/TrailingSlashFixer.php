<?php

namespace Drupal\pai_utility\Plugin\Filter;

use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter br/hr tags so they do not contain the trailing slash.
 *
 * @Filter(
 *   id = "filter_trailing_slash_fixer",
 *   title = @Translation("Trailing slash fixer"),
 *   description = @Translation("Fixer to remove trailing slash from br and hr tags (so far)."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class TrailingSlashFixer extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $extenstionThemeList;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ThemeExtensionList $theme_ext_list) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->extenstionThemeList = $theme_ext_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('extension.list.theme')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $text = str_replace(["<br />", "<hr />"], ["<br>", "<hr>"], $text);
    $result = new FilterProcessResult($text);
    return $result;
  }

}
