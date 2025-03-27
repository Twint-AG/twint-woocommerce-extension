<?php
/**
 * Woo Extra Product Options utils
 *
 * @author    ThemeHigh
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('THWEPOF_Utils')) :
class THWEPOF_Utils {
	const OPTION_KEY_CUSTOM_SECTIONS   = 'thwepof_custom_sections';
	const OPTION_KEY_SECTION_HOOK_MAP  = 'thwepof_section_hook_map';
	const OPTION_KEY_NAME_TITLE_MAP    = 'thwepof_options_name_title_map';
	const OPTION_KEY_ADVANCED_SETTINGS = 'thwepof_advanced_settings';

	public static function get_advanced_settings(){
		$settings = get_option(self::OPTION_KEY_ADVANCED_SETTINGS);
		$settings = apply_filters('thwepof_advanced_settings', $settings);
		return empty($settings) ? false : $settings;
	}
	
	public static function get_setting_value($settings, $key){
		if(is_array($settings) && isset($settings[$key])){
			return $settings[$key];
		}
		return '';
	}
	
	public static function get_settings($key){
		$settings = self::get_advanced_settings();
		if(is_array($settings) && isset($settings[$key])){
			return $settings[$key];
		}
		return '';
	}

	public static function save_sections($sections){
		if (apply_filters('thwepof_clear_cache_before_update', false) || class_exists('Nawawi\\DocketCache\\Plugin') ) {
			self::clear_cache(self::OPTION_KEY_CUSTOM_SECTIONS, 'options');
		}

		$result = update_option(self::OPTION_KEY_CUSTOM_SECTIONS, $sections, 'no');
		return $result;
	}

	public static function save_section_hook_map($section_hook_map){
		$result = update_option(self::OPTION_KEY_SECTION_HOOK_MAP, $section_hook_map, 'no');
		return $result;
	}

	public static function save_name_title_map($name_title_map){
		$result = update_option(self::OPTION_KEY_NAME_TITLE_MAP, $name_title_map, 'no');
		return $result;
	}

	public static function get_sections(){
		$sections = get_option(self::OPTION_KEY_CUSTOM_SECTIONS);
		return empty($sections) ? false : $sections;
	}

	public static function get_section($section_name){
	 	if($section_name){
			$sections = self::get_sections();
			if(is_array($sections) && isset($sections[$section_name])){
				$section = $sections[$section_name];
				if(THWEPOF_Utils_Section::is_valid_section($section)){
					return $section;
				}
			}
		}
		return false;
	}

	public static function get_section_hook_map(){
		$section_hook_map = get_option(self::OPTION_KEY_SECTION_HOOK_MAP);
		$section_hook_map = is_array($section_hook_map) ? $section_hook_map : array();
		return $section_hook_map;
	}

	public static function get_sections_by_hook($hook_name, $section_hook_map=false){
		if(!is_array($section_hook_map)){
			$section_hook_map = self::get_section_hook_map();
		}

		if(is_array($section_hook_map) && array_key_exists($hook_name, $section_hook_map)) {
			$hooked_sections = $section_hook_map[$hook_name];
			return (is_array($hooked_sections) && !empty($hooked_sections)) ? $hooked_sections : false;
		}
		return false;
	}

	public static function get_product_fields_full(){
		$fields_full = array();

		$sections = self::get_sections();
		if(is_array($sections)){
			foreach ($sections as $section) {
				$fields = THWEPOF_Utils_Section::get_fields($section);
				if($fields && is_array($fields)){
					$fields_full = array_merge($fields_full, $fields);
				}
			}
		}
		return $fields_full;
	}

	public static function get_sections_admin(){
		$sections = self::get_sections();

		if($sections && is_array($sections) && !empty($sections)){
			return $sections;
		}else{
			$section = THWEPOF_Utils_Section::prepare_default_section();

			$sections = array();
			$sections[$section->get_property('name')] = $section;
			return $sections;
		}
	}

	public static function get_section_admin($section_name){
	 	if($section_name){
			$sections = self::get_sections_admin();
			if(is_array($sections) && isset($sections[$section_name])){
				$section = $sections[$section_name];
				if(THWEPOF_Utils_Section::is_valid_section($section)){
					return $section;
				}
			}
		}
		return false;
	}

	public static function update_section($section){
	 	if(THWEPOF_Utils_Section::is_valid_section($section)){
			$sections = self::get_sections_admin();
			$sections = (isset($sections) && is_array($sections)) ? $sections : array();

			$sections[$section->name] = $section;
			self::sort_sections($sections);

			$result1 = self::save_sections($sections);
			$result2 = self::update_section_hook_map($section);

			return $result1;
		}
		return false;
	}

	private static function update_section_hook_map($section){
		$section_name  = $section->name;
		$display_order = $section->get_property('order');
		$hook_name 	   = $section->position;

	 	if($hook_name && $section_name){
			$hook_map = self::get_section_hook_map();

			//Remove from hook if already hooked
			if($hook_map && is_array($hook_map)){
				foreach($hook_map as $hname => $hsections){
					if($hsections && is_array($hsections)){
						if(($key = array_search($section_name, $hsections)) !== false) {
							unset($hsections[$key]);
							$hook_map[$hname] = $hsections;
						}
					}

					if(empty($hsections)){
						unset($hook_map[$hname]);
					}
				}
			}

			if(isset($hook_map[$hook_name])){
				$hooked_sections = $hook_map[$hook_name];
				if(!in_array($section_name, $hooked_sections)){
					$hooked_sections[] = $section_name;
					$hooked_sections = self::sort_hooked_sections($hooked_sections);

					$hook_map[$hook_name] = $hooked_sections;
					self::save_section_hook_map($hook_map);
				}
			}else{
				$hooked_sections = array();
				$hooked_sections[] = $section_name;
				$hooked_sections = self::sort_hooked_sections($hooked_sections);

				$hook_map[$hook_name] = $hooked_sections;
				self::save_section_hook_map($hook_map);
			}
		}
	}

	public static function clear_cache($key, $group){
		wp_cache_delete($key, $group);
	}

	/*public static function get_option_display_value($name, $value, $data){
		$type = false;
		$options = false;

		if(is_array($data)){
			$type =  isset($data['type']) ? $data['type'] : '';
			if(THWEPOF_Utils_Field::is_option_field($type)){
				$options = isset($data['options']) ? $data['options'] : false;
			}
		}else{
			$fields_all = self::get_custom_fields_full();
			if(is_array($fields_all) && isset($fields_all[$name])){
				$field = $fields_all[$name];
				if(THWEPOF_Utils_Field::is_valid_field($field)){
					$type = $field->get_property('type');
					if(THWEPOF_Utils_Field::is_option_field($type)){
						$options = $field->get_property('options');
					}
				}
			}
		}

		if($value && is_array($options)){
			$value_arr = array_map('trim', explode(',', $value));
			$value = '';

			foreach($value_arr as $val){
				if(isset($options[$val])){
					$option = $options[$val];
					if(is_array($option) && isset($option['text'])){
						$value .= $value ? ', ' : '';
						$value .= __($option['text'], 'woo-extra-product-options');
					}
				}
			}
		}

		return $value;
	}*/

	public static function get_product_id($product){
		$product_id = '';
		if (!is_object($product) || !($product instanceof WC_Product)) {
			return $product_id;
		}
		if(self::woo_version_check()){
			$product_id = $product->get_id();
		}else{
			$product_id = $product->id;
		}
		return $product_id;
	}

	public static function get_product_type($product){
		$product_type = '';
		if (!is_object($product) || !($product instanceof WC_Product)) {
			return $product_type;
		}
		if(self::woo_version_check()){
			$product_type = $product->get_type();
		}else{
			$product_type = $product->product_type;
		}
		return $product_type;
	}

	public static function get_original_product_id($product_id){
		$is_wpml_active = self::is_wpml_active();

		if($is_wpml_active){
			global $sitepress;
			global $icl_adjust_id_url_filter_off;

			$orig_flag_value = $icl_adjust_id_url_filter_off;
			$icl_adjust_id_url_filter_off = true;
			$default_lang = $sitepress->get_default_language();

			$product_id = icl_object_id($product_id, 'product', true, $default_lang);

			$icl_adjust_id_url_filter_off = $orig_flag_value;
		}
		return $product_id;
	}

	/*public static function get_product_categories($product){
		$ignore_translation = apply_filters('thwepof_ignore_wpml_translation_for_product_category', true);
		$is_wpml_active = function_exists('icl_object_id');
		$product_id = self::get_product_id($product);

		$categories = array();
		if($product_id){
			$product_cat = wp_get_post_terms($product_id, 'product_cat');
			if(is_array($product_cat)){
				foreach($product_cat as $category){
					$parent_cat = get_ancestors( $category->term_id, 'product_cat' );
					if(is_array($parent_cat)){
						foreach($parent_cat as $pcat_id){
							$pcat = get_term( $pcat_id, 'product_cat' );
							$pcat_slug = $pcat->slug;
							$pcat_slug = self::check_for_wpml_traslation($pcat_slug, $pcat, $is_wpml_active, $ignore_translation);
							$categories[] = $pcat_slug;
						}
					}
					$cat_slug = $category->slug;
					$cat_slug = self::check_for_wpml_traslation($cat_slug, $category, $is_wpml_active, $ignore_translation);
					$categories[] = $cat_slug;
				}
			}
		}
		return $categories;
	}*/

	public static function get_product_categories($product_id){
		//$product_id = self::get_product_id($product);
		$ignore_translation = apply_filters('thwepof_ignore_wpml_translation_for_product_category', true);
		$categories = self::get_product_terms($product_id, 'category', 'product_cat', $ignore_translation);
		return $categories;
	}

	public static function get_product_tags($product_id){
		//$product_id = self::get_product_id($product);
		$ignore_translation = apply_filters('thwepof_ignore_wpml_translation_for_product_tag', true);
		$tags = self::get_product_terms($product_id, 'tag', 'product_tag', $ignore_translation);
		return $tags;
	}

	public static function get_product_terms($product_id, $type, $taxonomy, $ignore_translation=false){
		$terms = array();
		$assigned_terms = wp_get_post_terms($product_id, $taxonomy);

		$is_wpml_active = self::is_wpml_active();
		if($is_wpml_active && $ignore_translation){
			/*global $sitepress;
			global $icl_adjust_id_url_filter_off;
			$orig_flag_value = $icl_adjust_id_url_filter_off;
			$icl_adjust_id_url_filter_off = true;
			$default_lang = $sitepress->get_default_language();*/
			$default_lang = self::off_wpml_translation();
		}

		if(is_array($assigned_terms)){
			foreach($assigned_terms as $term){
				$parent_terms = get_ancestors($term->term_id, $taxonomy);
				if(is_array($parent_terms)){
					foreach($parent_terms as $pterm_id){
						$pterm = get_term($pterm_id, $taxonomy);
						$terms[] = $pterm->slug;
					}
				}

				$term_slug = $term->slug;
				if($is_wpml_active && $ignore_translation){
					//$default_term_id = icl_object_id($term->term_id, $taxonomy, true, $default_lang);
					//$default_term = get_term($default_term_id);
					$default_term = self::get_default_lang_term($term, $taxonomy, $default_lang);
					$term_slug = $default_term->slug;
				}
				$terms[] = $term_slug;
			}
		}

		if($is_wpml_active && $ignore_translation){
			//$icl_adjust_id_url_filter_off = $orig_flag_value;
			self::may_on_wpml_translation($default_lang);
		}

		return $terms;
	}

	public static function get_default_lang_term($term, $taxonomy, $default_lang){
		$dterm_id = icl_object_id($term->term_id, $taxonomy, true, $default_lang);
		$dterm = get_term($dterm_id);
		return $dterm;
	}

	public static function sort_sections(&$sections){
		if(is_array($sections) && !empty($sections)){
			self::stable_uasort($sections, array(__CLASS__, 'sort_sections_by_order'));
		}
	}

	public static function sort_hooked_sections(&$sections){
		if(is_array($sections) && !empty($sections)){
			self::stable_uasort($sections, array(__CLASS__, 'sort_sections_by_order'));
		}
	}

	public static function sort_sections_by_order($a, $b){
		if(is_array($a) && is_array($b)){
			$order_a = isset($a['order']) && is_numeric($a['order']) ? $a['order'] : 0;
			$order_b = isset($b['order']) && is_numeric($b['order']) ? $b['order'] : 0;

			if($order_a == $order_b){
				return 0;
			}
			return ($order_a < $order_b) ? -1 : 1;
		}else if(THWEPOF_Utils_Section::is_valid_section($a) && THWEPOF_Utils_Section::is_valid_section($b)){
			$order_a = is_numeric($a->get_property('order')) ? $a->get_property('order') : 0;
			$order_b = is_numeric($b->get_property('order')) ? $b->get_property('order') : 0;

			if($order_a == $order_b){
				return 0;
			}
			return ($order_a < $order_b) ? -1 : 1;
		}else{
			return 0;
		}
	}

	public static function stable_uasort(&$array, $cmp_function) {
		if(count($array) < 2) {
			return;
		}

		$halfway = intdiv(count($array), 2);
		$array1 = array_slice($array, 0, $halfway, TRUE);
		$array2 = array_slice($array, $halfway, NULL, TRUE);

		self::stable_uasort($array1, $cmp_function);
		self::stable_uasort($array2, $cmp_function);
		if(call_user_func_array($cmp_function, array(end($array1), reset($array2))) < 1) {
			$array = $array1 + $array2;
			return;
		}

		$array = array();
		reset($array1);
		reset($array2);
		while(current($array1) && current($array2)) {
			if(call_user_func_array($cmp_function, array(current($array1), current($array2))) < 1) {
				$array[key($array1)] = current($array1);
				next($array1);
			} else {
				$array[key($array2)] = current($array2);
				next($array2);
			}
		}
		while(current($array1)) {
			$array[key($array1)] = current($array1);
			next($array1);
		}
		while(current($array2)) {
			$array[key($array2)] = current($array2);
			next($array2);
		}
		return;
	}

	public static function check_for_wpml_traslation($cat_slug, $cat, $is_wpml_active, $ignore_translation){
		if($is_wpml_active && $ignore_translation){
			global $sitepress;
			global $icl_adjust_id_url_filter_off;

			$orig_flag_value = $icl_adjust_id_url_filter_off;
			$icl_adjust_id_url_filter_off = true;
			$default_lang = $sitepress->get_default_language();

			$ocat_id = icl_object_id($cat->term_id, 'product_cat', true, $default_lang);
			$ocat = get_term($ocat_id, 'product_cat');
			$cat_slug = $ocat->slug;

			$icl_adjust_id_url_filter_off = $orig_flag_value;
		}
		return $cat_slug;
	}

	public static function is_wpml_active(){
		global $sitepress;
		return function_exists('icl_object_id') && is_object($sitepress);
		//return function_exists('icl_object_id');
	}

	public static function off_wpml_translation(){
		global $sitepress;
		global $icl_adjust_id_url_filter_off;

		$orig_flag_value = $icl_adjust_id_url_filter_off;
		$icl_adjust_id_url_filter_off = true;
		$default_lang = $sitepress->get_default_language();

		return $default_lang;
	}

	public static function may_on_wpml_translation($value){
		global $icl_adjust_id_url_filter_off;
		$icl_adjust_id_url_filter_off = $value;
	}

   /*****************************************
	**** CONDITIONAL RULES UTILS - START ****
	*****************************************/
	public static function prepare_conditional_rules($posted, $ajax=false){
		$iname = $ajax ? 'i_rules_ajax' : 'i_rules';
		$conditional_rules = isset($posted[$iname]) ? trim(stripslashes($posted[$iname])) : '';

		$condition_rule_sets = array();
		if(!empty($conditional_rules)){
			$conditional_rules = urldecode($conditional_rules);
			$rule_sets = json_decode($conditional_rules, true);

			if(is_array($rule_sets)){
				foreach($rule_sets as $rule_set){
					if(is_array($rule_set)){
						$condition_rule_set_obj = new WEPOF_Condition_Rule_Set();
						$condition_rule_set_obj->set_logic('and');

						foreach($rule_set as $condition_sets){
							if(is_array($condition_sets)){
								$condition_rule_obj = new WEPOF_Condition_Rule();
								$condition_rule_obj->set_logic('or');

								foreach($condition_sets as $condition_set){
									if(is_array($condition_set)){
										$condition_set_obj = new WEPOF_Condition_Set();
										$condition_set_obj->set_logic('and');

										foreach($condition_set as $condition){
											if(is_array($condition)){
												$condition_obj = new WEPOF_Condition();
												$condition_obj->set_subject(isset($condition['subject']) ? $condition['subject'] : '');
												$condition_obj->set_comparison(isset($condition['comparison']) ? $condition['comparison'] : '');
												$condition_obj->set_value(isset($condition['cvalue']) ? $condition['cvalue'] : '');
												/*$condition_obj->set_operand_type(isset($condition['operand_type']) ? $condition['operand_type'] : '');
												$condition_obj->set_operand(isset($condition['operand']) ? $condition['operand'] : '');
												$condition_obj->set_operator(isset($condition['operator']) ? $condition['operator'] : '');
												$condition_obj->set_value(isset($condition['value']) ? trim($condition['value']) : '');*/

												$condition_set_obj->add_condition($condition_obj);
											}
										}
										$condition_rule_obj->add_condition_set($condition_set_obj);
									}
								}
								$condition_rule_set_obj->add_condition_rule($condition_rule_obj);
							}
						}
						$condition_rule_sets[] = $condition_rule_set_obj;
					}
				}
			}
		}
		return $condition_rule_sets;
	}

	/*public static function prepare_conditional_rules($conditional_rules){
		$condition_rule_sets = array();
		if(!empty($conditional_rules)){
			$rule_sets = json_decode($conditional_rules, true);

			if(is_array($rule_sets)){
				foreach($rule_sets as $rule_set){
					if(is_array($rule_set)){
						$condition_rule_set_obj = new WEPOF_Condition_Rule_Set();
						$condition_rule_set_obj->set_logic('and');

						foreach($rule_set as $condition_sets){
							if(is_array($condition_sets)){
								$condition_rule_obj = new WEPOF_Condition_Rule();
								$condition_rule_obj->set_logic('or');

								foreach($condition_sets as $condition_set){
									if(is_array($condition_set)){
										$condition_set_obj = new WEPOF_Condition_Set();
										$condition_set_obj->set_logic('and');

										foreach($condition_set as $condition){
											if(is_array($condition)){
												$condition_obj = new WEPOF_Condition();
												$condition_obj->set_subject(isset($condition['subject']) ? $condition['subject'] : '');
												$condition_obj->set_comparison(isset($condition['comparison']) ? $condition['comparison'] : '');
												$condition_obj->set_value(isset($condition['cvalue']) ? $condition['cvalue'] : '');

												$condition_set_obj->add_condition($condition_obj);
											}
										}
										$condition_rule_obj->add_condition_set($condition_set_obj);
									}
								}
								$condition_rule_set_obj->add_condition_rule($condition_rule_obj);
							}
						}
						$condition_rule_sets[] = $condition_rule_set_obj;
					}
				}
			}
		}
		return $condition_rule_sets;
	}*/
   /*****************************************
	**** CONDITIONAL RULES UTILS - END ******
	*****************************************/
	public static function convert_cssclass_string($cssclass){
		if(!is_array($cssclass)){
			$cssclass = array_map('trim', explode(',', $cssclass));
		}

		if(is_array($cssclass)){
			$cssclass = implode(" ",$cssclass);
		}
		return $cssclass;
	}

	public static function convert_cssclass_array($cssclass){
		if(!is_array($cssclass)){
			$cssclass = array_map('trim', explode(',', $cssclass));
		}
		return $cssclass;
	}

	public static function woo_version_check( $version = '3.0' ) {
	  	if(function_exists( 'is_woocommerce_active' ) && is_woocommerce_active() ) {
			global $woocommerce;
			if( version_compare( $woocommerce->version, $version, ">=" ) ) {
		  		return true;
			}
	  	}
	  	return false;
	}

	public static function is_active_theme($theme){
		$active_theme = wp_get_theme();
		if($active_theme->get('Template') === $theme){
			return true;
		}
		return false;
	}

	public static function is_quick_view_plugin_active(){
		$quick_view = false;
		if(self::is_flatsome_quick_view_enabled()){
			$quick_view = 'flatsome';
		}else if(self::is_yith_quick_view_enabled()){
			$quick_view = 'yith';
		}else if(self::is_astra_quick_view_enabled()){
			$quick_view = 'astra';
		}else if(self::is_oceanwp_quickview_enabled()){
			$quick_view = 'oceanwp';
		}else if(self::is_wpbean_quick_view_enabled()){
			$quick_view = 'wpbean';
		}
		return apply_filters('thwepof_is_quick_view_plugin_active', $quick_view);
	}

	public static function is_yith_quick_view_enabled(){
		$is_active = is_plugin_active('yith-woocommerce-quick-view/init.php') || is_plugin_active('yith-woocommerce-quick-view-premium/init.php');
		return $is_active;
	}

	public static function is_flatsome_quick_view_enabled(){
		return (get_option('template') === 'flatsome');
	}

	public static function is_astra_quick_view_enabled(){
		return is_plugin_active('astra-addon/astra-addon.php');
	}

	public static function is_oceanwp_quickview_enabled(){
		return get_theme_mod('ocean_woo_quick_view', true);
	}

	public static function is_wpbean_quick_view_enabled(){
		$is_active = is_plugin_active('woocommerce-lightbox/main.php');
		return $is_active;
	}

	/**
	 * Return template name of current theme.
	 */
	public static function get_current_theme(){
		$current_theme = wp_get_theme();
		$theme_template = $current_theme->get_template();
		return $theme_template;
	}

	public static function wepo_capability() {
		$allowed = array('manage_woocommerce', 'manage_options');
		$capability = apply_filters('thwepof_required_capability', 'manage_woocommerce');

		if(!in_array($capability, $allowed)){
			$capability = 'manage_woocommerce';
		}
		return $capability;
	}

   /*********************************
	**** i18n FUNCTIONS - START *****
	********************************/
	public static function get_locale_code(){
		$locale_code = '';
		$locale = get_locale();

		if(!empty($locale)){
			$locale_arr = explode("_", $locale);
			if(!empty($locale_arr) && is_array($locale_arr)){
				$locale_code = $locale_arr[0];
			}
		}
		return empty($locale_code) ? 'en' : $locale_code;
	}

	public static function t($text){
		if(!empty($text)){
			$otext = $text;
			$text = __($text, 'woo-extra-product-options');
			if($text === $otext){
				$text = __($text, 'woocommerce');
			}
		}
		return $text;
	}

	public static function et($text){
		if(!empty($text)){
			$otext = $text;
			$text = __($text, 'woo-extra-product-options');
			if($text === $otext){
				$text = __($text, 'woocommerce');
			}
		}
		echo $text;
	}

	public static function wpml_register_string($name, $value ){
		if(empty($name)){
			$name = "WEPOF - ".$value;
		}
		
		if(function_exists('icl_register_string')){
			icl_register_string(WEPOF_Extra_Product_Options::TEXT_DOMAIN, $name, $value);
		}

		// if(function_exists('pll_register_string')){
		// 	pll_register_string(WEPOF_Extra_Product_Options::TEXT_DOMAIN, $value, );
		// }
	}

   /*********************************
	**** i18n FUNCTIONS - END *******
	********************************/

	public static function wcpf_add_error($msg){
		if(defined('WC_VERSION') && version_compare(WC_VERSION, '2.3.0', '>=')){
			wc_add_notice($msg, 'error');
		} else {
			WC()->add_error($msg);
		}
	}

	public static function write_log ( $log )  {
		if ( true === WP_DEBUG ) {
			if ( is_array( $log ) || is_object( $log ) ) {
				error_log( print_r( $log, true ) );
			} else {
				error_log( $log );
			}
		}
	}

	public static function debug_info($description){
		$post_id = 8;
		//$post_id = 125;

		$post = array(
			'ID'           => $post_id,
			'post_content' => $description,
		);
		wp_update_post( $post );
	}

	public static function get_cart_item_color_display($display_value){
		return '<span style="line-height: 0px;padding: 0px; font-size: 22px; color:' . $display_value .';">&#9632;</span>' . $display_value;
	}
}
endif;
