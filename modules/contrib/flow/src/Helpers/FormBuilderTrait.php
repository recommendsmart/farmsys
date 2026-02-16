<?php

namespace Drupal\flow\Helpers;

use Drupal\Core\Form\FormBuilderInterface;

/**
 * Trait for Flow-related components making use of the form builder.
 */
trait FormBuilderTrait {

  /**
   * The service name of the form builder.
   *
   * @var string
   */
  protected static $formBuilderServiceName = 'form_builder';

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected FormBuilderInterface $formBuilder;

  /**
   * Set the form builder.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function setFormBuilder(FormBuilderInterface $form_builder): void {
    $this->formBuilder = $form_builder;
  }

  /**
   * Get the form builder.
   *
   * @return \Drupal\Core\Form\FormBuilderInterface
   *   The form builder.
   */
  public function getFormBuilder(): FormBuilderInterface {
    if (!isset($this->formBuilder)) {
      $this->formBuilder = \Drupal::service(self::$formBuilderServiceName);
    }
    return $this->formBuilder;
  }

}
