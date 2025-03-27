<?php
/**
 * Custom product field data object.
 *
 * @author    ThemeHiGH
 * @category  Admin
 */

if(!defined('WPINC')){	die; }

if(!class_exists('WEPOF_Product_Field')):
class WEPOF_Product_Field{
	public $order = '';
	public $type = '';
	public $id   = '';
	public $name = '';	

	public $value = '';
	public $placeholder = '';
	public $options = array();
	public $validator = '';
	public $cssclass = '';
	public $cssclass_str = '';

	public $title = '';
	public $title_class = '';
	public $title_class_str = '';
	public $title_position = 'left';
	
	public $required = false;
	public $enabled  = true;
	public $readonly = false;

	public $position = 'woo_before_add_to_cart_button';
	
	public $conditional_rules_json = '';
	public $conditional_rules = array();

	public $name_old = '';
	public $position_old = '';

	public $minlength = '';
	public $maxlength = '';

	public function __construct() {

	}

   /***********************************
	**** Setters & Getters - START ****
	***********************************/
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

	public function set_options_str($options_str){
		$this->options = !empty($options_str) ? array_map('wc_clean', explode('|', $options_str)) : array();
	}
		
	public function set_conditional_rules_json($conditional_rules_json){
		$conditional_rules_json = str_replace("'", '"', $conditional_rules_json);
		$this->conditional_rules_json = $conditional_rules_json;
	}
	public function set_conditional_rules($conditional_rules){
		$this->conditional_rules = $conditional_rules;
	}
				
	/*** Getters ***/	
	public function get_options_str(){
		return is_array($this->options) ? implode("|", $this->options) : '';
	}

	public function is_required(){
		return $this->required;
	}	

	public function is_enabled(){
		return $this->enabled;
	}

	public function is_readonly(){
		return $this->readonly;
	}
	
	public function get_conditional_rules_json(){
		return $this->conditional_rules_json;
	}
	public function get_conditional_rules(){
		return $this->conditional_rules;
	}
   /***********************************
	**** Setters & Getters - END ******
	***********************************/
}
endif;