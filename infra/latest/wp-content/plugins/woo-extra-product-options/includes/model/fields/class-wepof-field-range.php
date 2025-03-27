<?php
/**
 * Product Field - Input Text
 *
 * @author    ThemeHiGH
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Product_Field_Range')):
class WEPOF_Product_Field_Range extends WEPOF_Product_Field{
    public $step = '';
    
	public function __construct() {
		$this->type = 'range';
	}
}
endif;