<?php
/**
 * Product Field - Input Text
 *
 * @author    ThemeHiGH
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Product_Field_InputText')):
class WEPOF_Product_Field_InputText extends WEPOF_Product_Field{
	public $input_mask = '';

	public function __construct() {
		$this->type = 'inputtext';
	}
}
endif;