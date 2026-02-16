<?php

namespace Drupal\properties_field\Plugin\Validation\Constraint;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the properies unique constraint.
 */
class UniquePropertiesConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($item, Constraint $constraint) {
    if (!$this->isUniqueValue($item, 'label')) {
      /** @var \Drupal\properties_field\Plugin\Validation\Constraint\UniquePropertiesConstraint $constraint */
      $this->context->buildViolation($constraint->labelExistsMessage, [
        '@label' => $item->label,
      ])->atPath('label')->addViolation();
    }

    if (!$this->isUniqueValue($item, 'machine_name')) {
      /** @var \Drupal\properties_field\Plugin\Validation\Constraint\UniquePropertiesConstraint $constraint */
      $this->context->buildViolation($constraint->machineNameExistsMessage, [
        '@machine_name' => $item->machine_name,
      ])->atPath('machine_name')->addViolation();
    }
  }

  /**
   * Check if the value of an item property is unique.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   * @param string $property
   *   The property to check.
   *
   * @return bool
   *   A boolean indicating if the property is unique.
   */
  protected function isUniqueValue(FieldItemInterface $item, $property) {
    $items = $item->getEntity()->get($item->getFieldDefinition()->getName());
    $counts = $this->getValueCounts($items, $property);
    $value = mb_strtolower($item->$property);

    return $counts[$value] === 1;
  }

  /**
   * Count the values of a specific property of all items.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field items.
   * @param string $property
   *   The property to count the values of.
   *
   * @return int[]
   *   The value counts.
   */
  protected function getValueCounts(FieldItemListInterface $items, $property) {
    $values = [];
    foreach ($items as $item) {
      $values[] = mb_strtolower($item->$property);
    }

    return array_count_values($values);
  }

}
