<?php

namespace Drupal\friggeri_cv\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\friggeri_cv\Entity\Profile;
use Drupal\friggeri_cv\Service\PdfService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns a Friggeri CV in pdf format.
 */
class PdfController extends ControllerBase {

  /**
   * The friggeri_cv.pdf service.
   *
   * @var \Drupal\friggeri_cv\Service\PdfService
   */
  protected $pdfService;

  /**
   * The controller constructor.
   *
   * @param \Drupal\friggeri_cv\Service\PdfService $pdf_service
   *   The friggeri_cv.pdf service.
   */
  public function __construct(PdfService $pdf_service) {
    $this->pdfService = $pdf_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('friggeri_cv.pdf')
    );
  }

  /**
   * Get PDF.
   */
  public function pdf(Request $request, Profile $profile) {
    $id = $profile->id();
    $url = $this->pdfService->getProfilePdfUrl($id);

    return new RedirectResponse($url);
  }

}
