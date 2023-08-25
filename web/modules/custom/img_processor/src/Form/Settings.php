<?php

namespace Drupal\img_processor\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Messenger\MessengerInterface;
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

    $form['process_avg_color'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Process Average Color"),
      '#default_value' => $config->get('process_avg_color'),
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

    // Retrieve the configuration.
    $this->configFactory->getEditable(static::SETTINGS)
      // Set the submitted configuration setting.
      ->set('bundle_options', $form_state->getValue('bundle_options'))
      ->set('process_luminance', $form_state->getValue('process_luminance'))
      ->set('luminance_field', $form_state->getValue('luminance_field'))
      ->set('quad_luminance_field', $form_state->getValue('quad_luminance_field'))
      ->set('process_avg_color', $form_state->getValue('process_avg_color'))
      ->set('avg_color_field', $form_state->getValue('avg_color_field'))
      ->set('process_color_palette', $form_state->getValue('process_color_palette'))
      ->set('color_palette_field', $form_state->getValue('color_palette_field'))
      ->save();

    parent::submitForm($form, $form_state);

    $this->messenger->addMessage('You have saved your settings.');
  }

}
