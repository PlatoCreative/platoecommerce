<?php
/**
 * Represents a Product, which is a type of a {@link Page}. Products are managed in a seperate
 * admin area {@link ShopAdmin}. A product can have {@link Variation}s, in fact if a Product
 * has attributes (e.g Size, Color) then it must have Variations. Products are Versioned so that
 * when a Product is added to an Order, then subsequently changed, the Order can get the correct
 * details about the Product.
 */
class Product extends Page implements HiddenClass {
	/**
	 * Flag for denoting if this is the first time this Product is being written.
	 *
	 * @var Boolean
	 */
	protected $firstWrite = false;

	/**
	 * DB fields for Product.
	 *
	 * @var Array
	 */
	private static $db = array(
		'Price' => 'Decimal(19,8)',
		'SpecialPrice' => 'Decimal(19,8)',
		'Currency' => 'Varchar(3)',
		'ShortDescription' => 'HTMLText',
		'Stock' => 'Int',
		'SKU' => 'Varchar(250)'
	);/**
	 * Has many relations for Product.
	 *
	 * @var Array
	 */
	private static $has_many = array(
		//'Attributes' => 'Attribute',
		//'Options' => 'Option',
		'Variations' => 'Variation',
		'Images' => 'ProductImage'
	);

	private static $has_one = array(
		'MainCategory' => 'ProductCategory'
	);

	/**
	 * Belongs many many relations for Product
	 *
	 * @var Array
	 */
	private static $belongs_many_many = array(
		'ProductCategories' => 'ProductCategory'
	);

	/**
	 * Defaults for Product
	 *
	 * @var Array
	 */
	private static $defaults = array(
		'ParentID' => -1
	);

	/**
	 * Summary fields for displaying Products in the CMS
	 *
	 * @var Array
	 */
	private static $summary_fields = array(
		'Amount.Nice' => 'Price',
		'Title' => 'Title'
	);

	private static $searchable_fields = array(
		'Title' => array(
			'field' => 'TextField',
			'filter' => 'PartialMatchFilter',
			'title' => 'Name'
		),
		'ProductCategories.Title' => array(
			'field' => 'TextField',
			'filter' => 'PartialMatchFilter',
			'title' => 'Category'
		)
	);

	public function OnSpecial(){
		return ($this->SpecialPrice > 0) ? true : false;
	}

	public function ShowVariations(){
		$shopConfig = ShopConfig::current_shop_config();

		if($shopConfig->config()->HideVariationsOnSpecial){
			return $this->OnSpecial() ? false : true;
		}
		return true;
	}

	/**
	 * Actual price in base currency, can decorate to apply discounts etc.
	 *
	 * @return Price
	 */
	public function Amount() {
		// TODO: Multi currency
		$shopConfig = ShopConfig::current_shop_config();

		$amount = Price::create();
		$price = $this->OnSpecial() ? $this->SpecialPrice : $this->Price;
		$amount->setAmount($price);
		$amount->setCurrency($shopConfig->BaseCurrency);
		$amount->setSymbol($shopConfig->BaseCurrencySymbol);

		// Transform amount for applying discounts etc.
		$this->extend('updateAmount', $amount);

		return $amount;
	}

	/**
	 * Display price, can decorate for multiple currency etc.
	 *
	 * @return Price
	 */
	public function Price() {
		$amount = $this->Amount();

		//Transform price here for display in different currencies etc.
		$this->extend('updatePrice', $amount);

		return $amount;
	}

	/**
	 * Original price in base currency, can decorate to apply discounts etc.
	 *
	 * @return Price
	 */
	public function OriginalAmount() {
		// TODO: Multi currency
		$shopConfig = ShopConfig::current_shop_config();

		$amount = Price::create();
		$price = $this->Price;
		$amount->setAmount($price);
		$amount->setCurrency($shopConfig->BaseCurrency);
		$amount->setSymbol($shopConfig->BaseCurrencySymbol);

		// Transform amount for applying discounts etc.
		$this->extend('updateOriginalAmount', $amount);

		return $amount;
	}

	/**
	 * Display original price, can decorate for multiple currency etc.
	 *
	 * @return Price
	 */
	public function OriginalPrice() {
		$amount = $this->OriginalAmount();

		//Transform price here for display in different currencies etc.
		$this->extend('updateOriginalPrice', $amount);

		return $amount;
	}

	private static $casting = array(
		//'ProductCategory' => 'Varchar',
	);

	/**
	 * @see SiteTree::onBeforeWrite()
	 */
	public function onBeforeWrite() {
		parent::onBeforeWrite();
	}

	/**
	 * @see SiteTree::onAfterWrite()
	 */
	public function onAfterWrite() {
		parent::onAfterWrite();
	}

	/**
	 * @see SiteTree::onBeforePublish()
	 */
	public function onBeforePublish(){
		// Fixes the can't find stage issue
		if($this->stagesDiffer('Stage', 'Live')){
			$this->writeToStage('Stage');
		}
	}

	/**
	* @see SiteTree::onAfterPublish()
	*/
	public function onAfterPublish(){
		$this->ParentID = -1;

		//Save in base currency
		$shopConfig = ShopConfig::current_shop_config();
		$this->Currency = $shopConfig->BaseCurrency;

		// Check for main category ID and the categories list
		$productCategories = $this->ProductCategories();
		$maincat = ProductCategory::get()->where("SiteTree.ID =" . $this->MainCategoryID)->first();
		if($this->isInDB() && !in_array($maincat->ID, array_keys($productCategories->map()->toArray()))) {
			$productCategories->add($maincat);
		}
	}

	/**
	 * Delete images and clear categories link
	 *
	 * @see SiteTree::onBeforeDelete()
	 */
	public function onBeforeDelete(){
		parent::onBeforeDelete();

		if($this->Images() && $images = $this->Images()){
			foreach($images as $image){
				if($image->exists()){
					$image->delete();
				}
			}
		}
	}

	/**
	 * Unpublish products if they get deleted, such as in product admin area
	 *
	 * @see SiteTree::onAfterDelete()
	 */
	public function onAfterDelete() {
		parent::onAfterDelete();

		if($this->isPublished()) {
			$this->doUnpublish();
		}
	}

	public function canView($member = null){
		return true;
		// TODO FIX THIS
		$extended = $this->extendedCan(__FUNCTION__, $member);
		if($extended !== null){
			return $extended;
		}
		return Permission::check('ADMIN', 'any', $member);
	}

	public function canAddChildren($member = null){
		return false;
	}

	/**
	 * Set some CMS fields for managing Products
	 *
	 * @see Page::getCMSFields()
	 * @return FieldList
	 */
	public function getCMSFields() {
		$shopConfig = ShopConfig::current_shop_config();
		$fields = parent::getCMSFields();

		$categories = ProductCategory::getAllCategories();
		if($categories){
			// Product fields
			$fields->addFieldsToTab('Root.Main', array(
				TextField::create('SKU', 'SKU Code'),
				PriceField::create('Price', 'Standard Price'),
				PriceField::create('SpecialPrice', 'Special Price')->setRightTitle('If price set to 0.00 the product will not be on special')
			), 'Content');

			// Replace URL Segment field
			/*
			$urlsegment = SiteTreeURLSegmentField::create("URLSegment", 'URLSegment');
			$baseLink = Controller::join_links(Director::absoluteURL(), 'product/');
			$url = (strlen($baseLink) > 36) ? "..." . substr($baseLink, -32) : $baseLink;
			$urlsegment->setURLPrefix($url);
			$fields->replaceField('URLSegment', $urlsegment);
			*/

			// Categories Fields
			arsort($categories);
			$fields->addFieldsToTab(
				'Root.Main',
				array(
					DropDownField::create('MainCategoryID', 'Select main category', $categories)->setEmptyString('Select a category'),
					ListboxField::create('ProductCategories', 'Categories')->setMultiple(true)->setSource($categories)->setAttribute('data-placeholder', 'Add categories')
				),
				'Content'
			);

			// Short Description
			$fields->addFieldToTab('Root.Main', TextareaField::create('ShortDescription', 'Short Description'), 'Content');

			// Product images
			$prodimgconf = GridFieldConfig_RelationEditor::create(10)->addComponent(new GridFieldSortableRows('SortOrder'));
			$fields->addFieldToTab('Root.Images', new GridField('Images', 'Images', $this->Images(), $prodimgconf));

			//Product variations
			$attributes = $shopConfig->Attributes();//$this->Attributes();
			if ($attributes && $attributes->exists()) {
				$variationFieldList = array();
				foreach ($attributes as $attribute) {
					$variationFieldList['AttributeValue_'.$attribute->ID] = $attribute->Title;
				}
				$variationFieldList = array_merge($variationFieldList, singleton('Variation')->summaryFields());

				$config = GridFieldConfig_HasManyRelationEditor::create();
				$dataColumns = $config->getComponentByType('GridFieldDataColumns');
				$dataColumns->setDisplayFields($variationFieldList);

				if($this->OnSpecial() && $shopConfig->config()->HideVariationsOnSpecial){
					$listField = new LiteralField('','<h3>Variations can only be added when a product is not on special.</h3>');
				} else {
					$listField = new GridField(
						'Variations',
						'Variations',
						$this->Variations(),
						$config
					);
				}
				$fields->addFieldToTab('Root.Variations', $listField);
			}

			// Stock level
			if($shopConfig->StockCheck){
				if(!$this->Variations()){
					$fields->addFieldToTab('Root.Main', TextField::create('Stock', 'Stock level'), 'MainCategoryID');
				}
			}

		} else {
			$fields->addFieldToTab('Root.Main', new LiteralField('CategoryWarning',
				'<p class="message warning">Please create a category before creating products.</p>'
			), 'Title');
		}

		//Ability to edit fields added to CMS here
		$this->extend('updateProductCMSFields', $fields);

		if ($warning = ShopConfig::base_currency_warning()) {
			$fields->addFieldToTab('Root.Main', new LiteralField('BaseCurrencyWarning',
				'<p class="message warning">'.$warning.'</p>'
			), 'Title');
		}

		return $fields;
	}

	public function getCMSValidator() {
		return new RequiredFields(array(
			'Title',
			'URLSegment',
			'MenuTitle',
			'MainCategoryID',
			'Price'
		));
	}

	/**
	 * Get the URL for this Product, products that are not part of the SiteTree are
	 * displayed by the {@link Product_Controller}.
	 *
	 * @see SiteTree::Link()
	 * @see Product_Controller::show()
	 * @return String
	 */
	public function Link($action = null) {
		if ($this->ParentID > -1) {
			return parent::Link($action);
		}

		if(Controller::curr() == 'ProductCategory_Controller'){ // Return the current category
			$category = Controller::curr();
			return Controller::join_links($category->Link() . 'product/', $this->URLSegment);//$this->ID);

		} else if($this->MainCategory()){ // Return the main category
			return Controller::join_links($this->MainCategory()->Link() . 'product/', $this->URLSegment);//$this->ID);

		} else { // Return base url
			return Controller::join_links(Director::baseURL() . 'product/', $this->RelativeLink($action));
		}
	}

	/**
	 * A product is required to be added to a cart with a variation if it has attributes.
	 * A product with attributes needs to have some enabled {@link Variation}s
	 *
	 * @return Boolean
	 */
	public function requiresVariation() {
		if($this->ShowVariations()){
			$variations = $this->Variations();
			return $variations && $variations->exists();
		} else {
			return false;
		}
	}

	/**
	 * Get options for an Attribute of this Product.
	 *
	 * @param Int $attributeID
	 * @return ArrayList
	 */
	public function getOptionsForAttribute($attributeID) {
		$options = new ArrayList();
		$variations = $this->Variations();

		if($variations && $variations->exists()) foreach ($variations as $variation){
			if($variation->isEnabled()){
				$option = $variation->getOptionForAttribute($attributeID);
				if($option){
					$options->push($option);
				}
			}
		}
		$options = $options->sort('SortOrder');
		return $options;
	}

	/**
	 * Validate the Product before it is saved in {@link ShopAdmin}.
	 *
	 * @see DataObject::validate()
	 * @return ValidationResult
	 */
	public function validate(){
		$result = new ValidationResult();

		//If this is being published, check that enabled variations exist if they are required
		$request = Controller::curr()->getRequest();
		$publishing = ($request && $request->getVar('action_publish')) ? true : false;

		if($publishing && $this->requiresVariation()){
			$variations = $this->Variations();

			if(!in_array('Enabled', $variations->map('ID', 'Status')->toArray())){
				$result->error(
					'Cannot publish product when no variations are enabled. Please enable some product variations and try again.',
					'VariationsDisabledError'
				);
			}
		}
		return $result;
	}

	// Check stock level
	// TODO Improve for live cart checking
	public function CheckStock($varID = null){
		$shopConfig = ShopConfig::current_shop_config();
		if($shopConfig->StockCheck){
			if($varID){
				$variation = Variation::get()->filter(array('ID' => $varID, 'ProductID' => $this->ID))->first();
				return $variation->Stock > 0 ? true : false;
			} else {
				if(!$this->Variations()){
					return $this->Stock > 0 ? true : false;
				}
			}
			return false;
		}
		return true;
	}
}
