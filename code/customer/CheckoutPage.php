<?php
/**
 * A checkout page for displaying the checkout form to a visitor.
 * Automatically created on install of the shop module, cannot be deleted by admin user
 * in the CMS. A required page for the shop module.
 *
 * @author Plato Creative
 * @copyright Copyright (c) 2017, Plato Creative
 * @package PlatoEcommerce
 * @subpackage customer
 */
class CheckoutPage extends Page {

	/**
	 * Automatically create a CheckoutPage if one is not found
	 * on the site at the time the database is built (dev/build).
	 */
	function requireDefaultRecords() {
		parent::requireDefaultRecords();

		if (!DataObject::get_one('CheckoutPage')) {
			$page = new CheckoutPage();
			$page->Title = 'Checkout';
			$page->Content = '';
			$page->URLSegment = 'checkout';
			$page->ShowInMenus = 0;
			$page->writeToStage('Stage');
			$page->publish('Stage', 'Live');

			DB::alteration_message('Checkout page \'Checkout\' created', 'created');
		}
	}

	/**
	 * Prevent CMS users from creating another checkout page.
	 *
	 * @see SiteTree::canCreate()
	 * @return Boolean Always returns false
	 */
	function canCreate($member = null) {
		return false;
	}

	/**
	 * Prevent CMS users from deleting the checkout page.
	 *
	 * @see SiteTree::canDelete()
	 * @return Boolean Always returns false
	 */
	function canDelete($member = null) {
		return false;
	}

	public function delete() {
		if ($this->canDelete(Member::currentUser())) {
			parent::delete();
		}
	}

	/**
	 * Prevent CMS users from unpublishing the checkout page.
	 *
	 * @see SiteTree::canDeleteFromLive()
	 * @see CheckoutPage::getCMSActions()
	 * @return Boolean Always returns false
	 */
	function canDeleteFromLive($member = null) {
		return false;
	}

	/**
	 * To remove the unpublish button from the CMS, as this page must always be published
	 *
	 * @see SiteTree::getCMSActions()
	 * @see CheckoutPage::canDeleteFromLive()
	 * @return FieldList Actions fieldset with unpublish action removed
	 */
	function getCMSActions() {
		$actions = parent::getCMSActions();
		$actions->removeByName('action_unpublish');
		return $actions;
	}

	/**
	 * Remove page type dropdown to prevent users from changing page type.
	 *
	 * @see Page::getCMSFields()
	 * @return FieldList
	 */
	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->removeByName('ClassName');
		return $fields;
	}
}
