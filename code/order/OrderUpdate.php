<?php
class Order_Update extends DataObject {

	private static $singular_name = 'Update';
	private static $plural_name = 'Updates';

	private static $db = array(
		'Status' => "Enum('Pending,Processing,Dispatched,Cancelled')",
		'Note' => 'Text',
		'Visible' => 'Boolean'
	);

	/**
	 * Relations for this class
	 *
	 * @var Array
	 */
	private static $has_one = array(
		'Order' => 'Order',
		'Member' => 'Member'
	);

	private static $summary_fields = array(
		'Created.Nice' => 'Created',
		'Status' => 'Order Status',
		'Note' => 'Note',
		'Member.Name' => 'Owner',
		'VisibleSummary' => 'Visible'
	);

	public function canDelete($member = null) {
		return false;
	}

	public function delete() {
		if ($this->canDelete(Member::currentUser())) {
			parent::delete();
		}
	}

	/**
	 * Update stock levels for {@link Item}.
	 *
	 * @see DataObject::onAfterWrite()
	 */
	public function onAfterWrite() {
		parent::onAfterWrite();

		$siteconfig = SiteConfig::current_site_config();
		$shopConfig = ShopConfig::current_shop_config();

		// Update the Order, setting the same status
		if($this->Status){
			$order = $this->Order();
			if($order->exists()){
				if($order->Status != $this->Status){
					// Email the customer about update
					if($this->Visible){
						$to = $order->Member()->Email;
						$from = $shopConfig->NotificationTo;
						$subject = $siteconfig->Title . ' - Order #' . $order->ID . ' Update';

						$body = "<p>Hi " . $order->Member()->FirstName . ",</p>";
						$body .= "<p>There has been the following update to your order #" . $order->ID . ".</p>";
						$body .= "<p><strong>Order Status:</strong> " . $this->Status . "</p>";
						$body .= $this->Note ? "<p><strong>Note:</strong> " . $this->Note . "</p>" : "";
						$body .= "<p>You can view this order by visiting the following URL<br />";
						$body .= "<a href='" . Director::absoluteBaseURL() . "account/order/" . $order->ID . "' target='_blank'>" . Director::absoluteBaseURL() . "account/order/" . $order->ID . "</a></p>";

						$email = new Email($from, $to, $subject, $body);
						$email->send();
					}

					// Update the order status
					$order->Status = $this->Status;
					$order->write();
				}
			}
		}
	}

	public function getCMSFields() {

		$fields = parent::getCMSFields();

		$visibleField = DropdownField::create('Visible', 'Visible', array(
			1 => 'Yes',
			0 => 'No'
		))->setRightTitle('Should this update be visible to the customer?');
		$fields->replaceField('Visible', $visibleField);

		$memberField = HiddenField::create('MemberID', 'Member', Member::currentUserID());
		$fields->replaceField('MemberID', $memberField);
		$fields->removeByName('OrderID');

		return $fields;
	}

	public function Created() {
		return $this->dbObject('Created');
	}

	public function VisibleSummary() {
		return ($this->Visible) ? 'True' : '';
	}
}
