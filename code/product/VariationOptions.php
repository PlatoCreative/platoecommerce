<?php
class Variation_Options extends DataObject {

	private static $has_one = array(
		'Variation' => 'Variation',
		'Option' => 'Option'
	);
}
