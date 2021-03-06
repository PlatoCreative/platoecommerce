<?php
/**
 * Shop configuration object for containing all the shop settings.
 *
 * @author Plato Creative
 * @copyright Copyright (c) 2017, Plato Creative
 * @package PlatoEcommerce
 * @subpackage admin
 */
class ShopConfig extends DataObject {

	private static $singular_name = 'Settings';
	private static $plural_name = 'Settings';

	private static $db = array(
		'LicenceKey' => 'Varchar',

		'BaseCurrency' => 'Varchar(3)',
		'BaseCurrencyPrecision' => 'Int',	// number of digits after the decimal place
		'BaseCurrencySymbol' => 'Varchar(10)',

		'CartTimeout' => 'Int',
		'CartTimeoutUnit' => "Enum('minute, hour, day', 'hour')",

		'StockCheck' => 'Boolean',
		'StockManagement' => "Enum('strict, relaxed', 'strict')",

		'EmailSignature' => 'HTMLText',
		'ReceiptSubject' => 'Varchar',
		'ReceiptBody' => 'HTMLText',
		'ReceiptFrom' => 'Varchar',
		'NotificationSubject' => 'Varchar',
		'NotificationBody' => 'HTMLText',
		'NotificationTo' => 'Varchar',
        'NotificationFrom' => 'Varchar'
	);

	private static $has_one = array(
		'SiteConfig' => 'SiteConfig'
	);

	private static $has_many = array(
		//'Attributes' => 'Attribute_Default'
		'Attributes' => 'Attribute',
		'Orders' => 'Order'
	);

	private static $defaults = array(
		'BaseCurrencyPrecision' => 2,
		'CartTimeout' => 1,
		'CartTimeoutUnit' => 'hour',
		'StockCheck' => false,
		'StockManagement' => 'strict'
	);

	public function onBeforeWrite(){
		parent::onBeforeWrite();
		$siteconfig = SiteConfig::current_site_config();
		$this->SiteConfigID = $siteconfig->ID;
	}

	public static function current_shop_config() {
		//$shopconfig = ShopConfig::get()->First();
		$siteconfig = SiteConfig::current_site_config();
		$shopconfig = ShopConfig::get()->where("\"SiteConfigID\" = '$siteconfig->ID'")->First();

		//$this->extend('edit_current_shop_config', $shopconfig);

		return $shopconfig;
	}

	public static function base_currency_warning() {
		$config = self::current_shop_config();
		$warning = null;

		if (!$config->BaseCurrency) {
			$warning = _t('ShopConfig.BASE_CURRENCY_WARNING','
				 Warning: Base currency is not set, please set base currency in the shop settings area before proceeding
			');
		}
		return $warning;
	}

	/**
	 * Setup a default ShopConfig record if none exists
	 */
	public function requireDefaultRecords() {
		parent::requireDefaultRecords();

		if(!self::current_shop_config()) {
			$shopConfig = new ShopConfig();
			$shopConfig->write();
			DB::alteration_message('Added default shop config', 'created');
		}
	}
}
