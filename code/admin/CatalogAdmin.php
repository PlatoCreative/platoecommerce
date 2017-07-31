<?php
/**
 * Catalog admin area for managing products, catgeories and attributes / options.
 *
 * @author Plato Creative
 * @package PlatoEcommerce
 * @subpackage admin
 */
class CatalogAdmin extends ModelAdmin {

	private static $url_segment = 'catalog';
	private static $url_priority = 50;
	private static $menu_title = 'Product Catalog';

	public $showImportForm = false;

	private static $managed_models = array(
		'ProductCategory',
		'Product',
		'Attribute'
	);

	private static $url_handlers = array(
	);

	public static $hidden_sections = array();

	/**
	 * @return ArrayList
	 */
	public function Breadcrumbs($unlinked = false) {
		$request = $this->getRequest();
		$items = parent::Breadcrumbs($unlinked);
		return $items;
	}

	public function init(){
		parent::init();
	}

	public function getManagedModels() {
		$models = $this->stat('managed_models');
		if(is_string($models)) {
			$models = array($models);
		}

		if(!count($models)) {
			user_error(
				'ModelAdmin::getManagedModels():
				You need to specify at least one DataObject subclass in private static $managed_models.
				Make sure that this property is defined, and that its visibility is set to "public"',
				E_USER_ERROR
			);
		}

		// Normalize models to have their model class in array key
		foreach($models as $k => $v) {
			if(is_numeric($k)) {
				$models[$v] = array('title' => singleton($v)->i18n_plural_name());
				unset($models[$k]);
			}
		}
		return $models;
	}

	/**
	 * Returns managed models' create, search, and import forms
	 * @uses SearchContext
	 * @uses SearchFilter
	 * @return SS_List of forms
	 */
	protected function getManagedModelTabs() {

		$forms  = new ArrayList();

		$models = $this->getManagedModels();
		foreach($models as $class => $options) {
			$forms->push(new ArrayData(array (
				'Title'     => $options['title'],
				'ClassName' => $class,
				'Link' => $this->Link($this->sanitiseClassName($class)),
				'LinkOrCurrent' => ($class == $this->modelClass) ? 'current' : 'link'
			)));
		}

		return $forms;
	}

	public function getList() {
        $list = parent::getList();

        // Always limit by model class, in case you're managing multiple
        if($this->modelClass == 'Product') {
			if(class_exists('Subsite')){
				$siteConfig = SiteConfig::current_site_config();
        		$list = $list->filter(array('SubsiteID' => $siteConfig->SubsiteID));
			} else {
				$list = Product::get();
			}
        }

        return $list;
    }

	public function getEditForm($id = null, $fields = null) {
		$list = $this->getList();

		$buttonAfter = new GridFieldButtonRow('after');
		$exportButton = new GridFieldExportButton('buttons-after-left');
		$exportButton->setExportColumns($this->getExportFields());

		$fieldConfig = GridFieldConfig_RecordEditor::create($this->stat('page_length'))->addComponent($buttonAfter)->addComponent($exportButton);
		$fieldConfig->removeComponentsByType('GridFieldExportButton');

		// Product category display settings
		if ($this->modelClass == 'ProductCategory') {
			$fieldConfig->removeComponentsByType('GridFieldAddNewButton');
			$fieldConfig->removeComponentsByType('GridFieldDeleteAction');
			$fieldConfig->removeComponentsByType('GridFieldAddExistingAutocompleter');
			//$fieldConfig->removeComponentsByType('GridFieldEditButton');
			$list = ProductCategory::get()->filter(array('ParentID' => 0));
		}

		$listField = new GridField(
			$this->sanitiseClassName($this->modelClass),
			false,
			$list,
			$fieldConfig
		);

		$categories = ProductCategory::getAllCategories();
		if($this->modelClass == 'Product' && !$categories){
			$listField = LiteralField::create('CategoryWarning', '<p class="message warning">Please create a category in the site tree before creating products.</p>');
		}

		// Validation
		if(singleton($this->modelClass)->hasMethod('getCMSValidator')) {
			if($listField->Type() != 'readonly'){
				$detailValidator = singleton($this->modelClass)->getCMSValidator();
				$listField->getConfig()->getComponentByType('GridFieldDetailForm')->setValidator($detailValidator);
			}
		}

		$form = new Form(
			$this,
			'EditForm',
			new FieldList($listField),
			new FieldList()
		);

		$form->addExtraClass('cms-edit-form cms-panel-padded center');
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
		$form->setFormAction(Controller::join_links($this->Link($this->sanitiseClassName($this->modelClass)), 'EditForm'));
		$form->setAttribute('data-pjax-fragment', 'CurrentForm');

		$this->extend('updateEditForm', $form);

		return $form;
	}
}
