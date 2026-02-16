<?php

namespace Drupal\colorpalette;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines Track Progress service.
 */
class ColorPaletteUtility {

  /**
   * Color storage instance.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $colorStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new ColorPaletteUtility object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;
    $this->colorStorage = $entity_type_manager->getStorage('taxonomy_term');
  }

  /**
   * Data dialog options that defines modal structure.
   *
   * @return array
   *   An array of data dialog options.
   */
  public function getDataDialogOptions() {
    return [
      'width' => 568,
      'position' => ['my' => 'top', 'at' => 'top+100'],
      'draggable' => TRUE,
      'autoResize' => FALSE,
    ];
  }

  /**
   * Data dialog options for links that defines modal structure.
   *
   * @param array $options
   *   Additional options to be merged with defined options.
   *
   * @return array
   *   An array of data dialog options for the color button attributes.
   */
  public function getDialogLinkOptions(array $options = []) {
    $options = !empty($options)
      ? $options + $this->getDataDialogOptions()
      : $this->getDataDialogOptions();

    return [
      'attributes' => [
        'class' => ['use-ajax', 'button'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode($options),
      ],
    ];
  }

  /**
   * Generate ajax response to update the color state of the fields.
   *
   * @param array $data
   *   An array of data to build the JS commands.
   *
   * @return Drupal\Core\Ajax\AjaxResponse\AjaxResponse
   *   Ajax response with various JS commands.
   */
  public function generateAjaxResponse(array $data) {
    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(new CloseDialogCommand());

    // Update the field value.
    $ajax_response->addCommand(new InvokeCommand('[data-drupal-selector=' . $data['selector'] . ']', 'val', [$data['value']]));

    // Update color button class, html and background color.
    $ajax_response->addCommand(new InvokeCommand('[data-launch-button=' . $data['selector'] . '-btn] .color-btn', 'attr', ['class', 'color-btn hexcode-' . $data['background']]));
    $ajax_response->addCommand(new CssCommand('[data-launch-button=' . $data['selector'] . '-btn] .button', ['background' => $data['background'] ? '#' . $data['background'] : '']));
    $ajax_response->addCommand(new HtmlCommand('[data-launch-button=' . $data['selector'] . '-btn] .button', $data['html']));

    return $ajax_response;
  }

  /**
   * Verifies existance of a color with given hexcode.
   *
   * @param string $hexcode
   *   A color hexcode value.
   *
   * @return int
   *   An integer representing color term id.
   */
  public function isColorExisting($hexcode) {
    $colors = $this->colorStorage->getQuery()
      ->condition('vid', 'colorpalette_colors')
      ->condition('field_colorpalette_hexcode', $hexcode)
      ->range(0, 1)
      ->execute();

    return !empty($colors) ? reset($colors) : 0;
  }

  /**
   * Check for 'Administer palette' privilege.
   *
   * @return bool
   *   A boolean indicating privileged to administer palette or not.
   */
  public function isAdministerPaletteUser() {
    return $this->currentUser->hasPermission('administer palette');
  }

  /**
   * Loads the color (or filter tags) for a given id(s).
   *
   * @param array|int $id
   *   An array of or a single color (or filter tags) term id.
   *
   * @return object[]|object|null
   *   An integer representing color (or filter tags) term id.
   */
  public function loadColor($id) {
    if (is_array($id)) {
      return $this->colorStorage->loadMultiple($id);
    }

    return $this->colorStorage->load($id);
  }

  /**
   * Create a new color.
   *
   * @param string $hexcode
   *   A color hexcode value.
   * @param string $name
   *   Color name.
   * @param array|null $filter_tags
   *   An array of formated filter tags.
   *
   * @return object
   *   An integer representing color term id.
   */
  public function createNewColor($hexcode, $name, array $filter_tags) {
    $color = $this->colorStorage->create([
      'name' => $name,
      'vid' => 'colorpalette_colors',
      'status' => 1,
      'field_colorpalette_filter_tags' => $filter_tags,
      'field_colorpalette_hexcode' => $hexcode,
    ]);

    $color->save();

    return $color;
  }

  /**
   * Verifies existance of a color for given hexcode.
   *
   * @param array $filter_tags
   *   An array filter tag term ids.
   *
   * @return object[]|null
   *   An integer representing color term id.
   */
  public function getPaletteColors(array $filter_tags = []) {
    $query = $this->colorStorage->getQuery()
      ->condition('vid', 'colorpalette_colors')
      ->condition('status', 1)
      ->sort('weight')
      ->sort('name');

    // Filter color options based on available filter tags.
    if (!empty($filter_tags)) {
      $query->condition('field_colorpalette_filter_tags', $filter_tags, 'IN');
    }

    return $this->loadColor($query->execute());
  }

  /**
   * Extract target_ids from a list of submitted referenced entity values.
   *
   * @param array $target_ids
   *   Submitted values of referenced entities.
   *
   * @return array
   *   A plain array containing the target-ids/tids.
   */
  public function extractTargetIds(array $target_ids) {
    $tids = [];
    foreach ($target_ids as $target) {
      $tids[] = (int) $target['target_id'];
    }

    return $tids;
  }

}
