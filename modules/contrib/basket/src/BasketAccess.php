<?php

namespace Drupal\basket;

use Drupal\Core\Session\AccountInterface;

/**
 * Class BasketAccess.
 */
class BasketAccess {

  /**
   * Drupal\basket\Basket definition.
   *
   * @var \Drupal\basket\Basket
   */
  protected $basket;
	
	/**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Constructs a new BasketAccess object.
   */
  public function __construct(Basket $Basket, AccountInterface $user) {
    $this->basket = $Basket;
		$this->user = $user;
  }
	
	/**
	 * @param $permission
	 * @param $options
	 *
	 * @return bool
	 */
	public function hasPermission(string $permission, array $options = []) {
		$access = NULL;
		if($this->user->id() == 1) {
			$access = TRUE;
		}
		/* Alter */
		if(is_null($access)) {
			$per = $permission;
			\Drupal::moduleHandler()->alter('basket_access', $access, $per, $options);
		}
		/* */
		if(is_null($access)) {
			$access = $this->user->hasPermission($permission);
		}
		return $access;
	}
}
