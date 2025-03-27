<?php
/**
 * Product Field - Radio
 *
 * @author    ThemeHiGH
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Product_Field_Radio')):
class WEPOF_Product_Field_Radio extends WEPOF_Product_Field{
	public function __construct() {
		$this->type = 'radio';
	}
}
endif;