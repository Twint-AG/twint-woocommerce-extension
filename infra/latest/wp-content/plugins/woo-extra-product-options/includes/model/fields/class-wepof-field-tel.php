<?php
/**
 * Product Field - Tel
 *
 * @author    ThemeHiGH
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Product_Field_Tel')):
class WEPOF_Product_Field_Tel extends WEPOF_Product_Field{
	public function __construct() {
		$this->type = 'tel';
	}
}
endif;