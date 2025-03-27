<?php
/**
 * Product Field - Number
 *
 * @author    ThemeHiGH
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Product_Field_Number')):
class WEPOF_Product_Field_Number extends WEPOF_Product_Field{
	public $step = '';
	
	public function __construct() {
		$this->type = 'number';
	}
}
endif;