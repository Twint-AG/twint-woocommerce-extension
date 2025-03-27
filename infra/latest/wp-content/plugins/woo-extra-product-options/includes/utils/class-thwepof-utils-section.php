<?php
/**
 * The custom sections specific functionality for the plugin.
 *
 * @author    ThemeHigh
 * @category  Admin
 */

if(!defined('WPINC')){	die; }

if(!class_exists('THWEPOF_Utils_Section')):

class THWEPOF_Utils_Section {
	static $SECTION_PROPS = array(
		'name' 	   => array('name'=>'name', 'value'=>''),		
		'position' => array('name'=>'position', 'value'=>''),
		'order'    => array('name'=>'order', 'value'=>''),
		'cssclass' => array('name'=>'cssclass', 'value'=>array(), 'value_type'=>'array'),
		
		'title_cell_with' => array('name'=>'title_cell_with', 'value'=>''),
		'field_cell_with' => array('name'=>'field_cell_with', 'value'=>''),
		
		'show_title'     => array('name'=>'show_title', 'value'=>1, 'value_type'=>'boolean'),
		
		'title' 	     => array('name'=>'title', 'value'=>''),
		'title_type'     => array('name'=>'title_type', 'value'=>''),
		'title_color'    => array('name'=>'title_color', 'value'=>''),
		'title_class'    => array('name'=>'title_class', 'value'=>array(), 'value_type'=>'array'),
	);
	
	public static function is_valid_section($section){
		if(isset($section) && $section instanceof THWEPOF_Section && !empty($section->name)){
			return true;
		} 
		return false;
	}
	
	/*public static function is_enabled($section){
		if($section->get_property('enabled')){
			return true;
		}
		return false;
	}*/
	
	public static function has_fields($section){
		if($section->get_property('fields')){
			return true;
		}
		return false;
	}
	
	public static function is_show_section($section, $product, $categories, $tags=false){
		$show = true;
		$conditional_rules = $section->get_property('conditional_rules');
		if(!empty($conditional_rules)){
			foreach($conditional_rules as $conditional_rule){				
				if(!$conditional_rule->is_satisfied($product, $categories, $tags)){
					$show = false;
				}
			}
		}
		$show = apply_filters('thwepof_show_section', $show, $section->name);
		return $show;
	}
	
	public static function prepare_default_section(){
		$section = new THWEPOF_Section();
		$section->set_property('id', 'default');
		$section->set_property('name', 'default');
		$section->set_property('title', 'Default');
		$section->set_property('show_title', 0);
		$section->set_property('position', 'woo_before_add_to_cart_button');
		
		return $section;
	}
	
	public static function prepare_section_from_posted_data($posted, $form = 'new'){
		$name     = isset($posted['i_name']) ? wc_clean(wp_unslash($posted['i_name'])) : '';
		$position = isset($posted['i_position']) ? wc_clean(wp_unslash($posted['i_position'])) : '';
		$title    = isset($posted['i_title']) ? wc_clean(wp_unslash($posted['i_title'])) : '';

		if(!$name || !$title || !$position){
			return false;
		}
		
		if($form === 'edit'){
			$section = THWEPOF_Utils::get_section_admin($name);
		}else{
			$name = strtolower($name);
			$name = is_numeric($name) ? "s_".$name : $name;
				
			$section = new THWEPOF_Section();
			$section->set_property('id', $name);
		}
		
		foreach( self::$SECTION_PROPS as $pname => $property ){
			$iname  = 'i_'.$pname;

			if($pname === 'title'){
				$pvalue = isset($posted[$iname]) ? wp_unslash(wp_filter_post_kses($posted[$iname])) : $property['value'];
			}else{
				$pvalue = isset($posted[$iname]) ? wc_clean(wp_unslash($posted[$iname])) : $property['value'];
			}
			
			if($pname === 'show_title'){
				$pvalue = !empty($pvalue) && $pvalue === 'yes' ? 1 : 0;
			}
			
			$section->set_property($pname, $pvalue);
		}
				
		$section->set_property('rules_action', isset($posted['i_rules_action']) ? trim(stripslashes($posted['i_rules_action'])) : '');
		$section->set_property('conditional_rules_json', isset($posted['i_rules']) ? trim(stripslashes($posted['i_rules'])) : '');
		$section->set_property('conditional_rules', THWEPOF_Utils::prepare_conditional_rules($posted));
		
		//WPML Support
		//self::add_wpml_support($section);
		return $section;
	}
	
	public static function get_property_set($section){
		if(self::is_valid_section($section)){
			$props_set = array();
			
			foreach(self::$SECTION_PROPS as $pname => $props){
				$pvalue = $section->get_property($props['name']);
				
				if(isset($props['value_type']) && $props['value_type'] === 'array' && !empty($pvalue)){
					$pvalue = is_array($pvalue) ? $pvalue : explode(',', $pvalue);
				}
				
				if(isset($props['value_type']) && $props['value_type'] != 'boolean'){
					$pvalue = empty($pvalue) ? $props['value'] : $pvalue;
				}
				
				$props_set[$pname] = $pvalue;
			}
			
			$props_set['rules_action'] = $section->get_property('rules_action');
			return $props_set;
		}else{
			return false;
		}
	}
	
	public static function get_property_json($section){
		$props_json = '';
		$props_set = self::get_property_set($section);
		
		if($props_set){
			$props_json = json_encode($props_set);
		}
		return $props_json;
	}
	
	public static function add_field($section, $field){
		if(self::is_valid_section($section) && THWEPOF_Utils_Field::is_valid_field($field)){
			$size = sizeof($section->fields);
			$field->set_property('order', $size);
			$section->fields[$field->get_property('name')] = $field;
			return $section;
		}else{
			throw new Exception('Invalid Section or Field Object.');
		}
	}
	
	public static function update_field($section, $field){
		if(self::is_valid_section($section) && THWEPOF_Utils_Field::is_valid_field($field)){
			$name = $field->get_property('name');
			$name_old = $field->get_property('name_old');
			$field_set = $section->fields;
			
			if(!empty($name) && is_array($field_set) && isset($field_set[$name_old])){
				$o_field = $field_set[$name_old];				
				//$index = array_search($name_old, array_keys($field_set));
				$field->set_property('order', $o_field->get_property('order'));
				$field_set[$name] = $field;
				
				if($name != $name_old){
					unset($field_set[$name_old]);
				}
				$field_set = self::sort_field_set($field_set);
				$section->set_property('fields', $field_set);
			}
			return $section;
		}else{
			throw new Exception('Invalid Section or Field Object.');
		}
	}
	
	public static function get_fields($section){
		return (is_array($section->fields) && !empty($section->fields)) ? $section->fields : array();
	}
	
	public static function prepare_section_and_fields($section, $product_id, $categories, $tags=false){
		if(self::is_valid_section($section) && self::is_show_section($section, $product_id, $categories, $tags)){					
			$fields = self::get_fields($section);
			if($fields && is_array($fields)){
				foreach($fields as $field_name => $field){
					if(THWEPOF_Utils_Field::is_enabled($field) && THWEPOF_Utils_Field::is_show_field($field, $product_id, $categories, $tags)){
						$fields[$field_name] = $field;
					}else{
						unset($fields[$field_name]);
					}
				}
				if(!empty($fields)){
					$section->set_property('fields', $fields);
					return $section;
				}
			}
		}
		return false;
	}

	public static function get_product_section_fields($section, $product_id, $categories, $tags=false, $names_only = true){
		$prod_fields = array();
		if(self::is_valid_section($section) && self::is_show_section($section, $product_id, $categories, $tags)){					
			$fields = self::get_fields($section);
			if($fields && is_array($fields)){
				foreach($fields as $field_name => $field){
					if(THWEPOF_Utils_Field::is_enabled($field) && THWEPOF_Utils_Field::is_show_field($field, $product_id, $categories, $tags)){
						if($names_only){
							$prod_fields[] = $field_name;
						}else{
							$prod_fields[$field_name] = $field;
						}
					}
				}
			}
		}
		return $prod_fields;
	}
	
	public static function get_product_fields($product, $names_only = true){
		$product_id = THWEPOF_Utils::get_product_id($product);
		$categories = THWEPOF_Utils::get_product_categories($product_id);
		$tags       = THWEPOF_Utils::get_product_tags($product_id);
		$sections   = THWEPOF_Utils::get_sections();
		
		$prod_fields = array();
		
		if($sections && is_array($sections) && !empty($sections)){
			foreach($sections as $section_name => $section){
				$fields = self::get_product_section_fields($section, $product_id, $categories, $tags, $names_only);
				if($fields){
					$prod_fields = array_merge($prod_fields, $fields);
				}
			}
		}
		return $prod_fields;
	}

	public static function has_extra_options($product){
		$options_extra = self::get_product_fields($product);
		return empty($options_extra) ? false : true;		
	}
	
	public static function clear_fields($section){
		if(self::is_valid_section($section)){
			$section->fields = array();
		}
		return $section;
	}
	
	public static function sort_fields($section){
		uasort($section->fields, array(__CLASS__, 'sort_by_order'));
		return $section;
	}
	
	public static function sort_field_set($field_set){
		uasort($field_set, array(__CLASS__, 'sort_by_order'));
		return $field_set;
	}
	
	public static function sort_by_order($a, $b){
	    if($a->get_property('order') == $b->get_property('order')){
	        return 0;
	    }
	    return ($a->get_property('order') < $b->get_property('order')) ? -1 : 1;
	}
	
	/*public static function add_wpml_support($section){
		THWEPO_i18n::wpml_register_string('Section Title - '.$section->name, $section->title );
		THWEPO_i18n::wpml_register_string('Section Subtitle - '.$section->name, $section->subtitle );
	}*/
	
	/***********************************************
	 *********** DISPLAY SECTIONS - START **********
	 ***********************************************/
	public static function prepare_section_html($section, $product){
		$product_id = THWEPOF_Utils::get_product_id($product);
		$categories = THWEPOF_Utils::get_product_categories($product_id);
		$tags       = THWEPOF_Utils::get_product_tags($product_id);

		$section_html = '';
		if(self::is_valid_section($section) && self::is_show_section($section, $product_id, $categories, $tags)){
			$field_html = '';
			$field_html_hidden = '';

			$fields = self::get_fields($section);
			if(is_array($fields)){
				foreach($fields as $field){
					if(THWEPOF_Utils_Field::is_enabled($field) && THWEPOF_Utils_Field::is_show_field($field, $product_id, $categories, $tags)){
						if($field->get_property('type') === 'hidden'){
							$field_html_hidden .= THWEPOF_Utils_Field::prepare_field_html($field, $section);
						}else{
							$field_html .= THWEPOF_Utils_Field::prepare_field_html($field, $section);
						}
					}
				}
			}
			
			if(!empty($field_html)){
				$product_type = THWEPOF_Utils::get_product_type($product);

				$cssclass  = THWEPOF_Utils::convert_cssclass_string($section->get_property('cssclass'));
				$cssclass .= $product_type ? ' thwepo_'.$product_type : '';
				
				$section_html .= '<table class="thwepo-extra-options '. esc_attr($cssclass) .'" cellspacing="0"><tbody>';
				$section_html .= $section->get_property('show_title') ? self::prepare_title_html($section) : '';
				$section_html .= $field_html;
				$section_html .= '</tbody></table>';
			}
			
			if(!empty($field_html_hidden)){
				$section_html .= $field_html_hidden;
			}
		}
		return $section_html;
	}
	
	public static function prepare_title_html($section){
		$title_html = '';
		if($section->get_property('title')){
			$title_class = THWEPOF_Utils::convert_cssclass_string($section->get_property('title_class'));
			$title_type  = $section->get_property('title_type') ? $section->get_property('title_type') : 'label';
			$title_style = $section->get_property('title_color') ? 'style="color:'. esc_attr($section->get_property('title_color')) .';"' : '';
			
			$title_html .= '<'.$title_type.' class="'. esc_attr($title_class) .'" '.$title_style.'>';
			$title_html .= wp_kses_post($section->get_property('title'));
			$title_html .= '</'.$title_type.'>';
		}
				
		if(!empty($title_html)){
			$title_html = apply_filters('thwepof_section_title', $title_html, $section->get_property('name'));
			$title_html = '<tr><td colspan="2" class="section-title">'.$title_html.'</td></tr>';
		}		
		return $title_html;
	}
}

endif;