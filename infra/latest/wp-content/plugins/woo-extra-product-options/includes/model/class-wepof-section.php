<?php
/**
 * Custom section data object.
 *
 * @author    ThemeHiGH
 * @category  Admin
 */

if(!defined('WPINC')){	die; }

if(!class_exists('THWEPOF_Section')):

class THWEPOF_Section{
	public $id = '';
	public $name = '';
	public $position = '';
	public $order = '';
	public $cssclass = '';
	
	public $title_cell_with = '';
	public $field_cell_with = '';
	
	public $show_title = 1;
		
	public $title = '';
	public $title_type  = '';
	public $title_color = '';
	public $title_class = '';
	
	public $cssclass_str = '';
	public $title_class_str = '';
	
	public $rules_action = '';
	public $conditional_rules_json = '';
	public $conditional_rules = array();
	
	public $condition_sets = array();
	public $fields = array();
	
	public function __construct() {
	}
	
	public function set_property($name, $value){
		if(property_exists($this, $name)){
			$this->$name = $value;
		}
	}
	
	public function get_property($name){
		if(property_exists($this, $name)){
			return $this->$name;
		}else{
			return '';
		}
	}
}

endif;