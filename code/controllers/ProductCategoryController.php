<?php
/**
 * Controller to display a ProductCategory and retrieve its Products.
 */
class ProductCategory_Controller extends Page_Controller {
	/**
	 * Allowed actions for this controller
	 *
	 * @var Array
	 */
	private static $allowed_actions = array(
		//'product'
	);

	private static $url_handlers = array(
		//'product//$ID!/$Action' => 'product'
	);

	/**
	 * Set number of products per page displayed in ProductCategory pages
	 *
	 * @var Int
	 */
	public static $products_per_page = 20;

	/**
	 * Set how the products are ordered on ProductCategory pages
	 *
	 * @see ProductCategory_Controller::Products()
	 * @var String Suitable for inserting in ORDER BY clause
	 */
	public static $products_ordered_by = "\"ProductCategory_Products\".\"ProductOrder\" ASC";
	//public static $products_ordered_by = "\"SiteTree\".\"ParentID\" ASC, \"SiteTree\".\"Sort\" ASC";

	/**
	 * Include some CSS.
	 *
	 * @see Page_Controller::init()
	 */
	function init() {
		parent::init();
		Requirements::css('plato-ecommerce/css/Shop.css');
	}
	/*
	function product(){
		$params = $this->getURLParams();
		$product = new Product_Controller();
		$product = $product->getProductFromUrl($params['ID']);
		$pc = Product_Controller::create($product);
		$this->handleRequest($this->request, $product);

		if($pc->hasAction($params['Action'])){
			return $pc->handleAction($this->request, $params['Action']);
		} else {
			return $pc->init();
		}
	}
	*/


	/**
	 * Get Products that have this ProductCategory set or have this ProductCategory as a parent in site tree.
	 * Supports pagination.
	 *
	 * @see Page_Controller::Products()
	 * @return FieldList
	 */
	 /*
	public function Products() {
		$limit = self::$products_per_page;
		$orderBy = self::$products_ordered_by;
		$cats = array($this->ID);
		foreach ($this->Children() as $child) {
			if ($child instanceof ProductCategory) {
				$cats[] = $child->ID;
			}
		}
		$in = "('" . implode("','", $cats) . "')";
		$products = Product::get()
			->where("\"ProductCategory_Products\".\"ProductCategoryID\" IN $in OR \"ParentID\" IN $in")
			->sort($orderBy)
			->leftJoin('ProductCategory_Products', "\"ProductCategory_Products\".\"ProductID\" = \"SiteTree\".\"ID\"");
		$this->extend('updateCategoryProducts', $products);
		$list = PaginatedList::create($products, $this->request)
			->setPageLength($limit);

		return $list;
	}
	*/

	// Return all products within this category as paginated list
	public function getProductsList(){
		$limit = self::$products_per_page;
		$orderBy = self::$products_ordered_by;

		$products = ArrayList::create($this->Products()->sort($orderBy)->toArray());

		// Go second level
		$children = $this->Children()->filter(array('ClassName' => 'ProductCategory'));
		foreach($children as $child){
			$childProducts = $child->Products()->sort($orderBy)->toArray();
			$products->merge($childProducts);

			// Go thrid level
			$childChildren = $child->Children()->filter(array('ClassName' => 'ProductCategory'));
			foreach($childChildren as $subChild){
				$childProducts = $subChild->Products()->sort($orderBy)->toArray();
				$products->merge($childProducts);
			}
		}

		$products->removeDuplicates('ID');

		$list = PaginatedList::create($products, $this->request)->setPageLength($limit);

		return $list;
	}
}
