<?php
/**
 * Product Field - Input Text
 *
 * @author    ThemeHiGH
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Product_Field_Url')):
class WEPOF_Product_Field_Url extends WEPOF_Product_Field{

	public function __construct() {
		$this->type = 'url';
	}
}
endif;