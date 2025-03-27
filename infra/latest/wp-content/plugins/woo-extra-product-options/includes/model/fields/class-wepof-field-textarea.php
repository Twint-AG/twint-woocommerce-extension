<?php
/**
 * Product Field - Textarea
 *
 * @author    ThemeHiGH
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Product_Field_Textarea')):
class WEPOF_Product_Field_Textarea extends WEPOF_Product_Field{
	public $cols = '';
	public $rows = '';

	public function __construct() {
		$this->type = 'textarea';
	}
}
endif;