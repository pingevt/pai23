<?php

namespace Drupal\pai_quick_edit\Form;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Implements a form for filtering media items.
 */
class MediaQuickEditForm extends FormBase {

  /**
   * Entity Type Bundle Info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, RequestStack $request_stack = NULL) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quick_edit_media';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Set the method.
    $form_state->setMethod('get');

    // GET forms must not be cached, so that the page output responds without
    // caching.
    $form['#cache'] = [
      'max-age' => 0,
    ];

    // The after_build removes elements from GET parameters. See
    // TestForm::afterBuild().
    $form['#after_build'] = ['::afterBuild'];

    $values = $this->requestStack->getCurrentRequest()->query->all();

    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo("media");

    $media_bundles = [];
    foreach ($bundle_info as $key => $info) {
      $media_bundles[$key] = $info['label'];
    }

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search'),
      '#default_value' => $values['title'] ?? "",
    ];

    // phpcs:disable
    // $form['alt'] = [
    //   '#type' => 'checkbox',
    //   '#title' => $this->t('Empty Alt'),
    //   '#default_value' => $values['alt'] ?? FALSE,
    // ];
    // phpcs:enable

    $form['bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Types'),
      '#multiple' => TRUE,
      '#options' => $media_bundles,
      '#default_value' => $values['bundle'] ?? ['image'],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * Custom after build to remove elements from being submitted as variables.
   */
  public function afterBuild(array $element, FormStateInterface $form_state) {
    // Remove the form_token, form_build_id and form_id from the GET parameters.
    unset($element['form_token']);
    unset($element['form_build_id']);
    unset($element['form_id']);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // ...
  }

}
