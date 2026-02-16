<?php

namespace Drupal\friggeri_cv\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;
use Drupal\friggeri_cv\Entity\Profile;
use mikehaertl\wkhtmlto\Pdf;

/**
 * Helps to generate the Friggeri CV pdf of a given profile.
 */
class PdfService {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Constructs a PdfService object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   */
  public function __construct(FileSystemInterface $file_system, LoggerChannelFactoryInterface $logger) {
    $this->fileSystem = $file_system;
    $this->logger = $logger->get('friggeri_cv.pdf');
  }

  /**
   * Gets the absolute url string of the Friggeri CV in html format.
   *
   * @param int $id
   *   The id of the profile.
   *
   * @return \Drupal\Core\GeneratedUrl|string
   *   The absolute url string of the profile.
   */
  public function getProfileUrl(int $id) {
    $url = Url::fromRoute('entity.profile.canonical', ['profile' => $id]);

    return $url->setAbsolute()->toString();
  }

  /**
   * Builds the pdf wrapper around wkhtmltopdf command.
   *
   * @param int $id
   *   The profile id.
   *
   * @return \mikehaertl\wkhtmlto\Pdf
   *   The Pdf wrapper.
   */
  public function getProfilePdf(int $id) {
    $html = $this->getProfileUrl($id);
    Cache::getBins()['page']->delete($html . ":");
    $pdf = new Pdf($html);
    $pdf->setOptions([
      'margin-top' => 0,
      'margin-bottom' => 0,
      'margin-left' => 0,
      'margin-right' => 0,
    ]);

    return $pdf;
  }

  /**
   * Generates the pdf and returns the path to it.
   *
   * @param int $id
   *   The profile id.
   *
   * @return false|string
   *   The generated pdf url.
   */
  public function getProfilePdfUrl(int $id) {
    $pdf = $this->getProfilePdf($id);
    $dir = 'public://pdf/profile/';
    $filename = Profile::load($id)->getName() . '.pdf';
    $this->fileSystem->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    if (!$pdf->saveAs($dir . $filename)) {
      $this->logger->error($pdf->getCommand());
      $this->logger->error($pdf->getError());
      return FALSE;
    }
    else {
      return file_create_url($dir . $filename);
    }
  }

}
