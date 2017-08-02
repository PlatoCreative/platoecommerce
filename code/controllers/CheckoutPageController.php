<?php
/**
 * Display the checkout page, with order form. Process the order - send the order details
 * off to the Payment class.
 *
 * @author Plato Creative
 * @copyright Copyright (c) 2017, Plato Creative
 * @package PlatoEcommerce
 * @subpackage customer
 */
class CheckoutPage_Controller extends Page_Controller {

	protected $orderProcessed = false;

	private static $allowed_actions = array (
		'index',
		'OrderForm',
		'LoginForm',
		'login'
	);

	public function init(){
		parent::init();
		Session::set('BackURL',$this->Link());

		// Include some CSS and javascript for the checkout page
		Requirements::css('plato-ecommerce/css/Shop.css');

		return array(
			'Content' => $this->Content,
			'Form' => $this->OrderForm()
		);
	}

	/**
	 * Include some CSS and javascript for the checkout page
	 *
	 * TODO why didn't I use init() here?
	 *
	 * @return Array Contents for page rendering
	 */
	 /*
	function index() {
		// Update stock levels
		// Order::delete_abandoned();

		Requirements::css('plato-ecommerce/css/Shop.css');

		return array(
			'Content' => $this->Content,
			'Form' => $this->OrderForm()
		);
	}
	*/

	function OrderForm() {
		$order = ShoppingCart::get_current_order();
		$member = Customer::currentUser() ? Customer::currentUser() : singleton('Customer');

		$form = OrderForm::create(
			$this,
			'OrderForm'
		)->disableSecurityToken();

		//Populate fields the first time form is loaded
		$form->populateFields();

		return $form;
	}
}
