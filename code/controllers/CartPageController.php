<?php
/**
 * Display the cart page, with cart form. Handle cart form actions.
 *
 * @author Plato Creative
 * @copyright Copyright (c) 2017, Plato Creative
 * @package PlatoEcommerce
 * @subpackage customer
 */
class CartPage_Controller extends Page_Controller {

	private static $allowed_actions = array (
		'index',
		'CartForm',
		'RemoveItem'
	);

	/**
	 * Include some CSS for the cart page.
	 *
	 * @return Array Contents for page rendering
	 */
	function index() {
		//Update stock levels
		//Order::delete_abandoned();

		Requirements::css('plato-ecommerce/css/Shop.css');

		return array(
			 'Content' => $this->Content,
			 'Form' => $this->Form
		);
	}

	/**
	 * Form including quantities for items for displaying on the cart page.
	 *
	 * @return CartForm A new cart form
	 */
	function CartForm() {
		return CartForm::create(
			$this,
			'CartForm'
		)->disableSecurityToken();
	}

	/*
	*	Remove items from the cart
	*/
	function RemoveItem(){
		if(Director::is_ajax()){
			$params = $this->getURLParams();
			if(isset($params['ID']) && $params['ID'] != ''){
				$item = Item::get()->byID(Convert::raw2sql($params['ID']));
				if($item){
					$result = $item->Delete();
					$currentOrder = ShoppingCart::get_current_order();
					$currentOrder->updateTotal();

					return Convert::array2json(array(
						'result' => $result
					));
				}
			}
			return;
		}
	}
}
