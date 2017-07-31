<?php
class ProductCategory_Products extends DataObject {
	private static $db = array(
		'ProductOrder' => 'Int'
	);

	private static $has_one = array(
		'ProductCategory' => 'ProductCategory',
		'Product' => 'Product'
	);
}
