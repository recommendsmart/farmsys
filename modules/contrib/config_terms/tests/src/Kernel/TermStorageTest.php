<?php

namespace Drupal\Tests\config_terms\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\config_terms\Entity\Term as ConfigTerm;
use Drupal\config_terms\Entity\Vocab as ConfigVocab;

/**
 * Tests basic module functionality.
 *
 * @group Config Terms
 */
class TermStorageTest extends KernelTestBase {

  /**
   * The modules required for each test.
   *
   * @var array
   */
  public static $modules = ['config_terms', 'config_terms_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['config_terms', 'config_terms_test']);
  }

  /**
   * Tests ::loadTree()
   */
  public function testloadTree() {
    /** @var \Drupal\config_terms\TermStorageInterface $term_storage */
    $term_storage = $this->container->get('entity_type.manager')
      ->getStorage('config_terms_term');
    // Check example config from config_terms_test module.
    $list = $term_storage->loadTree('example_type');
    $this->assertCount(1, $list);
    $term = end($list);
    $this->assertSame('ex_something', $term->id());
    // Create additional vocab.
    $vocab_data = ['label' => 'ZZZ', 'id' => 'test_zzz', 'weight' => 0];
    ConfigVocab::create($vocab_data)->save();
    $term_data = [
      'id' => 'test1',
      'vid' => 'test_zzz',
      'label' => 'Test',
    ];
    ConfigTerm::create($term_data)->save();
    $term_data = [
      'id' => 'test2',
      'vid' => 'test_zzz',
      'label' => 'Test',
      'weight' => -5,
    ];
    ConfigTerm::create($term_data)->save();
    $term_data = [
      'id' => 'child1',
      'vid' => 'test_zzz',
      'label' => 'Child1',
      'parents' => ['test2'],
    ];
    ConfigTerm::create($term_data)->save();
    $term_data = [
      'id' => 'child2',
      'vid' => 'test_zzz',
      'label' => 'Child2',
      'parents' => ['test2'],
      'weight' => -10,
    ];
    ConfigTerm::create($term_data)->save();
    $term_data = [
      'id' => 'test3',
      'vid' => 'test_zzz',
      'label' => 'Test',
      'weight' => 5,
    ];
    ConfigTerm::create($term_data)->save();
    $list = $term_storage->loadTree('test_zzz');
    $ids = array_map([$this, 'getId'], $list);
    $this->assertSame(['test2', 'child2', 'child1', 'test1', 'test3'], $ids);
  }

  /**
   * Tests ::loadChildren()
   */
  public function testLoadChildren() {
    /** @var \Drupal\config_terms\TermStorageInterface $term_storage */
    $term_storage = $this->container->get('entity_type.manager')
      ->getStorage('config_terms_term');
    $vocab_data = ['label' => 'ZZZ', 'id' => 'test_zzz', 'weight' => 0];
    ConfigVocab::create($vocab_data)->save();
    $term_data = [
      'id' => 'test2',
      'vid' => 'test_zzz',
      'label' => 'Test',
      'weight' => -5,
    ];
    ConfigTerm::create($term_data)->save();
    $term_data = [
      'id' => 'child1',
      'vid' => 'test_zzz',
      'label' => 'Child1',
      'parents' => ['test2'],
    ];
    ConfigTerm::create($term_data)->save();
    $term_data = [
      'id' => 'child2',
      'vid' => 'test_zzz',
      'label' => 'Child2',
      'parents' => ['test2'],
      'weight' => -10,
    ];
    ConfigTerm::create($term_data)->save();
    // The label Child0 sorts before Child1.
    $term_data = [
      'id' => 'child3',
      'vid' => 'test_zzz',
      'label' => 'Child0',
      'parents' => ['test2'],
    ];
    ConfigTerm::create($term_data)->save();
    $children = $term_storage->loadChildren('test2');
    $this->assertSame(['child2', 'child3', 'child1'], array_keys($children));
  }

  /**
   * Helper function to get term IDs from an array of term objects.
   *
   * @param \Drupal\config_terms\Entity\Term $term
   *   A term.
   *
   * @return string
   *   The term ID.
   */
  protected function getId(ConfigTerm $term) {
    return $term->id();
  }

}
