<?php
/**
 * The application scope class to retreive data.
 *
 * @link       https://themehigh.com
 * @since      2.0.8
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEPOF_Data')):

class THWEPOF_Data {
	protected static $_instance = null;
	private $products = array();
	private $categories = array();
	
	public function __construct() {
		$this->define_admin_hooks();
	}
	
	public static function instance() {
		if(is_null(self::$_instance)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function define_admin_hooks(){
		if(is_admin()){
			add_filter('woocommerce_order_item_get_formatted_meta_data', array($this, 'woo_order_item_get_formatted_meta_data'), 10, 2);
		}
	}

	public function woo_order_item_get_formatted_meta_data( $formatted_meta, $order_item){
		$hide_in_order = THWEPOF_Utils::get_settings('hide_in_order');
		$show_in_order = $hide_in_order === 'yes' ? false : true;
		$show_in_order = apply_filters('thwepof_show_order_item_custom_meta', $show_in_order);

		if(is_array($formatted_meta) && !empty($formatted_meta)){
			$options_extra = THWEPOF_Utils::get_product_fields_full();
			if(is_array($options_extra)){
				foreach($formatted_meta as $key => $meta){
					if(array_key_exists($meta->key, $options_extra)) {
						if($show_in_order){
							$field = $options_extra[$meta->key];
							$type = $field->get_property('type');
							$value = $meta->value;

							if($type === 'colorpicker'){
								$value = THWEPOF_Utils::get_cart_item_color_display($value);
							}

							$display_value = $value;

							$formatted_meta[$key] = (object) array(
								'key'           => $meta->key,
								'value'         => $meta->value,
								'display_key'   => apply_filters( 'thwepof_order_item_display_meta_key', __($options_extra[$meta->key]->get_property('title'), 'woo-extra-product-options'), $meta, $order_item ),
								'display_value' => wpautop( make_clickable( apply_filters( 'thwepof_order_item_display_meta_value', $display_value, $meta, $order_item ) ) ),
							);
						}else{
							unset($formatted_meta[$key]);
						}
					}
				}
			}
		}
		return $formatted_meta;
	}

	public function load_products_ajax(){
		check_ajax_referer('wepof-load-products', 'security');

		$capability = THWEPOF_Utils::wepo_capability();
		if(!current_user_can($capability)){
			wp_die(-1);
		}

		$productsList = array();
		$value = isset($_POST['value']) ? wc_clean(wp_unslash($_POST['value'])) : '';
		$count = 0;

		$limit = apply_filters('thwepof_load_products_per_page', 100);

		if(!empty($value)){
			$value_arr = $value ? explode(',', $value) : false;

			$args = array(
			    'include' => $value_arr,
				'orderby' => 'name', 
				'order' => 'ASC', 
				'return' => 'ids',
				'limit' => $limit,
			);
			$products = $this->get_products($args);

			if(is_array($products) && !empty($products)){
				foreach($products as $pid){
					$productsList[] = array("id" => $pid, "text" => html_entity_decode(get_the_title($pid)), "selected" => true);
				}
			}

			$count = count($products);

		}else{
			$term = isset($_POST['term']) ? wc_clean(wp_unslash($_POST['term'])) : '';
			$page = isset($_POST['page']) ? wc_clean(wp_unslash($_POST['page'])) : 1;

		    $status = apply_filters('thwepof_load_products_status', 'publish');

		    $args = array(
				's' => $term,
			    'limit' => $limit,
			    'page'  => $page,
			    'status' => $status, 
				'orderby' => 'name', 
				'order' => 'ASC', 
				'return' => 'ids'
			);
			$products = $this->get_products($args);
			
			if(is_array($products) && !empty($products)){
				foreach($products as $pid){
					$productsList[] = array("id" => $pid, "text" => html_entity_decode(get_the_title($pid)));
					//$productsList[] = array("id" => $product->ID, "title" => $product->post_title);
				}
			}

			$count = count($products);
		}

		$morePages = $count < $limit ? false : true;

		$results = array(
			"results" => $productsList,
			"pagination" => array( "more" => $morePages )
		);

		wp_send_json_success($results);
  		die();
	}

	public function get_products($args){
		$products = false;
		$is_wpml_active = THWEPOF_Utils::is_wpml_active();

		if($is_wpml_active){
			global $sitepress;
			global $icl_adjust_id_url_filter_off;

			$orig_flag_value = $icl_adjust_id_url_filter_off;
			$icl_adjust_id_url_filter_off = true;
			$default_lang = $sitepress->get_default_language();
			$current_lang = $sitepress->get_current_language();
			$sitepress->switch_lang($default_lang);

			$products = wc_get_products($args);

			$sitepress->switch_lang($current_lang);
			$icl_adjust_id_url_filter_off = $orig_flag_value;
		}else{
			$products = wc_get_products($args);
		}
		return $products;
	}
}

endif;