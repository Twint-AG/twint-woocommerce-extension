<?php
/**
 * Product Field - Checkbox
 *
 * @author    ThemeHiGH
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Product_Field_Checkbox')):
class WEPOF_Product_Field_Checkbox extends WEPOF_Product_Field{
	public $checked = false;

	public function __construct() {
		$this->type = 'checkbox';
	}
}
endif;