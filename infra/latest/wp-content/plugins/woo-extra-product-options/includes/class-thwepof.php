<?php
/**
 * Woo Extra Product Options Settings
 *
 * @author   ThemeHiGH
 * @category Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('THWEPOF')) :
class THWEPOF {
	protected static $_instance = null;
	public $wepof_admin = null;
	public $wepof_public = null;
	public $screen_id;

	public function __construct() {
		$required_classes = apply_filters('th_wepof_require_class', array(
			'common' => array(
				'includes/model/rules/class-wepof-condition.php',
				'includes/model/rules/class-wepof-condition-set.php',
				'includes/model/rules/class-wepof-rule.php',
				'includes/model/rules/class-wepof-rule-set.php',
				'includes/model/fields/class-wepof-field.php',
				'includes/model/fields/class-wepof-field-inputtext.php',
				'includes/model/fields/class-wepof-field-email.php',
				'includes/model/fields/class-wepof-field-url.php',
				'includes/model/fields/class-wepof-field-range.php',
				'includes/model/fields/class-wepof-field-hidden.php',
				'includes/model/fields/class-wepof-field-number.php',
				'includes/model/fields/class-wepof-field-tel.php',
				'includes/model/fields/class-wepof-field-password.php',
				'includes/model/fields/class-wepof-field-textarea.php',
				'includes/model/fields/class-wepof-field-select.php',
				'includes/model/fields/class-wepof-field-checkbox.php',
				'includes/model/fields/class-wepof-field-checkboxgroup.php',
				'includes/model/fields/class-wepof-field-radio.php',
				'includes/model/fields/class-wepof-field-datepicker.php',
				'includes/model/fields/class-wepof-field-colorpicker.php',
				'includes/model/fields/class-wepof-field-heading.php',
				'includes/model/fields/class-wepof-field-paragraph.php',
				'includes/model/fields/class-wepof-field-switch.php',
				'includes/model/fields/class-wepof-field-separator.php',
				'includes/model/class-wepof-section.php',

				'includes/utils/class-thwepof-utils.php',
				'includes/utils/class-thwepof-utils-field.php',
				'includes/utils/class-thwepof-utils-section.php',
				'includes/class-thwepof-data.php',

				/*'classes/fe/rules/class-wepof-condition.php',
				'classes/fe/rules/class-wepof-condition-set.php',
				'classes/fe/rules/class-wepof-rule.php',
				'classes/fe/rules/class-wepof-rule-set.php',
				'classes/fe/fields/class-wepof-field.php',
				'classes/fe/fields/class-wepof-field-inputtext.php',
				'classes/fe/fields/class-wepof-field-hidden.php',
				'classes/fe/fields/class-wepof-field-number.php',
				'classes/fe/fields/class-wepof-field-tel.php',
				'classes/fe/fields/class-wepof-field-password.php',
				'classes/fe/fields/class-wepof-field-textarea.php',
				'classes/fe/fields/class-wepof-field-select.php',
				'classes/fe/fields/class-wepof-field-checkbox.php',
				'classes/fe/fields/class-wepof-field-checkboxgroup.php',
				'classes/fe/fields/class-wepof-field-radio.php',
				'classes/fe/fields/class-wepof-field-datepicker.php',
				'classes/fe/fields/class-wepof-field-colorpicker.php',
				'classes/fe/fields/class-wepof-field-heading.php',
				'classes/fe/fields/class-wepof-field-paragraph.php',
				'classes/fe/class-wepof-section.php',
				'classes/fe/class-wepof-utils.php',
				'classes/fe/class-wepof-utils-field.php',
				'classes/fe/class-wepof-utils-section.php',
				'classes/fe/class-wepof-data.php',*/
			),
			'admin' => array(
				'admin/class-thwepof-admin-form.php',
				'admin/class-thwepof-admin-form-section.php',
				'admin/class-thwepof-admin-form-field.php',

				'admin/class-thwepof-admin-settings.php',
				'admin/class-thwepof-admin-settings-general.php',
				'admin/class-thwepof-admin-settings-advanced.php',
				'admin/class-thwepof-admin-settings-pro.php',

				//'classes/class-wepof-settings-page.php',
				//'classes/fe/class-wepof-product-options-settings.php',
				//'classes/fe/class-thwepof-admin-settings-advanced.php',
			),
			'public' => array(
				'public/class-thwepof-public.php',
				//'classes/fe/class-wepof-product-options-frontend.php',
			),
		));

		$this->include_required( $required_classes );
		$this->may_copy_older_version_settings();

		add_action('admin_menu', array($this, 'admin_menu'));
		add_filter('woocommerce_screen_ids', array($this, 'add_screen_id'));
		add_filter('plugin_action_links_'.THWEPOF_BASE_NAME, array($this, 'add_settings_link'));
		add_action('upgrader_process_complete', array($this, 'may_copy_older_version_settings'));

		// add_action('wp_ajax_dismiss_thwepof_review_request_notice', array($this, 'dismiss_thwepof_review_request_notice'));
		// add_action('wp_ajax_skip_thwepof_review_request_notice', array($this, 'skip_thwepof_review_request_notice'));
		add_action('admin_footer', array($this,'quick_links'),10);
		add_action('admin_footer', array($this, 'admin_notice_js_snippet'), 9999);
		add_action('wp_ajax_hide_thwepo_admin_notice', array($this, 'hide_thwepo_admin_notice'));
		add_action('admin_footer', array($this,'thwepof_display_discount_announcement'));
		add_action('admin_footer-plugins.php', array($this, 'wepo_deactivation_form'));
		add_action('wp_ajax_thwepo_deactivation_reason', array($this, 'thwepo_deactivation_reason'));

		$this->init();
	}

	public static function instance() {
		if(is_null(self::$_instance)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	protected function include_required( $required_classes ) {
		if(!function_exists('is_plugin_active')) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		foreach($required_classes as $section => $classes ) {
			foreach( $classes as $class ){
				if('common' == $section  || ('public' == $section && !is_admin() || ( defined('DOING_AJAX') && DOING_AJAX) )
					|| ('admin' == $section && is_admin()) && file_exists( THWEPOF_PATH . $class )){
					require_once( THWEPOF_PATH . $class );
				}
			}
		}
	}

	public function init() {
		$wepo_data = THWEPOF_Data::instance();
		add_action('wp_ajax_thwepof_load_products', array($wepo_data, 'load_products_ajax'));
    	add_action('wp_ajax_nopriv_thwepof_load_products', array($wepo_data, 'load_products_ajax'));

		if(!is_admin() || (defined( 'DOING_AJAX' ) && DOING_AJAX)){
			$this->wepof_public = new THWEPOF_Public();
		}else if(is_admin()){
			$this->wepof_admin = THWEPOF_Admin_Settings_General::instance();
		}

		//$this->may_copy_older_version_settings();
	}

	public function admin_menu() {
		$capability = THWEPOF_Utils::wepo_capability();
		$this->screen_id = add_submenu_page('edit.php?post_type=product', __('WooCommerce Extra Product Option', 'woo-extra-product-options'),
		__('Extra Product Option', 'woo-extra-product-options'), $capability, 'thwepof_extra_product_options', array($this, 'output_settings'));

		add_action('admin_print_scripts-'. $this->screen_id, array($this, 'enqueue_admin_scripts'));
	}

	public function add_screen_id($ids){
		$ids[] = 'product_page_thwepof_extra_product_options';
		$ids[] = strtolower(__('Product', 'woocommerce')) .'_page_thwepof_extra_product_options';
		return $ids;
	}

	public function add_settings_link($links) {
		$settings_link = '<a href="'.esc_url(admin_url('edit.php?post_type=product&page=thwepof_extra_product_options')).'">'. __('Settings') .'</a>';
		array_unshift($links, $settings_link);
		$premium_link = '<a href="https://www.themehigh.com/product/woocommerce-extra-product-options?utm_source=free&utm_medium=plugin_action_link&utm_campaign=wepo_upgrade_link" style="color:green; font-weight:bold" target="_blank">'. __('Get Pro') .'</a>';
		array_push($links, $premium_link);

		if (array_key_exists('deactivate', $links)) {
		    $links['deactivate'] = str_replace('<a', '<a class="thwepo-deactivate-link"', $links['deactivate']);
		}

		return $links;
	}

	public function output_settings() {
		// echo '<div class="wrap">';
		// echo '<h2></h2>';
		//$this->output_old_settings_copy_message();
		$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general_settings';

		echo '<div class="thwepof-wrap">';
		if($tab === 'advanced_settings'){			
			$advanced_settings = THWEPOF_Admin_Settings_Advanced::instance();	
			$advanced_settings->render_page();			
		}elseif($tab === 'pro'){
			$pro_details = THWEPOF_Admin_Settings_Pro::instance();	
			$pro_details->render_page();
		}else{
			$general_settings = THWEPOF_Admin_Settings_General::instance();
			$general_settings->render_page();
		}
		echo '</div">';
		// echo '</div>';
	}

	public function enqueue_admin_scripts() {
		$debug_mode = apply_filters('thwepof_debug_mode', false);
		$suffix = $debug_mode ? '' : '.min';

		wp_enqueue_style (array('woocommerce_admin_styles', 'jquery-ui-style'));
		wp_enqueue_style ('thwepof-admin-style', THWEPOF_URL.'admin/assets/css/thwepof-admin'. $suffix .'.css', THWEPOF_VERSION);
		wp_enqueue_script('thwepof-admin-script', THWEPOF_URL.'admin/assets/js/thwepof-admin'. $suffix .'.js',
		array('jquery', 'jquery-ui-sortable', 'jquery-tiptip', 'wc-enhanced-select', 'selectWoo'), THWEPOF_VERSION, false);

		$wepof_var = array(
			'tag'                 => __('Tag', 'woo-extra-product-options'),
			'equal'               => __('Equals to/ In', 'woo-extra-product-options'),
			'notequal'            => __('Not Equals to/ Not in', 'woo-extra-product-options'),
			'product'             => __('Product', 'woo-extra-product-options'),
			'category'            => __('Category', 'woo-extra-product-options'),
			'load_product_nonce'  => wp_create_nonce('wepof-load-products'),

			'edit_field'          => __('Edit Field', 'woo-extra-product-options'),
			'new_field'           => __('New Field', 'woo-extra-product-options'),
			'new_section'         => __('New Section', 'woo-extra-product-options'),
			'edit_section'        => __('Edit Section', 'woo-extra-product-options'),
		);
		wp_localize_script('thwepof-admin-script', 'thwepof_admin_var', $wepof_var);
	}

	public function output_old_settings_copy_message(){
		$new_settings = THWEPOF_Utils::get_sections();
		if($new_settings){
			return;
		}

		$custom_fields = get_option('thwepof_custom_product_fields');

		if(is_array($custom_fields) && !empty($custom_fields)){
			if(isset($_POST['may_copy_settings']))
				$result = $this->may_copy_older_version_settings();

			?>
			<form method="post" action="">
				<p>Copy older version settings <input type="submit" name="may_copy_settings" value="Copy Settings" /></p>
	        </form>
			<?php
		}
	}

	public function may_copy_older_version_settings(){
		$new_settings = THWEPOF_Utils::get_sections();
		if($new_settings){
			return;
		}

		$custom_fields = get_option('thwepof_custom_product_fields');
		if(is_array($custom_fields) && !empty($custom_fields)){
			$fields_before = isset($custom_fields['woo_before_add_to_cart_button']) ? $custom_fields['woo_before_add_to_cart_button'] : false;
			$fields_after = isset($custom_fields['woo_after_add_to_cart_button']) ? $custom_fields['woo_after_add_to_cart_button'] : false;

			$section_before = THWEPOF_Utils_Section::prepare_default_section();
			$section_after = THWEPOF_Utils_Section::prepare_default_section();

			if(is_array($fields_before)){
				foreach($fields_before as $key => $field){
					$section_before = THWEPOF_Utils_Section::add_field($section_before, $field);
				}
			}

			if(is_array($fields_after)){
				foreach($fields_after as $key => $field){
					$section_after = THWEPOF_Utils_Section::add_field($section_after, $field);
				}
			}

			$result1 = $result2 = false;

			if(THWEPOF_Utils_Section::has_fields($section_after)){
				if(THWEPOF_Utils_Section::has_fields($section_before)){
					$section_before->set_property('id', 'default');
					$section_before->set_property('name', 'default');
					$section_before->set_property('title', 'Section 1');

					$section_after->set_property('id', 'section_2');
					$section_after->set_property('name', 'section_2');
					$section_after->set_property('title', 'Section 2');

					$result1 = THWEPOF_Utils::update_section($section_before);
				}else{
					$result1 = true;
				}
				$section_after->set_property('position', 'woo_after_add_to_cart_button');
				$result2 = THWEPOF_Utils::update_section($section_after);

			}else if(THWEPOF_Utils_Section::has_fields($section_before)){
				$result1 = THWEPOF_Utils::update_section($section_before);
				$result2 = true;
			}

			if($result1 && $result2){
				update_option('thwepof_custom_product_fields_bkp', $custom_fields, 'no');
				delete_option('thwepof_custom_product_fields');
			}
		}
	}

	public function thwepof_discount_popup_actions() {
		$nonce = isset($_GET['thwepof_discount_popup_nonce']) ? $_GET['thwepof_discount_popup_nonce'] : false;
		if(!wp_verify_nonce($nonce, 'thwepof_discount_popup_security')){
			die();
		}
		$thwepof_dissmis_feature_popup = isset($_GET['thwepof_discount_popup_dismiss']) ? sanitize_text_field( wp_unslash($_GET['thwepof_discount_popup_dismiss'])) : false;
		
		if ($thwepof_dissmis_feature_popup) {
			update_user_meta( get_current_user_id(), 'thwepof_discount_popup' , true);
		}
	}

	public function thwepof_display_discount_announcement(){

		$thwepof_since = get_option('thwepof_since');
		$now = time();

		// $render_time = apply_filters('thwepof_show_discount_popup_render_time' , 3 * MONTH_IN_SECONDS);
		$render_time  = apply_filters('thwepof_show_discount_popup_render_time', 1 * MONTH_IN_SECONDS);
		$render_time = $thwepof_since + $render_time;
		
		if (isset($_GET['thwepof_discount_popup_dismiss'])) {
			$this->thwepof_discount_popup_actions();
		}

		$discount_popup = get_user_meta( get_current_user_id(),'thwepof_discount_popup', true);

		$show_discount_popup = isset($discount_popup) ? $discount_popup : false;

		if (!$show_discount_popup && ($now > $render_time)) {
			$this->secret_discount_popup();
		}
	}

	public function secret_discount_popup(){
		$admin_url = 'edit.php?post_type=product&page=thwepof_extra_product_options';
        $dismiss_url = $admin_url . '&thwepof_discount_popup_dismiss=true&thwepof_discount_popup_nonce=' . wp_create_nonce( 'thwepof_discount_popup_security');

		$url = "https://www.themehigh.com/?edd_action=add_to_cart&download_id=17&cp=lyCDSy_wepo&utm_source=free&utm_medium=premium_tab&utm_campaign=wepo_upgrade_link";

		$current_page = isset( $_GET['page'] ) ? $_GET['page']  : '';
		
		if($current_page !== 'thwepof_extra_product_options'){
			return;
		}

		?>
			<div id="thwepof-pro-discount-popup" class="thwepof-pro-discount-popup" style="display:none">
				<div id="thwepof-discount-popup-wrapper" class="thwepof-discount-popup-wrapper">
					<div class="thwepof-pro-offer">
						<div class="thwepof-discount-popup-close">
							<a id="thwepof-discount-close-btn" class="thwepof-discount-close-btn" href="<?php echo esc_url($dismiss_url); ?>"><img class="close-btn-img-popup" src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/close-popup.svg'); ?>"></a>
						</div>
						<div class="thwepof-discount-desc">
							<p class="thwepof-discount-desc-first">Exclusive offer for you.</p>
							<p class="thwepof-discount-desc-middle">Flat 50% off</p>
							<p class="thwepof-discount-desc-last">on your plan upgrade.</p>
							<p class="thwepof-discount-exp-date">Hurry, grab the offer before it expires.</p>
						</div>
					</div>
					<div class="thwcd-pro-claim-offer">
						<div class="thwepof-pro-offer-desc">
							<p class="thwepof-pro-offer-para">Extend the plugin functionalities and optimize your WooCommerce product page to its next level using the pro version of the Extra Product Options plugin.</p>
						</div>
						<div class="claim-discount-btn-div">
							<a id="claim-discount-btn" class="claim-discount-btn" href="<?php echo esc_url($url); ?>" onclick="thwepofPopUpClose(this)" target="_blank" rel="noopener noreferrer">Claim Now</a>
						</div>
					</div>
				</div>
			</div>	
		<?php
	}
	
	// Old Review Banner
	// public function dismiss_thwepof_review_request_notice(){
	// 	if(! check_ajax_referer( 'thwepof_review_request_notice', 'security' )){
	// 		die();
	// 	}
	// 	set_transient('thwepof_review_request_notice_dismissed', true, apply_filters('thwepof_dismissed_review_request_notice_lifespan', 1 * YEAR_IN_SECONDS));
	// }

	// public function skip_thwepof_review_request_notice(){
	// 	if(! check_ajax_referer( 'thwepof_review_request_notice', 'security' )){
	// 		die();
	// 	}
	// 	set_transient('thwepof_skip_review_request_notice', true, apply_filters('thwepof_skip_review_request_notice_lifespan', 1 * DAY_IN_SECONDS));
	// }

	public function hide_thwepo_admin_notice(){
		check_ajax_referer('thwepof_notice_security', 'thwepo_review_nonce');

		$capability = THWEPOF_Utils::wepo_capability();
		if(!current_user_can($capability)){
			wp_die(-1);
		}

		$now = time();
		update_user_meta( get_current_user_id(), 'thwepo_review_skipped', true );
		update_user_meta( get_current_user_id(), 'thwepo_review_skipped_time', $now );
	}

	public function admin_notice_js_snippet(){
		if(!apply_filters('thwepo_dismissable_admin_notice_javascript', true)){
			return;
		}		
		?>
	    <script>
			var thwepo_dismissable_notice = (function($, window, document) {
				'use strict';

				$( document ).on( 'click', '.thwepo-review-wrapper  .notice-dismiss', function() {
					var wrapper = $(this).closest('div.thpladmin-notice');
					var nonce = wrapper.data("nonce");
					var data = {
						thwepo_review_nonce: nonce,
						action: 'hide_thwepo_admin_notice',
					};
					$.post( ajaxurl, data, function() {

					});
				});

			}(window.jQuery, window, document));	
	    </script>
	    <?php
	}

	public function wepo_deactivation_form(){
		$is_snooze_time = get_user_meta( get_current_user_id(), 'thwepof_deactivation_snooze', true );
		$now = time();

		if($is_snooze_time && ($now < $is_snooze_time)){
			return;
		}

		$deactivation_reasons = $this->get_deactivation_reasons();
		?>
		<div id="thwepo_deactivation_form" class="thpladmin-modal-mask">
			<div class="thpladmin-modal">
				<div class="modal-container">
					<!-- <span class="modal-close" onclick="thwepofCloseModal(this)">×</span> -->
					<div class="modal-content">
						<div class="modal-body">
							<div class="model-header">
								<img class="th-logo" src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/themehigh.svg'); ?>" alt="themehigh-logo">
								<span><?php echo __('Quick Feedback', 'woo-extra-product-options'); ?></span>
							</div>

							<!-- <div class="get-support-version-b">
								<p>We are sad to see you go. We would be happy to fix things for you. Please raise a ticket to get help</p>
								<a class="thwepo-link thwepo-right-link thwepo-active" target="_blank" href="https://help.themehigh.com/hc/en-us/requests/new?utm_source=wepo_free&utm_medium=feedback_form&utm_campaign=get_support"><?php echo __('Get Support', 'woo-extra-product-options'); ?></a>
							</div> -->

							<main class="form-container main-full">
								<p class="thwepo-title-text"><?php echo __('If you have a moment, please let us know why you want to deactivate this plugin', 'woo-extra-product-options'); ?></p>
								<ul class="deactivation-reason" data-nonce="<?php echo wp_create_nonce('thwepo_deactivate_nonce'); ?>">
									<?php 
									if($deactivation_reasons){
										foreach($deactivation_reasons as $key => $reason){
											$reason_type = isset($reason['reason_type']) ? $reason['reason_type'] : '';
											$reason_placeholder = isset($reason['reason_placeholder']) ? $reason['reason_placeholder'] : '';
											?>
											<li data-type="<?php echo esc_attr($reason_type); ?>" data-placeholder="<?php echo esc_attr($reason_placeholder); ?> ">
												<label>
													<input type="radio" name="selected-reason" value="<?php echo esc_attr($key); ?>">
													<span><?php echo esc_html($reason['radio_label']); ?></span>
												</label>
											</li>
											<?php
										}
									}
									?>
								</ul>
								<p class="thwepo-privacy-cnt"><?php echo __('This form is only for getting your valuable feedback. We do not collect your personal data. To know more read our ', 'woo-extra-product-options'); ?> <a class="thwepo-privacy-link" target="_blank" href="<?php echo esc_url('https://www.themehigh.com/privacy-policy/');?>"><?php echo __('Privacy Policy', 'woo-extra-product-options'); ?></a></p>
							</main>
							<footer class="modal-footer">
								<div class="thwepo-left">
									<a class="thwepo-link thwepo-left-link thwepo-deactivate" href="#"><?php echo __('Skip & Deactivate', 'woo-extra-product-options'); ?></a>
								</div>
								<div class="thwepo-right">
									
									<a class="thwepo-link thwepo-right-link thwepo-active" target="_blank" href="https://help.themehigh.com/hc/en-us/requests/new?utm_source=wepo_free&utm_medium=feedback_form&utm_campaign=get_support"><?php echo __('Get Support', 'woo-extra-product-options'); ?></a>

									<a class="thwepo-link thwepo-right-link thwepo-active thwepo-submit-deactivate" href="#"><?php echo __('Submit and Deactivate', 'woo-extra-product-options'); ?></a>
									<a class="thwepo-link thwepo-right-link thwepo-close" href="#"><?php echo __('Cancel', 'woo-extra-product-options'); ?></a>
								</div>
							</footer>
						</div>
					</div>
				</div>
			</div>
		</div>
		<style type="text/css">
			.th-logo{
			    margin-right: 10px;
			}
			.thpladmin-modal-mask{
			    position: fixed;
			    background-color: rgba(17,30,60,0.6);
			    top: 0;
			    left: 0;
			    width: 100%;
			    height: 100%;
			    z-index: 9999;
			    overflow: scroll;
			    transition: opacity 250ms ease-in-out;
			}
			.thpladmin-modal-mask{
			    display: none;
			}
			.thpladmin-modal .modal-container{
			    position: absolute;
			    background: #fff;
			    border-radius: 2px;
			    overflow: hidden;
			    left: 50%;
			    top: 50%;
			    transform: translate(-50%,-50%);
			    width: 50%;
			    max-width: 960px;
			    /*min-height: 560px;*/
			    /*height: 80vh;*/
			    /*max-height: 640px;*/
			    animation: appear-down 250ms ease-in-out;
			    border-radius: 15px;
			}
			.model-header {
			    padding: 21px;
			}
			.thpladmin-modal .model-header span {
			    font-size: 18px;
			    font-weight: bold;
			}
			.thpladmin-modal .model-header {
			    padding: 21px;
			    background: #ECECEC;
			}
			.thpladmin-modal .form-container {
			    margin-left: 23px;
			    clear: both;
			}
			.thpladmin-modal .deactivation-reason input {
			    margin-right: 13px;
			}
			.thpladmin-modal .thwepo-privacy-cnt {
			    color: #919191;
			    font-size: 12px;
			    margin-bottom: 31px;
			    margin-top: 18px;
			    max-width: 75%;
			}
			.thpladmin-modal .deactivation-reason li {
			    margin-bottom: 17px;
			}
			.thpladmin-modal .modal-footer {
			    padding: 20px;
			    border-top: 1px solid #E7E7E7;
			    float: left;
			    width: 100%;
			    box-sizing: border-box;
			}
			.thwepo-left {
			    float: left;
			}
			.thwepo-right {
			    float: right;
			}
			.thwepo-link {
			    line-height: 31px;
			    font-size: 12px;
			}
			.thwepo-left-link {
			    font-style: italic;
			}
			.thwepo-right-link {
			    padding: 0px 20px;
			    border: 1px solid;
			    display: inline-block;
			    text-decoration: none;
			    border-radius: 5px;
			}
			.thwepo-right-link.thwepo-active {
			    background: #0773AC;
			    color: #fff;
			}
			.thwepo-title-text {
			    color: #2F2F2F;
			    font-weight: 500;
			    font-size: 15px;
			}
			.reason-input {
			    margin-left: 31px;
			    margin-top: 11px;
			    width: 70%;
			}
			.reason-input input {
			    width: 100%;
			    height: 40px;
			}
			.reason-input textarea {
			    width: 100%;
			    min-height: 80px;
			}
			input.th-snooze-checkbox {
			    width: 15px;
			    height: 15px;
			}
			input.th-snooze-checkbox:checked:before {
			    width: 1.2rem;
			    height: 1.2rem;
			}
			.th-snooze-select {
			    margin-left: 20px;
			    width: 172px;
			}

			/* Version B */
			.get-support-version-b {
			    width: 100%;
			    padding-left: 23px;
			    clear: both;
			    float: left;
			    box-sizing: border-box;
			    background: #0673ab;
			    color: #fff;
			    margin-bottom: 20px;
			}
			.get-support-version-b p {
			    font-size: 12px;
			    line-height: 17px;
			    width: 70%;
			    display: inline-block;
			    margin: 0px;
			    padding: 15px 0px;
			}
			.get-support-version-b .thwepo-right-link {
			    background-image: url(<?php echo esc_url(THWEPOF_URL .'admin/assets/css/get_support_icon.svg'); ?>);
			    background-repeat: no-repeat;
			    background-position: 11px 10px;
			    padding-left: 31px;
			    color: #0773AC;
			    background-color: #fff;
			    float: right;
			    margin-top: 17px;
			    margin-right: 20px;
			}
			.thwepo-privacy-link {
			    font-style: italic;
			}
			.wepo-review-link {
			    margin-top: 7px;
			    margin-left: 31px;
			    font-size: 16px;
			}
			span.wepo-rating-link {
			    color: #ffb900;
			}
			.thwepo-review-and-deactivate {
			    text-decoration: none;
			}
		</style>

		<script type="text/javascript">
			(function($){
				var popup = $("#thwepo_deactivation_form");
				var deactivation_link = '';

				$('.thwepo-deactivate-link').on('click', function(e){
					e.preventDefault();
					deactivation_link = $(this).attr('href');
					popup.css("display", "block");
					popup.find('a.thwepo-deactivate').attr('href', deactivation_link);
				});

				popup.on('click', 'input[type="radio"]', function () {
					var parent = $(this).parents('li:first');
                    popup.find('.reason-input').remove();

                    var type = parent.data('type');
                    var placeholder = parent.data('placeholder');

                    var reason_input = '';
                    if('text' == type){
                    	reason_input += '<div class="reason-input">';
                    	reason_input += '<input type="text" placeholder="'+ placeholder +'">';
                    	reason_input += '</div>';
                    }else if('textarea' == type){
                    	reason_input += '<div class="reason-input">';
                    	reason_input += '<textarea row="5" placeholder="'+ placeholder +'">';
                    	reason_input += '</textarea>';
                    	reason_input += '</div>';
                    }else if('checkbox' == type){
                    	reason_input += '<div class="reason-input ">';
                    	reason_input += '<input type="checkbox" id="th-snooze" name="th-snooze" class="th-snooze-checkbox">';
                    	reason_input += '<label for="th-snooze">Snooze this panel while troubleshooting</label>';
                    	reason_input += '<select name="th-snooze-time" class="th-snooze-select" disabled>';
                    	reason_input += '<option value="<?php echo HOUR_IN_SECONDS ?>">1 Hour</option>';
                    	reason_input += '<option value="<?php echo 12*HOUR_IN_SECONDS ?>">12 Hour</option>';
                    	reason_input += '<option value="<?php echo DAY_IN_SECONDS ?>">24 Hour</option>';
                    	reason_input += '<option value="<?php echo WEEK_IN_SECONDS ?>">1 Week</option>';
                    	reason_input += '<option value="<?php echo MONTH_IN_SECONDS ?>">1 Month</option>';
                    	reason_input += '</select>';
                    	reason_input += '</div>';
                    }else if('reviewlink' == type){
                    	reason_input += '<div class="reason-input wepo-review-link">';
                    	/*
                    	reason_input += '<?php _e('Deactivate and ', 'woo-extra-product-options');?>'
                    	reason_input += '<a href="#" target="_blank" class="thwepo-review-and-deactivate">';
                    	reason_input += '<?php _e('leave a review', 'woo-extra-product-options'); ?>';
                    	reason_input += '<span class="wepo-rating-link"> &#9733;&#9733;&#9733;&#9733;&#9733; </span>';
                    	reason_input += '</a>';
                    	*/
                    	reason_input += '<input type="hidden" value="<?php _e('Upgraded', 'woo-extra-product-options');?>">';
                    	reason_input += '</div>';
                    }

                    if(reason_input !== ''){
                    	parent.append($(reason_input));
                    }
				});

				popup.on('click', '.thwepo-close', function () {
					popup.css("display", "none");
				});

				/*
				popup.on('click', '.thwepo-review-and-deactivate', function () {
					e.preventDefault();
                    window.open("https://wordpress.org/support/plugin/woo-extra-product-options/reviews/?rate=5#new-post");
                    console.log(deactivation_link);
                    window.location.href = deactivation_link;
				});
				*/

				popup.on('click', '.thwepo-submit-deactivate', function (e) {
                    e.preventDefault();
                    var button = $(this);
                    if (button.hasClass('disabled')) {
                        return;
                    }
                    var radio = $('.deactivation-reason input[type="radio"]:checked');
                    var parent_li = radio.parents('li:first');
                    var parent_ul = radio.parents('ul:first');
                    var input = parent_li.find('textarea, input[type="text"], input[type="hidden"]');
                    var wepo_deacive_nonce = parent_ul.data('nonce');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'thwepo_deactivation_reason',
                            reason: (0 === radio.length) ? 'none' : radio.val(),
                            comments: (0 !== input.length) ? input.val().trim() : '',
                            security: wepo_deacive_nonce,
                        },
                        beforeSend: function () {
                            button.addClass('disabled');
                            button.text('Processing...');
                        },
                        complete: function () {
                            window.location.href = deactivation_link;
                        }
                    });
                });

                popup.on('click', '#th-snooze', function () {
                	if($(this).is(':checked')){
                		popup.find('.th-snooze-select').prop("disabled", false);
                	}else{
                		popup.find('.th-snooze-select').prop("disabled", true);
                	}
				});

			}(jQuery))
		</script>

		<?php 
	}

	private function get_deactivation_reasons(){
		return array(
			'upgraded_to_wepo_pro' => array(
				'radio_val'          => 'upgraded_to_wepo_pro',
				'radio_label'        => __('Upgraded to premium.', 'woo-extra-product-options'),
				'reason_type'        => 'reviewlink',
				'reason_placeholder' => '',
			),

			'feature_missing'=> array(
				'radio_val'          => 'feature_missing',
				'radio_label'        => __('A specific feature is missing', 'woo-extra-product-options'),
				'reason_type'        => 'text',
				'reason_placeholder' => __('Type in the feature', 'woo-extra-product-options'),
			),

			'error_or_not_working'=> array(
				'radio_val'          => 'error_or_not_working',
				'radio_label'        => __('Found an error in the plugin/ Plugin was not working', 'woo-extra-product-options'),
				'reason_type'        => 'text',
				'reason_placeholder' => __('Specify the issue', 'woo-extra-product-options'),
			),

			'hard_to_use' => array(
				'radio_val'          => 'hard_to_use',
				'radio_label'        => __('It was hard to use', 'woo-extra-product-options'),
				'reason_type'        => 'text',
				'reason_placeholder' => __('How can we improve your experience?', 'woo-extra-product-options'),
			),

			'found_better_plugin' => array(
				'radio_val'          => 'found_better_plugin',
				'radio_label'        => __('I found a better Plugin', 'woo-extra-product-options'),
				'reason_type'        => 'text',
				'reason_placeholder' => __('Could you please mention the plugin?', 'woo-extra-product-options'),
			),

			// 'not_working_as_expected'=> array(
			// 	'radio_val'          => 'not_working_as_expected',
			// 	'radio_label'        => __('The plugin didn’t work as expected', 'woo-extra-product-options'),
			// 	'reason_type'        => 'text',
			// 	'reason_placeholder' => __('Specify the issue', 'woo-extra-product-options'),
			// ),

			'temporary' => array(
				'radio_val'          => 'temporary',
				'radio_label'        => __('It’s a temporary deactivation - I’m troubleshooting an issue', 'woo-extra-product-options'),
				'reason_type'        => 'checkbox',
				'reason_placeholder' => __('Could you please mention the plugin?', 'woo-extra-product-options'),
			),

			'other' => array(
				'radio_val'          => 'other',
				'radio_label'        => __('Not mentioned here', 'woo-extra-product-options'),
				'reason_type'        => 'textarea',
				'reason_placeholder' => __('Kindly tell us your reason, so that we can improve', 'woo-extra-product-options'),
			),
		);
	}

	public function thwepo_deactivation_reason(){
		global $wpdb;

		check_ajax_referer('thwepo_deactivate_nonce', 'security');

		if(!isset($_POST['reason'])){
			return;
		}

		if($_POST['reason'] === 'temporary'){

			$snooze_period = isset($_POST['th-snooze-time']) && $_POST['th-snooze-time'] ? $_POST['th-snooze-time'] : MINUTE_IN_SECONDS ;
			$time_now = time();
			$snooze_time = $time_now + $snooze_period;

			update_user_meta(get_current_user_id(), 'thwepof_deactivation_snooze', $snooze_time);

			return;
		}
		
		$data = array(
			'plugin'        => 'wepo',
			'reason' 	    => sanitize_text_field($_POST['reason']),
			'comments'	    => isset($_POST['comments']) ? sanitize_textarea_field(wp_unslash($_POST['comments'])) : '',
	        'date'          => gmdate("M d, Y h:i:s A"),
	        'software'      => $_SERVER['SERVER_SOFTWARE'],
	        'php_version'   => phpversion(),
	        'mysql_version' => $wpdb->db_version(),
	        'wp_version'    => get_bloginfo('version'),
	        'wc_version'    => (!defined('WC_VERSION')) ? '' : WC_VERSION,
	        'locale'        => get_locale(),
	        'multisite'     => is_multisite() ? 'Yes' : 'No',
	        'plugin_version'=> THWEPOF_VERSION
		);

		$response = wp_remote_post('https://feedback.themehigh.in/api/add_feedbacks', array(
	        'method'      => 'POST',
	        'timeout'     => 45,
	        'redirection' => 5,
	        'httpversion' => '1.0',
	        'blocking'    => false,
	        'headers'     => array( 'Content-Type' => 'application/json' ),
	        'body'        => json_encode($data),
	        'cookies'     => array()
	            )
	    );

	    wp_send_json_success();
	}

	public function quick_links(){

		$current_screen = get_current_screen();
		if($current_screen->id !== 'product_page_thwepof_extra_product_options'){
			return;
		}
		
		?>
		<div class="th_quick_widget-float">
			<div id="myDIV" class="th_quick_widget">
				<div class="th_whead">
					<div class="th_whead_close_btn" onclick="thwepofwidgetClose()">
						<svg
						width="8"
                		height="8"
                		viewBox="0 0 8 8"
                		fill="none"
                		xmlns="http://www.w3.org/2000/svg"
                		>
                		<path
                		d="M1 1.25786C1.00401 1.25176 1.00744 1.24529 1.01024 1.23853C1.08211 0.993028 1.35531 0.919956 1.53673 1.10012C1.86493 1.42605 2.19216 1.75313 2.51843 2.08137L3.95107 3.51439C3.96595 3.52947 3.9816 3.54378 4.01406 3.57471C4.02464 3.55274 4.0376 3.53199 4.0527 3.51285C4.84988 2.71382 5.64738 1.91551 6.4452 1.11791C6.53582 1.02724 6.63706 0.978916 6.76632 1.01139C6.81535 1.02341 6.86066 1.04735 6.89822 1.08109C6.93579 1.11484 6.96444 1.15734 6.98165 1.20483C6.99885 1.25231 7.00407 1.30332 6.99684 1.35331C6.98961 1.4033 6.97016 1.45073 6.9402 1.49139C6.92103 1.51599 6.90004 1.53912 6.87741 1.56059C6.08049 2.35691 5.28376 3.15329 4.48723 3.94973C4.46465 3.96936 4.44437 3.99147 4.42675 4.01565C4.4484 4.02488 4.46879 4.03683 4.48742 4.05122C5.28717 4.85024 6.08661 5.64927 6.88572 6.44829C6.95508 6.51749 7.0001 6.59501 6.99836 6.69592C6.9976 6.7567 6.97884 6.81588 6.94444 6.86599C6.91005 6.91609 6.86158 6.95486 6.80515 6.97738C6.78757 6.98434 6.77037 6.99227 6.75279 6.99961H6.63571C6.54702 6.97371 6.48114 6.91688 6.41584 6.85231C5.62845 6.06424 4.8408 5.27604 4.05289 4.48772C4.0382 4.46942 4.02575 4.44943 4.0158 4.42818L3.98759 4.43282C3.97559 4.45322 3.96196 4.47262 3.94682 4.49081C3.16072 5.27708 2.37481 6.06366 1.58909 6.85057C1.5234 6.91649 1.45578 6.97564 1.36381 7H1.24653C1.18653 6.98245 1.1322 6.94939 1.08903 6.90415C1.04585 6.85891 1.01534 6.8031 1.00058 6.74231V6.63677C1.02879 6.54011 1.09409 6.46975 1.16365 6.40035C1.94576 5.61975 2.72722 4.83871 3.50804 4.05721C3.52747 4.04089 3.54836 4.02639 3.57045 4.01391V3.98549C3.54826 3.97322 3.5273 3.95885 3.50785 3.94258C2.72613 3.16121 1.94486 2.38023 1.16403 1.59964C1.09448 1.53024 1.02879 1.46046 1.00058 1.36341L1 1.25786Z"
                		fill="white"
                  		stroke="white"
                  		stroke-width="0.5"
                  		/>
                  		</svg>
            		</div>
            		<!-- -----------------------------Widget head icon ----------------------------->
            		<div class="th_whead_icon">
            			<svg width="16" height="13" viewBox="0 0 16 13" fill="none" xmlns="http://www.w3.org/2000/svg">
		        			<path d="M9.89459 0H0.508202C0.441464 0 0.37538 0.013146 0.313722 0.0386855C0.252064 0.0642251 0.196039 0.101657 0.148848 0.148848C0.101657 0.196039 0.064224 0.252065 0.0386845 0.313723C0.0131449 0.375381 0 0.441464 0 0.508202V2.5017C-6.61617e-08 2.56841 0.0131492 2.63446 0.0386975 2.69607C0.0642459 2.75769 0.101692 2.81367 0.148892 2.8608C0.196092 2.90794 0.252121 2.9453 0.313775 2.97076C0.375429 2.99623 0.441497 3.00928 0.508202 3.00919H9.89459C10.0292 3.00919 10.1583 2.95572 10.2534 2.86055C10.3486 2.76538 10.4021 2.6363 10.4021 2.5017V0.508202C10.4022 0.441497 10.3891 0.375429 10.3636 0.313775C10.3382 0.252121 10.3008 0.196092 10.2537 0.148892C10.2066 0.101692 10.1506 0.064247 10.089 0.0386986C10.0273 0.0131503 9.96129 -6.61617e-08 9.89459 0Z" fill="#845DE2"/>
							<path d="M15.2318 4.67675H3.88198C3.74732 4.67675 3.61817 4.7302 3.52288 4.82535C3.42759 4.9205 3.37397 5.04958 3.37378 5.18424V7.18131C3.37397 7.31597 3.42759 7.44505 3.52288 7.54021C3.61817 7.63536 3.74732 7.6888 3.88198 7.6888H15.2318C15.2985 7.6888 15.3645 7.67567 15.4261 7.65017C15.4876 7.62467 15.5436 7.58729 15.5907 7.54016C15.6378 7.49304 15.6752 7.43709 15.7007 7.37552C15.7262 7.31395 15.7393 7.24796 15.7393 7.18131V5.18424C15.7393 5.04965 15.6859 4.92057 15.5907 4.82539C15.4955 4.73022 15.3664 4.67675 15.2318 4.67675Z" fill="#6E55FF"/>
							<path d="M15.2318 9.35279H12.595C12.4604 9.35298 12.3313 9.4066 12.2361 9.50189C12.141 9.59718 12.0875 9.72634 12.0875 9.861V11.8574C12.0874 11.9241 12.1005 11.9901 12.126 12.0518C12.1514 12.1134 12.1888 12.1695 12.2359 12.2167C12.283 12.2639 12.339 12.3013 12.4006 12.3269C12.4623 12.3524 12.5283 12.3656 12.595 12.3656H15.2318C15.3666 12.3656 15.4959 12.312 15.5912 12.2167C15.6865 12.1214 15.74 11.9921 15.74 11.8574V9.86385C15.7404 9.79687 15.7275 9.73048 15.7021 9.66849C15.6768 9.60651 15.6394 9.55014 15.5922 9.50265C15.5449 9.45516 15.4888 9.41747 15.427 9.39175C15.3651 9.36603 15.2988 9.35279 15.2318 9.35279Z" fill="#6E55FF"/>
							<path d="M9.89463 9.35279H7.2557C7.12085 9.35355 6.99181 9.4078 6.89693 9.50362C6.80204 9.59944 6.74907 9.729 6.74964 9.86385V11.8602C6.74964 11.995 6.80318 12.1243 6.89849 12.2196C6.99379 12.3149 7.12306 12.3684 7.25784 12.3684H9.89463C9.96134 12.3684 10.0274 12.3553 10.089 12.3297C10.1506 12.3042 10.2066 12.2667 10.2537 12.2195C10.3009 12.1723 10.3382 12.1163 10.3637 12.0546C10.3892 11.993 10.4022 11.9269 10.4021 11.8602V9.86385C10.4025 9.79693 10.3897 9.7306 10.3643 9.66866C10.339 9.60672 10.3017 9.55039 10.2545 9.5029C10.2074 9.45542 10.1513 9.41771 10.0896 9.39195C10.0278 9.36619 9.96155 9.35288 9.89463 9.35279Z" fill="#6E55FF"/>
							<path d="M15.2318 0H12.595C12.5283 -6.61617e-08 12.4623 0.0131503 12.4006 0.0386986C12.339 0.064247 12.283 0.101692 12.2359 0.148892C12.1888 0.196092 12.1514 0.252121 12.126 0.313775C12.1005 0.375429 12.0874 0.441497 12.0875 0.508202V2.5017C12.0875 2.56835 12.1007 2.63434 12.1262 2.69591C12.1517 2.75748 12.189 2.81342 12.2362 2.86055C12.2833 2.90767 12.3392 2.94506 12.4008 2.97056C12.4624 2.99606 12.5284 3.00919 12.595 3.00919H15.2318C15.2985 3.00928 15.3646 2.99623 15.4262 2.97076C15.4879 2.9453 15.5439 2.90794 15.5911 2.8608C15.6383 2.81367 15.6758 2.75769 15.7013 2.69607C15.7269 2.63446 15.74 2.56841 15.74 2.5017V0.508202C15.74 0.373418 15.6865 0.244155 15.5912 0.148848C15.4959 0.0535418 15.3666 0 15.2318 0Z" fill="#6E55FF"/>
						</svg>
            		</div>
            		<!--------------------------Whidget heading section ---------------------------->
            		<div class="th_quick_widget_heading">
						<div class="th_whead_t1"><p>Welcome, we're</p><p><b style="font-size: 28px;">ThemeHigh</b></p></div>
                		</div>
        			</div>
        			<!-- --------------------Widget Body--------------------------------------- -->
        			<div class="th_quick_widget_body">
        				<ul>
        					<li>
        						<div class="list_icon" style="background-color: rgba(199, 0, 255, 0.15);">
        							<svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M6.91072 14.9998C6.70136 14.914 6.47074 14.8589 6.28742 14.736C5.91747 14.4906 5.75542 14.1145 5.74075 13.6743C5.73818 13.5961 5.74075 13.518 5.74075 13.4398C5.74075 13.1529 5.77045 12.8748 5.52773 12.6327C5.33157 12.4364 5.30041 12.1458 5.30041 11.8652C5.30041 11.233 5.08922 10.6945 4.64448 10.2289C2.62793 8.12188 3.48405 4.66072 6.25222 3.74646C7.04263 3.48069 7.89711 3.47384 8.69167 3.7269C9.48624 3.97997 10.1796 4.47977 10.671 5.15381C11.6654 6.51127 11.7123 8.25396 10.8005 9.66829C10.6527 9.88635 10.4864 10.0913 10.3037 10.281C9.88934 10.7304 9.69465 11.2602 9.69465 11.8652C9.69465 12.1513 9.62976 12.4155 9.46733 12.6525C9.45927 12.6646 9.4556 12.6822 9.4446 12.6892C9.22792 12.8429 9.25651 13.0696 9.25138 13.2938C9.24551 13.5459 9.25358 13.8074 9.18832 14.047C9.04459 14.5742 8.67501 14.8798 8.13641 14.9789C8.11799 14.9841 8.10017 14.9912 8.08325 15.0002L6.91072 14.9998ZM7.49002 12.363C7.76317 12.363 8.03632 12.363 8.30947 12.363C8.64238 12.363 8.79087 12.1913 8.81947 11.8575C8.84457 11.5134 8.89829 11.172 8.98006 10.8368C9.11536 10.3133 9.46074 9.913 9.81051 9.51236C10.5071 8.71403 10.74 7.77225 10.4705 6.75452C10.1368 5.49501 9.29721 4.72126 8.01139 4.47802C6.38788 4.1713 4.80874 5.24517 4.49159 6.85614C4.3211 7.72565 4.47179 8.53609 4.98693 9.26288C5.15522 9.50062 5.37081 9.70314 5.55046 9.93464C5.99484 10.5029 6.17376 11.16 6.17779 11.8732C6.17779 12.1888 6.35378 12.3608 6.66873 12.3627C6.94335 12.3645 7.21686 12.363 7.49002 12.363ZM6.6218 13.2487C6.6218 13.4174 6.61043 13.5789 6.62473 13.7363C6.6345 13.8397 6.68214 13.9358 6.75849 14.0062C6.83485 14.0766 6.93451 14.1162 7.03831 14.1175C7.34482 14.1255 7.65146 14.1255 7.95822 14.1175C8.06198 14.1153 8.16127 14.0748 8.23696 14.0037C8.31264 13.9327 8.35937 13.8361 8.36813 13.7326C8.3817 13.5745 8.3707 13.4145 8.3707 13.2487H6.6218Z" fill="#C700FF"/>
									<path d="M0 7.38305C0.11696 7.1299 0.317515 7.04992 0.589199 7.05653C1.13 7.07047 1.6708 7.05653 2.21161 7.0624C2.53719 7.0657 2.74764 7.36691 2.63288 7.65674C2.56322 7.83138 2.42793 7.93447 2.24094 7.93631C1.63671 7.94181 1.03101 7.94034 0.428609 7.93631C0.210455 7.93631 0.0923946 7.78955 0 7.61602V7.38305Z" fill="#C700FF"/>
									<path d="M7.93549 1.341C7.93549 1.62423 7.93732 1.90747 7.93549 2.1907C7.93292 2.46659 7.75217 2.66104 7.50358 2.66508C7.24693 2.66911 7.05921 2.47136 7.05811 2.18813C7.05615 1.61677 7.05615 1.04566 7.05811 0.474795C7.05811 0.1989 7.24143 0.004086 7.49002 5.02997e-05C7.74667 -0.00361851 7.93292 0.193764 7.93549 0.476996C7.93732 0.764998 7.93549 1.053 7.93549 1.341Z" fill="#C700FF"/>
									<path d="M13.6692 7.06071C13.9523 7.06071 14.235 7.05851 14.518 7.06071C14.7934 7.06328 14.9877 7.24415 14.991 7.49437C14.9939 7.75118 14.7967 7.93719 14.5143 7.93829C13.9436 7.94049 13.3727 7.94049 12.8017 7.93829C12.5268 7.93829 12.3321 7.75485 12.3291 7.50464C12.3262 7.25443 12.5231 7.06438 12.8058 7.06071C13.0885 7.05704 13.3814 7.06071 13.6692 7.06071Z" fill="#C700FF"/>
									<path d="M4.21244 3.71903C4.19521 3.95346 4.11602 4.09214 3.94919 4.16809C3.78237 4.24403 3.61921 4.22349 3.48575 4.10095C3.32333 3.9542 3.17117 3.7946 3.01571 3.63941C2.7499 3.37416 2.48261 3.10964 2.21973 2.84108C2.15821 2.78388 2.1153 2.7095 2.09653 2.6276C2.07777 2.5457 2.08403 2.46004 2.1145 2.38174C2.1739 2.21591 2.29782 2.10034 2.47125 2.10108C2.59224 2.10108 2.7444 2.1407 2.82799 2.22031C3.25697 2.62608 3.67127 3.0469 4.08155 3.47138C4.15451 3.54696 4.18421 3.66326 4.21244 3.71903Z" fill="#C700FF"/>
									<path d="M11.2831 4.21644C11.0558 4.20397 10.9164 4.13243 10.8365 3.97504C10.7566 3.81764 10.7632 3.64998 10.8791 3.50873C10.9714 3.39573 11.08 3.29594 11.183 3.19211C11.5008 2.87415 11.8185 2.55619 12.1363 2.23822C12.2672 2.10835 12.4234 2.05478 12.6041 2.11495C12.6816 2.13889 12.7505 2.18474 12.8025 2.24697C12.8546 2.30919 12.8875 2.38515 12.8974 2.46569C12.9094 2.53348 12.9044 2.60319 12.8828 2.66856C12.8613 2.73394 12.8238 2.79294 12.7739 2.84027C12.3614 3.25595 11.9497 3.67199 11.5291 4.0796C11.4536 4.15224 11.3377 4.18489 11.2831 4.21644Z" fill="#C700FF"/>
									<path d="M5.36065 6.98437C5.42475 6.66988 5.56528 6.37604 5.76982 6.1288C6.1966 5.60159 6.75463 5.3312 7.43109 5.30515C7.71488 5.29451 7.92093 5.46695 7.93303 5.72193C7.94513 5.97691 7.75264 6.16806 7.47142 6.18237C6.84813 6.21355 6.43382 6.52613 6.23033 7.11645C6.14343 7.36776 5.94508 7.50021 5.72216 7.45508C5.61453 7.43496 5.51812 7.3758 5.45141 7.28893C5.38469 7.20207 5.35237 7.09361 5.36065 6.98437Z" fill="#C700FF"/>
									</svg>

                    			</div>
                    			<a href="https://app.loopedin.io/extra-product-options-for-woocommerce/ideas" target="_blank" class="quick-widget-doc-link">Request a feature</a></li>
               			 	<li>
               			 		<div class="list_icon" style="background-color: rgba(255, 183, 67, 0.15);">
        							<svg width="15" height="12" viewBox="0 0 15 12" fill="none" xmlns="http://www.w3.org/2000/svg">
        								<path d="M11.137 4.21931C11.7996 3.86585 12.4434 3.51681 13.0919 3.18102C13.8532 2.7878 14.7977 3.17218 14.9716 3.95863C15.0139 4.14862 14.9998 4.36953 14.9481 4.55952C14.5299 6.20311 14.0975 7.84229 13.6652 9.48146C13.5712 9.83492 13.3973 9.96747 13.0026 9.96747C11.9124 9.96747 10.8221 9.96747 9.73192 9.96747C7.17083 9.96747 4.60974 9.96747 2.04395 9.96747C1.58343 9.96747 1.42365 9.8526 1.31557 9.43286C0.892638 7.80694 0.469705 6.18544 0.0420739 4.55952C-0.0942043 4.047 0.0326754 3.60076 0.488502 3.27822C0.94433 2.95127 1.43775 2.9336 1.93587 3.19428C2.52328 3.50355 3.11068 3.81725 3.69339 4.13095C3.74508 4.15746 3.79677 4.18397 3.85786 4.21931C3.89546 4.17071 3.93305 4.12211 3.96595 4.06909C4.76482 2.92034 5.56369 1.7716 6.36256 0.627265C6.94057 -0.207786 8.04019 -0.207786 8.6229 0.627265C9.42177 1.7716 10.2112 2.91151 11.0101 4.05584C11.0571 4.10444 11.09 4.15304 11.137 4.21931ZM12.6643 8.93802C12.6784 8.91151 12.6925 8.88942 12.6972 8.86291C13.0966 7.33861 13.4913 5.81872 13.8861 4.29C13.9002 4.22815 13.8767 4.12211 13.8297 4.08676C13.7827 4.05142 13.684 4.07351 13.6041 4.07793C13.5712 4.08235 13.543 4.10886 13.5101 4.12211C12.7911 4.5065 12.0721 4.88647 11.3532 5.27086C10.9208 5.50061 10.6765 5.44317 10.4086 5.05878C9.51576 3.77307 8.6182 2.48735 7.72534 1.20164C7.55617 0.958635 7.44339 0.958635 7.27422 1.19722C6.37196 2.49177 5.4744 3.79074 4.57215 5.08529C4.32779 5.43433 4.06463 5.49619 3.67929 5.29295C2.93681 4.89531 2.19433 4.49766 1.44245 4.10886C1.36726 4.06909 1.23098 4.05142 1.17459 4.08676C1.1229 4.12211 1.1041 4.25466 1.1229 4.32977C1.40956 5.44759 1.70091 6.56541 1.99226 7.68323C2.10034 8.09855 2.20843 8.51386 2.31651 8.9336C5.77986 8.93802 9.21971 8.93802 12.6643 8.93802Z" fill="#FFB743"/>
										<path d="M7.50435 12.0001C5.81732 12.0001 4.12559 12.0001 2.43856 12.0001C2.10491 12.0001 1.87935 11.8454 1.83235 11.5848C1.77596 11.2976 1.98743 11.0281 2.29758 10.9927C2.35397 10.9839 2.41506 10.9839 2.47145 10.9839C5.82671 10.9839 9.17728 10.9839 12.5325 10.9839C12.8991 10.9839 13.1246 11.1297 13.1763 11.3992C13.2327 11.6952 13.0119 11.9692 12.6923 11.9912C12.6218 11.9957 12.5513 11.9957 12.4809 11.9957C10.822 12.0001 9.16318 12.0001 7.50435 12.0001Z" fill="#FFB743"/>
									</svg>
                    			</div>
                    			<a href="https://www.themehigh.com/product/woocommerce-extra-product-options/?utm_source=free&utm_medium=quicklinks&utm_campaign=wepo_upgrade_link" target="_blank" class="quick-widget-doc-link">Upgrade to Premium</a></li>
               			 	<li>

               			 	<div class="list_icon" style="background-color: rgba(5, 15, 250, 0.15);">
               			 		<svg width="14" height="13" viewBox="0 0 14 13" fill="none" xmlns="http://www.w3.org/2000/svg">
               			 			<path
				                    d="M0 3.38284C0.0365282 3.2037 0.0639243 3.02455 0.109585 2.84791C0.474866 1.41869 1.66897 0.371758 3.16005 0.190821C4.2822 0.0543117 5.26626 0.369967 6.10166 1.11593C6.34019 1.33091 6.56776 1.5552 6.82858 1.80206C6.87606 1.74509 6.91698 1.68705 6.96738 1.63724C7.33267 1.27609 7.69283 0.909913 8.13081 0.626504C9.82352 -0.468078 11.8512 -0.070732 13.0939 1.36996C13.7076 2.08117 14.0261 2.90596 13.9983 3.84576C13.9735 4.68452 13.652 5.41507 13.1315 6.06716C13.0336 6.19006 12.927 6.3065 12.7827 6.4749C12.8287 6.50034 12.8923 6.51897 12.931 6.55946C13.3072 6.94104 13.438 7.39428 13.2802 7.90198C13.1249 8.40359 12.7724 8.73142 12.2468 8.82673C12.0393 8.86435 11.9816 8.93135 11.9458 9.12626C11.8362 9.7307 11.312 10.156 10.6812 10.195C10.6633 10.195 10.6447 10.1993 10.6147 10.2029C10.5442 10.812 10.2418 11.2645 9.62846 11.4491C9.01515 11.6336 8.50376 11.4197 8.08149 10.9374C7.92625 11.0922 7.77794 11.2405 7.62891 11.3882C7.50362 11.5121 7.37942 11.6368 7.25157 11.7579C7.08572 11.9155 6.88143 12.0286 6.65823 12.0866C6.43504 12.1445 6.20042 12.1453 5.97682 12.0889C5.75323 12.0324 5.54814 11.9206 5.3812 11.7642C5.21426 11.6078 5.09104 11.4119 5.02335 11.1954C4.99298 11.08 4.9686 10.9632 4.9503 10.8453C4.09554 10.6694 3.72295 10.3079 3.6309 9.56123C3.15238 9.52934 2.77066 9.32332 2.53505 8.90233C2.29945 8.48133 2.32757 8.05425 2.57158 7.62072C2.50948 7.56483 2.44264 7.50535 2.37725 7.4448C1.96595 7.06465 1.55318 6.68629 1.14479 6.30328C0.509202 5.70816 0.131502 4.98405 0.0277618 4.12558C0.0218314 4.10007 0.0132612 4.07522 0.00218937 4.05141L0 3.38284ZM9.90169 6.39249C10.0843 5.91668 10.4032 5.59064 10.9059 5.47992C11.4085 5.36921 11.8508 5.49856 12.2209 5.85828C12.2574 5.8246 12.2881 5.7988 12.3155 5.77014C13.0245 5.02669 13.2853 4.15209 13.0968 3.15461C12.8079 1.62542 11.2087 0.582434 9.65695 0.868709C8.6769 1.04786 8.0435 1.69099 7.40901 2.36207C7.85064 2.78306 8.29043 3.19796 8.72402 3.61788C8.9613 3.85006 9.11138 4.15423 9.14979 4.4808C9.1882 4.80738 9.11267 5.13708 8.93552 5.41615C8.58193 5.97652 7.86342 6.22481 7.2187 6.01306C6.99754 5.94105 6.79715 5.81821 6.63425 5.65477C6.48193 5.50393 6.32777 5.35452 6.17837 5.20834C6.16397 5.21522 6.15016 5.22325 6.1371 5.23234C5.19906 6.15148 4.26247 7.07193 3.32735 7.9937C3.25608 8.07398 3.21248 8.1743 3.20279 8.28033C3.1827 8.47166 3.28607 8.61319 3.46068 8.69882C3.54384 8.7429 3.6395 8.75882 3.73283 8.74412C3.82616 8.72941 3.91193 8.6849 3.97682 8.61749C4.24129 8.36668 4.49954 8.1062 4.76145 7.8511C4.95578 7.66192 5.20818 7.64974 5.3795 7.81885C5.55082 7.98797 5.53548 8.23483 5.34297 8.42544C5.10846 8.65654 4.8714 8.88585 4.63762 9.11766C4.40384 9.34948 4.39617 9.65761 4.61095 9.87007C4.81916 10.0768 5.1512 10.0671 5.37804 9.84535C5.61438 9.61569 5.84816 9.38351 6.0845 9.15385C6.277 8.96682 6.53343 8.95679 6.70256 9.12698C6.87168 9.29716 6.8567 9.53794 6.66931 9.72353C6.43188 9.95857 6.19152 10.1893 5.95482 10.4258C5.73565 10.6443 5.72835 10.9632 5.93546 11.17C6.14258 11.3767 6.46877 11.3738 6.69269 11.1574C6.94839 10.9095 7.20043 10.6558 7.45978 10.41C7.51786 10.3545 7.52444 10.3111 7.49156 10.237C7.3469 9.91676 7.33535 9.55381 7.45938 9.22538C7.58341 8.89695 7.83322 8.62897 8.15564 8.47847C8.18998 8.46235 8.22286 8.44264 8.25683 8.42544C7.93319 7.89732 7.92807 7.32692 8.25683 6.88909C8.67361 6.34018 9.23067 6.19221 9.90169 6.395V6.39249ZM3.1396 7.04673C3.17941 6.99944 3.20827 6.95824 3.24333 6.92384C4.0097 6.17119 4.77679 5.41878 5.54461 4.6666C5.9077 4.31189 6.42421 4.31153 6.7884 4.66194C6.93451 4.80275 7.07733 4.94858 7.22417 5.0876C7.37102 5.22661 7.54964 5.30257 7.75749 5.27498C8.02341 5.24094 8.21628 5.10157 8.30541 4.85076C8.39491 4.59387 8.32514 4.36492 8.12898 4.17753C7.27093 3.35704 6.41946 2.52939 5.54899 1.72216C4.85898 1.08153 4.02613 0.840046 3.0932 1.02206C2.03389 1.22915 1.30332 1.85294 0.968724 2.85651C0.628646 3.87191 0.844163 4.80705 1.5897 5.58741C2.07151 6.09224 2.60665 6.54799 3.1396 7.04673ZM10.6184 9.39319C10.7214 9.39015 10.8214 9.35814 10.9064 9.30098C10.9914 9.24381 11.0579 9.16389 11.098 9.07072C11.1941 8.86614 11.171 8.66048 11.0107 8.49746C10.5924 8.07467 10.1705 7.65691 9.73622 7.24845C9.51705 7.03993 9.19305 7.06931 8.97936 7.29037C8.88103 7.38892 8.82655 7.52168 8.82785 7.65957C8.82915 7.79745 8.88613 7.9292 8.9863 8.02595C9.39176 8.42974 9.80233 8.82852 10.2115 9.22909C10.2646 9.28212 10.3281 9.32401 10.3983 9.35222C10.4684 9.38043 10.5437 9.39437 10.6195 9.39319H10.6184ZM11.9381 8.08829C12.1938 8.05246 12.3662 7.94246 12.4641 7.73537C12.562 7.52828 12.5277 7.32154 12.3637 7.15422C12.1116 6.89649 11.8545 6.64366 11.5922 6.39572C11.4914 6.29957 11.356 6.24643 11.2154 6.24791C11.0748 6.24939 10.9406 6.30536 10.8419 6.4036C10.6228 6.61177 10.6071 6.93423 10.816 7.14849C11.0717 7.40969 11.3299 7.66765 11.6006 7.91237C11.6927 7.99406 11.8256 8.03096 11.9392 8.08829H11.9381ZM8.16624 9.7049C8.21628 9.81561 8.24368 9.94567 8.32112 10.0338C8.49452 10.229 8.68098 10.4126 8.87927 10.5834C9.09844 10.7726 9.41477 10.7443 9.61787 10.5372C9.71522 10.441 9.7707 10.3115 9.77261 10.1759C9.77452 10.0404 9.72271 9.90941 9.6281 9.8106C9.46031 9.63456 9.28656 9.46413 9.10684 9.29931C8.93771 9.14417 8.73316 9.11479 8.52458 9.20723C8.31601 9.29967 8.2035 9.46198 8.16733 9.7049H8.16624Z"
				                    fill="#0060FE"
				                  />
				              	</svg>
				          	</div><a href="https://www.facebook.com/groups/740534523911091" target="_blank" class="quick-widget-community-link">Join our Community</a></li>
				          	<li>
				          		<div class="list_icon" style="background-color: rgba(152, 190, 0, 0.15);">
				          			<svg width="15" height="13" viewBox="0 0 15 13" fill="none" xmlns="http://www.w3.org/2000/svg">
				          				<path d="M14.1991 6.08468C14.088 6.32262 13.8965 6.40304 13.6394 6.39229C13.3061 6.37858 12.975 6.39007 12.6424 6.38859C12.3746 6.38859 12.1838 6.21551 12.1798 5.97645C12.1757 5.7374 12.3698 5.55728 12.6461 5.55691C12.9831 5.55691 13.3205 5.56765 13.6569 5.5532C13.9121 5.54208 14.0935 5.62732 14.1991 5.86267V6.08468Z" fill="#98BE00"/>
				          				<path d="M6.16027 9.20862C6.12768 9.26199 6.10101 9.30424 6.07619 9.3476C5.4369 10.4553 4.79786 11.5631 4.15907 12.671C4.13833 12.7081 4.11796 12.7451 4.09611 12.7785C3.95943 12.9971 3.73609 13.062 3.51052 12.9367C2.90902 12.6032 2.30924 12.2668 1.71119 11.9275C1.46414 11.7867 1.41191 11.5569 1.56081 11.2975C2.00527 10.5251 2.45035 9.75319 2.89605 8.98179L2.97606 8.84133C2.94568 8.81909 2.91902 8.79611 2.88902 8.77758C2.6578 8.64004 2.46256 8.44949 2.31936 8.22164C2.27047 8.1427 2.21602 8.1212 2.12898 8.12157C1.78749 8.12157 1.44488 8.13084 1.10412 8.11008C0.812864 8.08923 0.539677 7.96105 0.33738 7.75034C0.135082 7.53963 0.0180427 7.26135 0.00888928 6.9693C-0.00296309 6.30415 -0.00296309 5.63851 0.00888928 4.97237C0.0203713 4.32748 0.554839 3.82677 1.22376 3.81565C1.52859 3.81046 1.83378 3.81195 2.13861 3.81565C2.17423 3.8191 2.21007 3.81214 2.24182 3.79563C2.27357 3.77912 2.29986 3.75377 2.31751 3.72262C2.65345 3.22784 3.12199 2.97581 3.72164 2.97544C4.40538 2.97544 5.08911 2.97804 5.77285 2.97285C5.85424 2.97098 5.93375 2.948 6.0036 2.90614C7.53255 1.9729 9.05978 1.0372 10.5853 0.0990233C10.7423 0.00266077 10.8983 -0.0410736 11.0668 0.0497295C11.2427 0.144239 11.289 0.30509 11.289 0.49448C11.2875 4.14192 11.2875 7.78925 11.289 11.4364C11.289 11.6247 11.2479 11.7859 11.0734 11.8845C10.899 11.9831 10.7401 11.9316 10.5797 11.8356C9.1525 10.9839 7.72441 10.1342 6.29546 9.28645C6.25583 9.26347 6.21546 9.24086 6.16027 9.20862ZM10.446 10.7856V1.17013C10.4053 1.19237 10.3771 1.20719 10.3505 1.22276C9.07089 2.00675 7.79244 2.79198 6.5151 3.57845C6.48989 3.59787 6.46914 3.62247 6.45425 3.65061C6.43936 3.67874 6.43068 3.70975 6.4288 3.74153C6.42436 5.25331 6.42621 6.76509 6.42324 8.27686C6.42013 8.31507 6.42879 8.35332 6.44806 8.38645C6.46733 8.41959 6.49629 8.44602 6.53103 8.46218C7.65947 9.1293 8.7868 9.79853 9.91303 10.4699C10.0853 10.5729 10.2586 10.6748 10.446 10.7856V10.7856ZM5.58099 8.21571V3.80935H3.76942C3.22051 3.80935 2.86457 4.15737 2.86309 4.70441C2.86087 5.54993 2.86087 6.39545 2.86309 7.24097C2.86309 7.75984 3.24051 8.18866 3.75646 8.21015C4.35982 8.23424 4.9654 8.21571 5.58099 8.21571ZM3.57349 12.0194C4.1424 11.0343 4.70835 10.0533 5.28653 9.05073H5.14467C4.74317 9.05073 4.34167 9.0537 3.94017 9.04851C3.90247 9.04443 3.86444 9.05238 3.83151 9.07122C3.79859 9.09005 3.77245 9.11881 3.75683 9.15339C3.35237 9.86277 2.94198 10.5692 2.53456 11.2767C2.5127 11.3138 2.49381 11.3553 2.47085 11.3998L3.57349 12.0194ZM2.02601 4.65141C1.73415 4.65141 1.45784 4.64177 1.18227 4.65437C1.09032 4.65622 1.00279 4.69426 0.938663 4.76023C0.874534 4.82621 0.838966 4.91481 0.839667 5.00684C0.834481 5.64926 0.834481 6.29167 0.839667 6.93409C0.839667 7.10124 0.960042 7.26209 1.11486 7.27099C1.41636 7.28878 1.71933 7.27618 2.02601 7.27618V4.65141Z" fill="#98BE00"/>
				          				<path d="M13.0036 2.6377C13.2259 2.6477 13.3566 2.72146 13.43 2.87971C13.5033 3.03797 13.4892 3.19549 13.3718 3.31853C13.0824 3.62195 12.7861 3.91845 12.4829 4.20803C12.3229 4.36073 12.0729 4.33849 11.9206 4.18246C11.8463 4.10788 11.8038 4.00742 11.8018 3.90213C11.7999 3.79684 11.8388 3.69489 11.9103 3.61763C12.1947 3.32113 12.4847 3.03167 12.7821 2.74888C12.8492 2.68328 12.9559 2.65919 13.0036 2.6377Z" fill="#98BE00"/>
				          				<path d="M12.2749 7.63037C12.3249 7.65631 12.4331 7.68596 12.5045 7.75379C12.792 8.02657 13.0712 8.30973 13.3475 8.59288C13.3869 8.63063 13.4182 8.67589 13.4398 8.72599C13.4613 8.77608 13.4726 8.82999 13.4729 8.88452C13.4733 8.93906 13.4627 8.9931 13.4418 9.04347C13.4209 9.09383 13.3901 9.13949 13.3512 9.17773C13.1872 9.34414 12.9327 9.34599 12.7564 9.17328C12.4764 8.89902 12.1995 8.62179 11.9256 8.3416C11.8643 8.28409 11.823 8.20852 11.8075 8.12588C11.7921 8.04324 11.8033 7.95782 11.8397 7.88202C11.9093 7.71561 12.0434 7.64149 12.2749 7.63037Z" fill="#98BE00"/>
				          			</svg>

		              			</div><a href="https://wordpress.org/support/plugin/woo-extra-product-options/" target="_blank" class="quick-widget-support-link">Get support</a></li>
		              		<li>
		              			<div class="list_icon" style="background-color: rgba(255, 0, 0, 0.15);">
		              				<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
		              					<path 
		              					d="M13.9983 7.40916C13.9622 7.67875 13.9348 7.95008 13.8878 8.21793C13.7211 9.17181 13.3549 10.0797 12.8131 10.882C12.7742 10.9427 12.7235 10.9949 12.664 11.0356C12.6046 11.0763 12.5376 11.1047 12.467 11.1191C12.3964 11.1334 12.3236 11.1335 12.253 11.1193C12.1824 11.1051 12.1153 11.0768 12.0558 11.0362C11.9954 10.9969 11.9435 10.9459 11.9032 10.8861C11.8629 10.8264 11.8349 10.7592 11.821 10.6884C11.8071 10.6177 11.8075 10.5449 11.8222 10.4743C11.8368 10.4037 11.8655 10.3368 11.9065 10.2775C12.3466 9.60203 12.6721 8.87731 12.7992 8.07987C13.1801 5.68448 12.404 3.72461 10.4885 2.24601C9.3895 1.3976 8.11206 1.04395 6.72667 1.09883C4.02075 1.20597 1.69089 3.23857 1.20167 5.91182C0.661531 8.86164 2.36682 11.7287 5.22464 12.6272C6.88249 13.1498 8.47853 12.9538 9.9897 12.0924C10.1325 12.0109 10.2757 11.9513 10.4428 11.9861C10.5509 12.0075 10.6501 12.0608 10.7277 12.1391C10.8053 12.2174 10.8576 12.3172 10.878 12.4255C10.898 12.5363 10.8832 12.6505 10.8357 12.7525C10.7881 12.8546 10.7102 12.9393 10.6125 12.9952C10.0244 13.3524 9.38561 13.6186 8.71792 13.7848C6.95193 14.2334 5.08069 13.9747 3.50246 13.0637C1.92422 12.1526 0.763611 10.6613 0.267631 8.90693C-0.831799 5.07692 1.56597 1.03089 5.44705 0.17421C5.77479 0.101913 6.11211 0.0731673 6.44463 0.0235174C6.48468 0.0178556 6.52428 0.00827498 6.56389 0H7.4383C7.47355 0.00783946 7.50837 0.0174192 7.54363 0.0226455C7.90271 0.0783928 8.26701 0.109752 8.62042 0.192502C11.3155 0.80877 13.436 3.06697 13.8921 5.79379C13.9356 6.04858 13.9631 6.30641 13.9979 6.56293L13.9983 7.40916Z" fill="#FF0000"/>
		              					<path d="M5.84092 4.22803C6.02808 4.29118 6.23046 4.32689 6.39934 4.42227C7.31771 4.94142 8.23172 5.46971 9.14138 6.00715C9.31609 6.10769 9.46115 6.25263 9.56188 6.4273C9.6626 6.60198 9.71542 6.80018 9.71498 7.00185C9.71453 7.20351 9.66085 7.40148 9.55936 7.57571C9.45787 7.74994 9.31218 7.89425 9.13703 7.99402C8.23259 8.52826 7.32366 9.05496 6.41022 9.5741C5.62156 10.0218 4.68535 9.47306 4.6823 8.5676C4.67882 7.51537 4.67882 6.46329 4.6823 5.41135C4.68236 5.26059 4.71211 5.11132 4.76986 4.97208C4.82761 4.83283 4.91221 4.70634 5.01885 4.59984C5.12548 4.49334 5.25206 4.40891 5.39132 4.35139C5.53059 4.29387 5.67981 4.26438 5.83048 4.26461L5.84092 4.22803ZM5.77259 6.99884C5.77259 7.4997 5.77259 8.00055 5.77259 8.5014C5.77259 8.65166 5.7974 8.66603 5.92754 8.59069C6.78961 8.09216 7.6514 7.59319 8.51289 7.09379C8.65043 7.01408 8.65043 6.9836 8.51289 6.9039C7.65169 6.40478 6.7899 5.90596 5.92754 5.40743C5.79696 5.33208 5.77259 5.34602 5.77259 5.49454C5.77085 5.99655 5.77085 6.49799 5.77259 6.99884V6.99884Z" fill="#FF0000"/>
		              				</svg>

			            		</div>
			            	<a href="https://www.youtube.com/watch?v=YoVPQhdwuis" target="_blank" class="quick-widget-youtube-link" >Video Tutorial</a></li>
			       	 	</ul>
			   		</div>
				</div>
			<div id="myWidget" class="widget-popup" onclick="thwepofwidgetPopUp()">
				<span id="th_quick_border_animation"></span>
				<div class="widget-popup-icon" id="th_arrow_head">
					<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path
						d="M10.1279 5.1957L0 15.3236L2.92808 18.2517L13.056 8.12378L10.1279 5.1957Z"
						fill="white"
						/>
			            <path
			            d="M19.8695 4.54623L7.83801 16.5777L10.7717 19.5113L22.8031 7.47991L19.8695 4.54623Z"
			            fill="white"
			            />
              			<path
                		d="M24.4214 9.0978L20.8551 12.6641L23.7888 15.5978L27.3439 12.0427L24.4214 9.0978Z"
                		fill="white"
              			/>
              			<path
		                d="M19.226 14.2932L15.6709 17.8483L18.6046 20.782L22.1597 17.2268L19.226 14.2932Z"
		                fill="white"
              			/>
              			<path
		                d="M15.3236 -7.92623e-06L11.7573 3.56631L14.6854 6.49439L18.2405 2.93927L15.3236 -7.92623e-06Z"
		                fill="white"
             			/>
             		</svg>
             	</div>
            </div>
            </div>
        <?php
	}
}
endif;
