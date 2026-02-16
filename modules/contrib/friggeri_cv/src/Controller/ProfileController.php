<?php

namespace Drupal\friggeri_cv\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\RendererInterface;
use Drupal\friggeri_cv\ProfileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns a Friggeri CV in html format.
 */
class ProfileController extends ControllerBase {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * Get Friggeri CV in html format.
   */
  public function profile(Request $request, ProfileInterface $profile) {

    $build = [
      '#type' => 'html',
      '#theme' => 'html__firggeri_cv',
      'page' => [
        "#theme" => "profile",
        "#profile" => $profile,
        '#attached' => [
          'library' => [
            'friggeri_cv/bootstrap-cdn',
            'friggeri_cv/font-awesome',
            'friggeri_cv/profile',
          ],
        ],
        '#cache' => [
          'tags' => ['profile_list'],
        ],
      ],
    ];

    $html = $this->renderer->renderRoot($build);
    $response = new HtmlResponse();
    $response->setContent($html);
    $response->setAttachments($build['#attached']);
    $response->addCacheableDependency($build['page']['#cache']);

    return $response;
  }

}
