<?php
/**
 * Product Field - Switch
 *
 * @author    ThemeHiGH
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Product_Field_Switch')):
class WEPOF_Product_Field_Switch extends WEPOF_Product_Field{
	public $checked = false;

	public function __construct() {
		$this->type = 'switch';
	}
}
endif;