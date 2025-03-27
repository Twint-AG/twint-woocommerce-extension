<?php
/**
 * Product Field - Date Picker
 *
 * @author    Themehigh
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Product_Field_DatePicker')):
class WEPOF_Product_Field_DatePicker extends WEPOF_Product_Field{
	public function __construct() {
		$this->type = 'datepicker';
	}
}
endif;