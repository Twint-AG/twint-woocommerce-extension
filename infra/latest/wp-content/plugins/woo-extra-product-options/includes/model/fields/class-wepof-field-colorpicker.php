<?php
/**
 * Product Field - Color Picker
 *
 * @author    Themehigh
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Product_Field_ColorPicker')):
class WEPOF_Product_Field_ColorPicker extends WEPOF_Product_Field{
	public function __construct() {
		$this->type = 'colorpicker';
	}
}
endif;