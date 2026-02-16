<?php

namespace Drupal\Tests\commerce_invoice\Kernel;

use Drupal\commerce_invoice\Entity\Invoice;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\profile\Entity\Profile;

/**
 * Tests the invoice file manager.
 *
 * @coversDefaultClass \Drupal\commerce_invoice\InvoiceFileManager
 * @group commerce_invoice
 */
class InvoiceFileManagerTest extends InvoiceKernelTestBase {

  /**
   * The invoice file manager.
   *
   * @var \Drupal\commerce_invoice\InvoiceFileManagerInterface
   */
  protected $fileManager;

  /**
   * The profile.
   *
   * @var \Drupal\profile\Entity\Profile
   */
  protected $profile;

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);
    $profile = Profile::create([
      'type' => 'customer',
      'address' => [
        'country_code' => 'US',
        'postal_code' => '53177',
        'locality' => 'Milwaukee',
        'address_line1' => 'Pabst Blue Ribbon Dr',
        'administrative_area' => 'WI',
        'given_name' => 'Frederick',
        'family_name' => 'Pabst',
      ],
      'uid' => $this->user->id(),
    ]);
    $profile->save();
    $this->profile = $this->reloadEntity($profile);
    $this->fileManager = $this->container->get('commerce_invoice.invoice_file_manager');
  }

  /**
   * @covers ::getInvoiceFile
   */
  public function testGetInvoiceFile() {
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'title' => $this->randomString(),
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();

    $order = Order::create([
      'type' => 'default',
      'state' => 'completed',
      'store_id' => $this->store,
      'billing_profile' => $this->profile,
      'uid' => $this->user->id(),
      'order_items' => [$order_item],
    ]);
    $order->save();
    $order = $this->reloadEntity($order);
    $invoice = Invoice::create([
      'type' => 'default',
      'invoice_number' => '10',
      'orders' => [$order],
      'store_id' => $this->store->id(),
      'billing_profile' => $this->profile,
      'state' => 'draft',
      'uid' => $this->user->id(),
    ]);
    $invoice->save();
    $invoice = $this->reloadEntity($invoice);

    $file = $this->fileManager->getInvoiceFile($invoice);
    $this->assertNotEmpty($file);
    $this->assertRegExp('#private://(.*)\.pdf#', $file->getFileUri());
    $this->assertEquals('10-en-draft.pdf', $file->getFilename());
    $this->assertEquals('application/pdf', $file->getMimeType());

    $invoice = $this->reloadEntity($invoice);
    $invoice_file = $invoice->getFile();
    $this->assertNotEmpty($invoice_file);
    $this->assertEquals($file->id(), $invoice_file->id());

    // Assert that updating the invoice state clears the invoice file
    // reference.
    $invoice->getState()->applyTransitionById('confirm');
    $invoice->save();
    $file = $this->fileManager->getInvoiceFile($invoice);
    $this->assertNotEmpty($file);
    $this->assertRegExp('#private://(.*)\.pdf#', $file->getFileUri());
    $this->assertEquals('10-en-pending.pdf', $file->getFilename());
    $this->assertEquals('application/pdf', $file->getMimeType());
    $this->assertEquals($file->id(), $invoice->getFile()->id());
  }

}
