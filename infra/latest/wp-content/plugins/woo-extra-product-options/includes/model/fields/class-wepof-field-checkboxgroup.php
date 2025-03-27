<?php
/**
 * Product Field - Checkbox Group
 *
 * @author    Themehigh
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Product_Field_CheckboxGroup')):
class WEPOF_Product_Field_CheckboxGroup extends WEPOF_Product_Field{
	public function __construct() {
		$this->type = 'checkboxgroup';
	}
}
endif;