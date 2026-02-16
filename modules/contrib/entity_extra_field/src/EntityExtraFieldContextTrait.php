<?php

namespace Drupal\entity_extra_field;

use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;

/**
 * Define the entity extra field context trait.
 */
trait EntityExtraFieldContextTrait {

  /**
   * Apply the plugin runtime contexts.
   *
   * @param \Drupal\Core\Plugin\ContextAwarePluginInterface $plugin
   *   The plugin instance.
   * @param array $contexts
   *   An array of contexts that are not provided by the
   *   \Drupal\Core\Plugin\Context\ContextRepositoryInterface::getAvailableContexts()
   *   method.
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   * @throws \Drupal\Component\Plugin\Exception\MissingValueContextException
   */
  protected function applyPluginRuntimeContexts(
    ContextAwarePluginInterface $plugin,
    array $contexts = []
  ): void {
    $context_repository = $this->getContextRepository();

    $current_contexts = $context_repository->getAvailableContexts();
    $context_ids = array_keys($current_contexts);

    $runtime_context_ids = array_values(
      array_filter($context_ids, static function ($context_id) {
        return strpos($context_id, '@') === 0;
      })
    );
    $contexts += $context_repository->getRuntimeContexts($runtime_context_ids);
    $contexts += array_intersect_key(
      $current_contexts,
      array_flip(array_diff($context_ids, $runtime_context_ids))
    );

    $this->getContextHandler()->applyContextMapping($plugin, $contexts);
  }

  /**
   * Get the context handler service.
   *
   * @return \Drupal\Core\Plugin\Context\ContextHandlerInterface
   *   The context handler.
   */
  protected function getContextHandler(): ContextHandlerInterface {
    return \Drupal::service('context.handler');
  }

  /**
   * Get the context repository service.
   *
   * @return \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   *   The context repository.
   */
  protected function getContextRepository(): ContextRepositoryInterface {
    return \Drupal::service('context.repository');
  }

}
