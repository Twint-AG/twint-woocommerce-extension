<?php
/**
 * Product Field - Separator
 *
 * @author    Themehigh
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Product_Field_Separator')):
class WEPOF_Product_Field_Separator extends WEPOF_Product_Field{
	public function __construct() {
		$this->type = 'separator';
	}
}
endif;