<?php
/**
 * Product Field - Paragraph
 *
 * @author    Themehigh
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Product_Field_Paragraph')):
class WEPOF_Product_Field_Paragraph extends WEPOF_Product_Field{
	public function __construct() {
		$this->type = 'paragraph';
	}
}
endif;