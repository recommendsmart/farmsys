<?php

namespace Drupal\collection\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\collection\Entity\CollectionInterface;
use Drupal\collection\Event\CollectionEvents;
use Drupal\collection\Event\CollectionItemFormSaveEvent;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Class CollectionNewNodeForm.
 */
class CollectionNewNodeForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new CollectionNewNodeForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher) {
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'collection_new_node_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, CollectionInterface $collection = NULL) {
    // Set the collection to which we are adding a node. This will be used in
    // the submit handler.
    $form_state->set('collection', $collection);
    $form_state->set('collection_item_type', '¯\_(ツ)_/¯');

    // Node label (e.g. title).
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#required' => TRUE,
    ];

    // Check the configuration for the allowed bundle options.
    $content_type_options = $this->getBundleOptions($collection);

    // Node bundle (e.g. content type).
    $form['bundle'] = [
      '#type' => 'radios',
      '#title' => $this->t('Type'),
      '#options' => $content_type_options,
      '#default_value' => (count($content_type_options) === 1) ? array_keys($content_type_options)[0] : [],
      '#required' => TRUE,
    ];

    if (empty($content_type_options)) {
      $form['missing_bundle_message'] = [
        '#markup' => t('<p>This collection does not allow any content types. Please check the  %collection_type configuration.</p>', [
          '%collection_type' => $collection->type->entity->toLink(NULL, 'edit-form')->toString()
        ]),
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#weight' => 10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $collection = $form_state->get('collection');
    $collection_item_type = $form_state->get('collection_item_type');
    $node_storage = $this->entityTypeManager->getStorage('node');
    $collection_item_storage = $this->entityTypeManager->getStorage('collection_item');

    // Create the new node stub (for later editing).
    $node = $node_storage->create([
      'type' => $form_state->getValue('bundle'),
      'title' => $form_state->getValue('label'),
      'status' => FALSE,
      'uid' => \Drupal::currentUser()->id(),
    ]);

    if ($node->save()) {
      // Add the new node to the form state so that other submit handlers can
      // access it.
      $form_state->set('node', $node);
      $form_state->setRedirect('entity.node.edit_form', ['node' => $node->id()],
        ['query' => ['destination' => '/collection/' . $collection->id() . '/items']]
      );
    }

    // Check if the type was set in a presubmit hook. Otherwise, use the first
    // available option.
    if ($collection_item_type === '¯\_(ツ)_/¯') {
      $allowed_types = $collection->type->entity->getAllowedCollectionItemTypes('node', $node->bundle());
      $collection_item_type = reset($allowed_types);
    }

    // Add the node to this collection.
    $collection_item = $collection_item_storage->create([
      'type' => $collection_item_type,
      'collection' => $collection,
      'item' => $node,
      'canonical' => TRUE,
    ]);

    if ($collection_item->save()) {
      $form_state->set('collection_item', $collection_item);

      // Dispatch the CollectionItemFormSaveEvent.
      $event = new CollectionItemFormSaveEvent($collection_item, SAVED_NEW);
      $this->eventDispatcher->dispatch(CollectionEvents::COLLECTION_ITEM_FORM_SAVE, $event);
    }
  }

  /**
   * Provides an add title callback for add node to collection form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return string
   *   The title for the entity add page, if the bundle was found.
   */
  public function addTitle(RouteMatchInterface $route_match) {
    $collection = $route_match->getParameter('collection');
    return $this->t('Add content to @collection', [
      '@collection' => $collection->label(),
    ]);
  }

  /**
   * Checks the configuration for allowed bundles.
   *
   * @param CollectionInterface $collection
   *
   * @return array
   *    An array of node bundles allowed in this collection, keyed by bundle
   *    machine name with the label as the value
   */
  protected function getBundleOptions(CollectionInterface $collection) {
    $bundle_options = [];
    $allowed_bundles = $collection->type->entity->getAllowedEntityBundles('node');

    if (empty($allowed_bundles['node'])) {
      return $bundle_options;
    }

    foreach ($allowed_bundles['node'] as $bundle) {
      $access = $this->entityTypeManager->getAccessControlHandler('node')->createAccess($bundle, NULL, [], TRUE);

      if ($access->isAllowed() && $type = $this->entityTypeManager->getStorage('node_type')->load($bundle)) {
        $bundle_options[$bundle] = $type->label();
      }
    }

    return $bundle_options;
  }

}
