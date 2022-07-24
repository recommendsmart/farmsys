<?php

namespace Drupal\basket\Plugins\Twig;

use Drupal\Component\Uuid\Uuid;
use Twig\TwigFilter;

/**
 * Twig functions.
 */
class BasketTwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'basket';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('basket_t', [$this, 'basketT'], ['is_safe' => ['html']]),
      new \Twig_SimpleFunction('basket_image', [$this, 'basketImage'], ['is_safe' => ['html']]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new TwigFilter('basket_number_format', [$this, 'basketNumberFormat']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function basketT($text, $args = [], $context = 'basket') {
    return \Drupal::service('Basket')->Translate($context)->trans($text, $args);
  }

  /**
   * {@inheritdoc}
   */
  public function basketNumberFormat($number, $decimals = 2, $dec_point = ',', $thousands_sep = ' ') {
    if ($number == (int) $number) {
      $decimals = 0;
    }
    return number_format($number, $decimals, $dec_point, $thousands_sep);
  }

  /**
   * {@inheritdoc}
   */
  public function basketImage($property, $style = NULL, array $attributes = [], $responsive = FALSE, $check_access = TRUE) {
    // Determine property type by its value.
    if (preg_match('/^\d+$/', $property)) {
      $property_type = 'fid';
    }
    elseif (Uuid::isValid($property)) {
      $property_type = 'uuid';
    }
    else {
      $property_type = 'uri';
    }
    if ($property_type != 'uri') {
      $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties([$property_type => $property]);
      // To avoid ambiguity render nothing unless exact one image was found.
      if (count($files) != 1) {
        return;
      }
      $file = reset($files);

      if ($check_access && !$file->access('view')) {
        return;
      }
      $build = [
        '#uri'          => $file->getFileUri(),
        '#attributes'   => $attributes,
      ];
      if ($style) {
        if ($responsive) {
          $build['#type'] = 'responsive_image';
          $build['#responsive_image_style_id'] = $style;
        }
        else {
          $build['#theme'] = 'image_style';
          $build['#style_name'] = $style;
        }
      }
      else {
        $build['#theme'] = 'image';
      }
    }
    else {
      $build = [
        '#uri'          => $property,
        '#attributes'   => $attributes,
      ];
      if ($style) {
        if ($responsive) {
          $build['#type'] = 'responsive_image';
          $build['#responsive_image_style_id'] = $style;
        }
        else {
          $build['#theme'] = 'image_style';
          $build['#style_name'] = $style;
        }
      }
      else {
        $build['#theme'] = 'image';
      }
    }
    return $build;
  }

}
