<?php

namespace Drupal\img_processor\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\multivalue_form_element\Element\MultiValue;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings for the Img Processor Module.
 */
class Settings extends ConfigFormBase {

  const SETTINGS = 'img_processor.settings';

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Class constructor.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'img_processor_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * Drupal Entity Field Manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  private $entityFieldManager;

  /**
   * Get Entity Field Manager.
   */
  private function entityFieldManager() {
    if (!$this->entityFieldManager) {
      $this->entityFieldManager = \Drupal::service('entity_field.manager'); // phpcs:ignore
    }
    return $this->entityFieldManager;
  }

  /**
   * Drupal Entity Type Bundle Info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  private $entityTypeBundleInfo;

  /**
   * Get Entity Type Bundle Info.
   */
  private function entityTypeBundleInfo() {
    if (!$this->entityTypeBundleInfo) {
      $this->entityTypeBundleInfo = \Drupal::service('entity_type.bundle.info'); // phpcs:ignore
    }
    return $this->entityTypeBundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);
    // ksm($config);
    $form['#tree'] = TRUE;

    // Set up fields.
    $bundle_info = $this->entityTypeBundleInfo()->getBundleInfo("media");
    $bundle_options = [];
    foreach ($bundle_info as $bundle_id => $bundle_data) {
      $bundle_options[$bundle_id] = $bundle_data['label'];
    }

    $field_map = $this->entityFieldManager()->getFieldMap();
    $options = ['' => "- Select -"];
    foreach ($field_map['media'] as $field => $field_data) {
      $options[$field] = $field . " (" . implode(",", $field_data['bundles']) . ")";
    }

    $form['bundle_options'] = [
      '#type' => 'select',
      '#title' => $this->t("Which bundles"),
      '#options' => $bundle_options,
      '#default_value' => $config->get('bundle_options'),
      '#multiple' => TRUE,
      '#required' => TRUE,
    ];

    $form['process_luminance'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Process Luminance"),
      '#default_value' => $config->get('process_luminance'),
    ];

    $form['luminance_field'] = [
      '#type' => 'select',
      '#title' => $this->t("Luminance Field"),
      '#description' => $this->t("Should be a float field."),
      '#options' => $options,
      '#default_value' => $config->get('luminance_field'),
      '#states' => [
        'visible' => [
          ':input[name="process_luminance"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['quad_luminance_field'] = [
      '#type' => 'select',
      '#title' => $this->t("Quadrant Luminance Field"),
      '#description' => $this->t("Should be a flot field with cardinality >= 4."),
      '#options' => $options,
      '#default_value' => $config->get('quad_luminance_field'),
      '#states' => [
        'visible' => [
          ':input[name="process_luminance"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['process_std_deviation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Process Color Standard Deviation"),
      '#default_value' => $config->get('process_std_deviation'),
      '#prefix' => "<br><hr><br>",
    ];

    $form['std_deviation_field'] = [
      '#type' => 'select',
      '#title' => $this->t("Standard Deviation Field"),
      '#description' => $this->t("Should be a number float field"),
      '#options' => $options,
      '#default_value' => $config->get('std_deviation_field'),
      '#states' => [
        'visible' => [
          ':input[name="process_std_deviation"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['process_avg_color'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Process Average Color"),
      '#default_value' => $config->get('process_avg_color'),
      '#prefix' => "<br><hr><br>",
    ];

    $form['avg_color_field'] = [
      '#type' => 'select',
      '#title' => $this->t("Average Color Field"),
      '#description' => $this->t("Should be a color field."),
      '#options' => $options,
      '#default_value' => $config->get('avg_color_field'),
      '#states' => [
        'visible' => [
          ':input[name="process_avg_color"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['process_color_palette'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Process Color Palette"),
      '#default_value' => $config->get('process_color_palette'),
      '#prefix' => "<br><hr><br>",
    ];

    $form['color_palette_count'] = [
      '#type' => 'number',
      '#title' => $this->t("Color Palette Count"),
      '#description' => $this->t('Targeted count for the color palette'),
      '#default_value' => $config->get('color_palette_count') ?? 6,
      '#min' => 1,
      '#step' => 1,
      '#states' => [
        'visible' => [
          ':input[name="process_color_palette"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['color_palette_field'] = [
      '#type' => 'select',
      '#title' => $this->t("Color Palette Field"),
      '#description' => $this->t("Should be a color field with cardinality >= 6."),
      '#options' => $options,
      '#default_value' => $config->get('color_palette_field'),
      '#states' => [
        'visible' => [
          ':input[name="process_color_palette"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['process_histogram_string'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Process Histogram String"),
      '#default_value' => $config->get('process_histogram_string'),
      '#prefix' => "<br><hr><br>",
    ];

    $form['histogram_string_field'] = [
      '#type' => 'select',
      '#title' => $this->t("Histogram String Field"),
      '#description' => $this->t("Should be a long text field"),
      '#options' => $options,
      '#default_value' => $config->get('histogram_string_field'),
      '#suffix' => "<br><hr><br>",
      '#states' => [
        'visible' => [
          ':input[name="process_histogram_string"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['color_bins'] = [
      '#type' => 'multivalue',
      '#title' => $this->t('Color Bins'),
      '#cardinality' => MultiValue::CARDINALITY_UNLIMITED,
      '#default_value' => $config->get('color_bins'),
      '#description' => $this->t('Black as the last color is considered "empty"'),
      'color' => [
        '#type' => 'color',
        '#title' => $this->t('Color'),
        '#width' => 4,
      ],

      // phpcs:disable
      // 'color_dist_field' => [
      //   '#type' => 'select',
      //   '#title' => $this->t("Color distance field"),
      //   '#description' => $this->t("Should be a multi-value number float field."),
      //   '#options' => $options,
      // ],
      // 'hue_dist_field' => [
      //   '#type' => 'select',
      //   '#title' => $this->t("Hue distance field"),
      //   '#description' => $this->t("Should be a multi-value number float field."),
      //   '#options' => $options,
      // ],

      // phpcs:enable
    ];

    return parent::buildForm($form, $form_state);
  }

  // phpcs:disable

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  // phpcs:enable

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Check last color bin. Since there is no "empty" for a color field, this
    // will always add a new item on save. So we remove it here.
    $color_bins_value = $form_state->getValue('color_bins');
    while (end($color_bins_value)['color'] == "#000000") {
      array_pop($color_bins_value);
    }

    // Retrieve the configuration.
    $this->configFactory->getEditable(static::SETTINGS)
      // Set the submitted configuration setting.
      ->set('bundle_options', $form_state->getValue('bundle_options'))
      ->set('process_luminance', $form_state->getValue('process_luminance'))
      ->set('luminance_field', $form_state->getValue('luminance_field'))
      ->set('quad_luminance_field', $form_state->getValue('quad_luminance_field'))
      ->set('process_std_deviation', $form_state->getValue('process_std_deviation'))
      ->set('std_deviation_field', $form_state->getValue('std_deviation_field'))
      ->set('process_avg_color', $form_state->getValue('process_avg_color'))
      ->set('avg_color_field', $form_state->getValue('avg_color_field'))
      ->set('process_color_palette', $form_state->getValue('process_color_palette'))
      ->set('color_palette_count', (int) $form_state->getValue('color_palette_count'))
      ->set('color_palette_field', $form_state->getValue('color_palette_field'))
      ->set('process_histogram_string', $form_state->getValue('process_histogram_string'))
      ->set('histogram_string_field', $form_state->getValue('histogram_string_field'))
      ->set('color_bins', $color_bins_value)
      ->save();

    parent::submitForm($form, $form_state);

    $this->messenger->addMessage('You have saved your settings.');
  }

}
