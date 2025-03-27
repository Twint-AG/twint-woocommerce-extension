<?php
/**
 * Product Field - Select
 *
 * @author    ThemeHiGH
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Product_Field_Select')):
class WEPOF_Product_Field_Select extends WEPOF_Product_Field{
	public function __construct() {
		$this->type = 'select';
	}	
}
endif;