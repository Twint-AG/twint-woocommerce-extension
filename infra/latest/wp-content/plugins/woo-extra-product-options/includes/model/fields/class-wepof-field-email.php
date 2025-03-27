<?php
/**
 * Product Field - Input Text
 *
 * @author    ThemeHiGH
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Product_Field_Email')):
class WEPOF_Product_Field_Email extends WEPOF_Product_Field{

	public function __construct() {
		$this->type = 'email';
	}
}
endif;