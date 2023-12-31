<?php

namespace Drupal\pai_utility\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter links in the text formatter to include an SVG for external links.
 *
 * @Filter(
 *   id = "filter_custom_links",
 *   title = @Translation("Filter Links"),
 *   description = @Translation("Filter links to create complex markup for a link"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class Links extends FilterBase implements ContainerFactoryPluginInterface {

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
    $result = new FilterProcessResult($text);

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);

    $processed = FALSE;
    foreach ($xpath->query('//a[contains(@class,"external")]') as $node) {
      $this->createArrowSvg($dom, $node);
      $processed = TRUE;
    }

    if ($processed) {
      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

  /**
   * Create dom for an svg link.
   */
  protected function createArrowSvg($dom, $link) {

    $svg = $dom->createElement('svg', '');
    $svg->setAttribute("height", "24");
    $svg->setAttribute("width", "24");
    $svg->setAttribute("viewBox", "0 0 24 24");
    $svg->setAttribute("xmlns", "http://www.w3.org/2000/svg");

    $path1 = $dom->createElement('path', '');
    $path1->setAttribute('d', "m12.0007042 2.00070416c5.5228475 0 10 4.4771525 10 10.00000004 0 5.5228475-4.4771525 10-10 10-5.52284754 0-10.00000004-4.4771525-10.00000004-10 0-5.52284754 4.4771525-10.00000004 10.00000004-10.00000004zm0 1.5c-4.69442042 0-8.50000004 3.80557962-8.50000004 8.50000004 0 4.6944203 3.80557962 8.5 8.50000004 8.5 4.6944203 0 8.5-3.8055797 8.5-8.5 0-4.69442042-3.8055797-8.50000004-8.5-8.50000004zm-.3529483 4.05308419.0726182-.08411844c.2662665-.26626656.6829302-.29047261.9765417-.07261815l.0841184.07261815 4.0008227 4.00082269c.2663025.2663024.2904743.6830339.0725417.9766451l-.0726429.0841162-4.0015268 4c-.2929491.2928373-.7678228.2927467-1.0606602-.0002024-.2662157-.2663173-.2903423-.6829856-.0724318-.9765556l.0726342-.0841045 2.7219331-2.7206872-6.6917042.0002958c-.37969577 0-.69349096-.2821539-.74315338-.6482294l-.00684662-.1017706c0-.3796958.28215388-.693491.64822944-.7431534l.10177056-.0068466 6.6897042-.0002958-2.7193301-2.71937411c-.2662666-.26626657-.2904726-.68293025-.0726182-.97654174l.0726182-.08411844z");
    $path1->setAttribute('fill', 'currentColor');

    $svg->appendChild($path1);

    $link->appendChild($svg);
  }

}
