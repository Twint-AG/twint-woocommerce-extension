<?php
/**
 * Product Field - Heading
 *
 * @author    Themehigh
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Product_Field_Heading')):
class WEPOF_Product_Field_Heading extends WEPOF_Product_Field{
    public $title_type  = 'h1';
    
	public function __construct() {
		$this->type = 'heading';
	}
}
endif;