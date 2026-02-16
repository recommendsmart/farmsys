<?php

namespace Drupal\book_pdf\Controller;

use Drupal\book\BookExport;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Renderer;
use Drupal\node\Entity\Node;
use mikehaertl\wkhtmlto\Pdf;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for Book PDF routes.
 */
class BookPdfController extends ControllerBase {


  /**
   * The Book export service.
   *
   * @var \Drupal\book\BookExport
   */
  protected $bookExport;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The book_pdf logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The The book_pdf settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * BookPdfController constructor.
   *
   * @param \Drupal\book\BookExport $bookExport
   *   The Book export service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The book_pdf logger channel.
   * @param \Drupal\Core\Config\ImmutableConfig $settings
   *   The The book_pdf settings.
   */
  public function __construct(BookExport $bookExport, Renderer $renderer, LoggerInterface $logger, ImmutableConfig $settings) {
    $this->bookExport = $bookExport;
    $this->renderer = $renderer;
    $this->logger = $logger;
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('book.export'),
      $container->get('renderer'),
      $container->get('logger.channel.book_pdf'),
      $container->get('config.factory')->get('book_pdf.settings')
    );
  }

  /**
   * Clean a string so it can be used as a file name.
   *
   * @param string $string
   *   The string to clean.
   *
   * @return string
   *   The cleaned string.
   */
  protected function cleanString($string) {
    // Lower case everything.
    $string = strtolower($string);
    // Make alphanumeric (removes all other characters).
    $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
    // Clean up multiple dashes or whitespaces.
    $string = preg_replace("/[\s-]+/", " ", $string);
    // Convert whitespaces and underscore to dash.
    $string = preg_replace("/[\s_]/", "-", $string);
    return $string;
  }

  /**
   * Get a filename for the PDF based on the book title and date/time.
   *
   * @param \Drupal\node\Entity\Node $book
   *   The book node by which we can generate the filename from.
   *
   * @return string
   *   A string which can be used as the PDF attachment filename.
   *
   * @throws \Exception
   */
  protected function getFileName(Node $book) {
    $now = new \DateTime();
    return $this->cleanString($book->getTitle() . '-' . $now->format('Ymd')) . '.pdf';
  }

  /**
   * Returns the Book PDF attachment response.
   */
  public function sendPdf(Node $book) {
    $bookBuild = $this->bookExport->bookExportHtml($book);
    $bookString = $this->renderer->render($bookBuild);
    $options = [];
    $basicUser = $this->settings->get('basic_user');
    $basicPass = $this->settings->get('basic_pass');
    if (!empty($basicUser) && !empty($basicPass)) {
      $options['username'] = $basicUser;
      $options['password'] = $basicPass;
    }
    $pdf = new Pdf($options);
    $pdf->addPage($bookString);
    if (!$pdf->send($this->getFileName($book), FALSE)) {
      $this->logger->error('Error generating Book PDF. <br> %error', ['%error' => $pdf->getError()]);
      return new Response('Error generating Book PDF.', 404);
    }
    return [];
  }

}
