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
class VocabStorageTest extends KernelTestBase {

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
   * Tests ::getToplevelTids()
   */
  public function testGetToplevelTids() {
    /** @var \Drupal\config_terms\VocabStorageInterface $vocab_storage */
    $vocab_storage = $this->container->get('entity_type.manager')
      ->getStorage('config_terms_vocab');
    $tids = $vocab_storage->getToplevelTids([]);
    $this->assertSame([], $tids);
    $term = ConfigTerm::create(['id' => 'test', 'vid' => 'test_vid']);
    $term->save();
    $tids = $vocab_storage->getToplevelTids(['test_vid']);
    $this->assertSame(['test'], $tids);

    // Check example config from config_terms_test module.
    $tids = $vocab_storage->getToplevelTids(['example_type']);
    $this->assertSame(['ex_something'], $tids);
  }

  /**
   * Tests ::getVocabsList()
   */
  public function testGetVocabsList() {
    /** @var \Drupal\config_terms\VocabStorageInterface $vocab_storage */
    $vocab_storage = $this->container->get('entity_type.manager')
      ->getStorage('config_terms_vocab');

    // Check example config from config_terms_test module.
    $list = $vocab_storage->getVocabsList();
    $expected = ['example_type' => 'Example Type'];
    $this->assertSame($expected, $list);

    // Create additional vocabs with various weights and labels.
    $vocab_data = ['label' => 'ZZZ', 'id' => 'test_zzz0', 'weight' => 0];
    ConfigVocab::create($vocab_data)->save();
    $vocab_data = ['label' => 'AAA', 'id' => 'test_aaa0', 'weight' => 0];
    ConfigVocab::create($vocab_data)->save();
    $vocab_data = ['label' => 'Test', 'id' => 'test_vid5', 'weight' => -5];
    ConfigVocab::create($vocab_data)->save();
    $vocab_data = ['label' => 'AAA', 'id' => 'test_aaa2', 'weight' => -2];
    ConfigVocab::create($vocab_data)->save();

    /**
     * @var \Drupal\config_terms\Entity\VocabInterface $loaded
     */
    $loaded = $vocab_storage->loadUnchanged('test_vid5');
    $this->assertEquals(-5, $loaded->getWeight());

    // Lower weight should come first, then sorted by label.
    $expected = [
      'test_vid5' => 'Test',
      'test_aaa2' => 'AAA',
      'test_aaa0' => 'AAA',
      'example_type' => 'Example Type',
      'test_zzz0' => 'ZZZ',
    ];
    $list = $vocab_storage->getVocabsList();
    $this->assertSame($expected, $list);
  }

}
