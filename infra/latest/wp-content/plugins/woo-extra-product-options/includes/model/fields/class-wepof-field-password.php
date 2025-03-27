<?php
/**
 * Product Field - Password
 *
 * @author    ThemeHiGH
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Product_Field_Password')):
class WEPOF_Product_Field_Password extends WEPOF_Product_Field{
	public $view_password = false;
	
	public function __construct() {
		$this->type = 'password';
	}
}
endif;