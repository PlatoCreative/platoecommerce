<?php
/**
 * Displays a product, add to cart form, gets options and variation price for a {@link Product}
 * via AJAX.
 *
 * @author Plato Creative
 * @copyright Copyright (c) 2017, Plato Creative
 * @package PlatoEcommerce
 * @subpackage product
 */
class Product_Controller extends Page_Controller {

	/**
	 * Allowed actions for this controller
	 *
	 * @var Array
	 */
	private static $allowed_actions = array(
		'index',
		'productform',
		'ProductAdd'
	);

	private static $url_handlers = array(
	);

	/**
	 * Include some CSS and set the dataRecord to the current Product that is being viewed.
	 *
	 * @see Page_Controller::init()
	 */
	public function init(){
		parent::init();

		// CSS & JS
		Requirements::javascript('plato-ecommerce/javascript/Attribute_OptionField.js');
		$params = $this->getURLParams();

		if($params['ID'] != '') {
			$product = $this->getProductFromUrl();
			if($product && $product->exists()){
				$this->dataRecord = $product;
				$this->failover = $this->dataRecord;
				$request = $this->request;

				// Handle Actions - TODO: work out why this doesn't work normally
				if(isset($params['Action']) && $params['Action'] != ''){
					$val = $this->handleAction($request, $params['Action']);
					if(Director::is_ajax()){
						die($val);
					}
				}
			} else {
				return $this->httpError(404);
			}
		} else {
			return $this->httpError(404);
		}

		$this->extend('onInit');
	}

	public function Product(){
		return $this->getProductFromUrl();
	}

	public function getProductFromUrl($urlVar = null){
		if(!$urlVar){
			$params = $this->getURLParams();
			if($params['ID'] != '') {
				$urlVar = $params['ID'];
			} else {
				return false;
			}
		}
		$product = Product::get()->filter(array('URLSegment' => Convert::raw2sql($urlVar)))->first();
		if(!$product){
			$product = Product::get()->byID(Convert::raw2sql($urlVar));
		}
		return $product;
	}

	public function productform($quantity = null, $redirectURL = null) {
		$form = ProductForm::create(
			$this,
			'ProductForm',
			$quantity,
			$redirectURL
		)->disableSecurityToken();

		Session::set('ProductForm', $form);

		return $form;
	}

	/**
	 * Add an item to the current cart ({@link Order}) for a given {@link Product}.
	 *
	 * @param $request
	 *
	 */
	public function ProductAdd(SS_HTTPRequest $request) {
		$form = Session::get('ProductForm');
		if($request && $form){
			$cart = ShoppingCart::get_current_order(true);
			$data = $request->postVars();
			$form->setRequest($request);
			$form->loadDataFrom($data);

			$added = $cart->addItem(
				$form->getProduct(),
				$form->getVariation(),
				$form->getQuantity(),
				$form->getOptions()
			);

			Session::clear('ProductForm');

			if(Director::is_ajax()){
				return Convert::array2json(array(
					'result' => $added->exists(),
					'message' => $added ? 'Successfully added to your cart.' : 'There was an error updating your cart. Please try again.'
				));
			} else {
				//Show feedback if redirecting back to the Product page
				if (!$this->getRequest()->requestVar('Redirect')) {
					$cartPage = DataObject::get_one('CartPage');
					$message = _t('ProductForm.PRODUCT_ADDED', 'The product was added to your cart.');
					if ($cartPage->exists()) {
						$message = _t(
							'ProductForm.PRODUCT_ADDED_LINK',
							'The product was added to {openanchor}your cart{closeanchor}.',
							array(
								'openanchor' => "<a href=\"{$cartPage->Link()}\">",
								'closeanchor' => "</a>"
							)
						);
					}
					$form->sessionMessage(
						DBField::create_field("HTMLText", $message),
						'good',
						false
					);
				}

				$form->goToNextPage();
			}

		} else {
			if(Director::is_ajax()){
				return Convert::array2json(array(
					'result' => false,
					'message' => 'There was an error updating your cart. Please try again.'
				));
			} else {
				$message = _t('ProductForm.PRODUCT_ERROR', 'There was an error updating your cart. Please try again');
				$form->sessionMessage(
					DBField::create_field("HTMLText", $message),
					'bad',
					false
				);
			}
		}
	}
}
