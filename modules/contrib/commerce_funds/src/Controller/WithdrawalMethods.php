<?php

namespace Drupal\commerce_funds\Controller;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserDataInterface;
use Drupal\commerce_funds\WithdrawalMethodManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Lists withdrawal methods in user profile.
 */
class WithdrawalMethods extends ControllerBase {

  /**
   * Defines variables to be used later.
   *
   * @var \Drupal\commerce_funds\WithdrawalMethodManager
   */
  protected $withdrawalMethodManager;

  /**
   * The user data interface.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Class constructor.
   */
  public function __construct(WithdrawalMethodManager $withdrawal_method_manager, UserDataInterface $user_data) {
    $this->withdrawalMethodManager = $withdrawal_method_manager;
    $this->userData = $user_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.withdrawal_method'),
      $container->get('user.data')
    );
  }

  /**
   * Display the list of available Withdrawal methods.
   *
   * @see WithdrawalMethodsManager
   *
   * @return array
   *   Return a renderable array.
   */
  public function content(AccountInterface $user, Request $request) {
    $all_methods = $this->config('commerce_funds.settings')->get('withdrawal_methods');
    $enabled_methods = array_filter($all_methods);
    // Prepares the table to host the methods.
    $build['withdrawal_methods'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Withdrawal method'),
        $this->t('Details'),
        $this->t('Operations'),
      ],
      '#attributes' => [
        'class' => [
          'withdrawal-method-table',
        ],
      ],
    ];

    if ($enabled_methods) {
      $i = 1;
      foreach ($enabled_methods as $method) {
        // Add css for themer.
        $build['withdrawal_methods'][$i]['#attributes'] = [
          'class' => [
            'withdrawal-method', $method,
          ],
        ];
        $build['withdrawal_methods'][$i]['name'] = [
          '#markup' => $this->t('@method', ['@method' => ucfirst($method)]),
        ];

        $method_user_data = $this->userData->get('commerce_funds', $user->id(), str_replace('-', '_', $method));
        if (!empty($method_user_data)) {
          foreach ($method_user_data as $data_name => $data) {
            $data_name = str_replace('_', ' ', $data_name);
            $build['withdrawal_methods'][$i]['details'][] = [
              '#type' => 'html_tag',
              '#tag' => 'div',
              '#value' => $this->t('@name : @data <br>', [
                '@name' => ucfirst(str_replace($method . '_', '', $data_name)),
                '@data' => $data,
              ]),
              '#attributes' => [
                'class' => [
                  'method-element',
                  $data_name,
                ],
              ],
            ];
          }
        }
        else {
          $build['withdrawal_methods'][$i][$method] = [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => $this->t('Not specified.'),
            '#attributes' => [
              'class' => [
                'method-none',
              ],
            ],
          ];
        }

        $build['withdrawal_methods'][$i]['edit'] = [
          '#title' => $this->t('Edit'),
          '#type' => 'link',
          '#url' => Url::fromRoute('commerce_funds.withdrawal_methods.edit', [
            'user' => $user->id(),
            'method' => $method,
          ], [
            'query' => [
              'destination' => $request->getRequestUri(),
            ],
          ]),
        ];
        $i++;
      }
    }

    return $build;
  }

  /**
   * Display the edit form for a method.
   *
   * @return array
   *   Return the method renderable array form.
   */
  public function editMethod(AccountInterface $user, $method) {
    $methods = $this->withdrawalMethodManager->getDefinitions();
    // Load the plugin (route parameter = plugin id).
    if (in_array($method, array_keys($methods))) {
      $class = $this->withdrawalMethodManager->getDefinition($method)['class'];
      $build = $this->formBuilder()->getForm($class);

      return $build;
    }
    // Make sure people reach page not found on other route paramater.
    else {
      throw new NotFoundHttpException();
    }
  }

}
