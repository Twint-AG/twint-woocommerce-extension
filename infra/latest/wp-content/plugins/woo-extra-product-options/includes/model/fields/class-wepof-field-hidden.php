<?php
/**
 * Product Field - Hidden
 *
 * @author    ThemeHiGH
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Product_Field_Hidden')):
class WEPOF_Product_Field_Hidden extends WEPOF_Product_Field{
	public function __construct() {
		$this->type = 'hidden';
	}
}
endif;