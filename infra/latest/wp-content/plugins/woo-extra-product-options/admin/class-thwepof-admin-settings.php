<?php
/**
 * Woo Extra Product Options Setting Page
 *
 * @author   ThemeHiGH
 * @category Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('THWEPOF_Admin_Settings')) :
abstract class THWEPOF_Admin_Settings{
	protected $page_id = '';
	protected $section_id = '';
	protected $tabs = '';

	protected $cell_props_L = array();
	//protected $cell_props_R = array();
	//protected $cell_props_CB = array();
	//protected $cell_props_CBS = array();
	//protected $cell_props_CBL = array();
	//protected $cell_props_CP = array();

	public function __construct($page, $section = '') {
		$this->page_id = $page;
		if($section){
			$this->section_id = $section;
		}else{
			$this->set_first_section_as_current();
		}
		$this->tabs = array( 
			'general_settings' => __('Product Options', 'woo-extra-product-options'),
			'advanced_settings' => __('Advanced Settings', 'woo-extra-product-options'),
			'pro' => __('Premium Features', 'woo-extra-product-options'),
		);

		$this->init_constants();

		add_action( 'admin_init', array( $this, 'wepo_notice_actions' ), 20 );
		add_action( 'admin_notices', array($this, 'output_review_request_link'));
		add_action( 'admin_head', array($this,'review_banner_custom_css'));
	}

	public function get_tabs(){
		return $this->tabs;
	}
	
	public function get_current_tab(){
		return $this->page_id;
	}

	public function get_current_section(){
		return isset( $_GET['section'] ) ? sanitize_key( $_GET['section'] ) : $this->section_id;
	}
	
	public function set_current_section($section_id){
		if($section_id){
			$this->section_id = $section_id;
		}
	}

	public function set_first_section_as_current(){
		$sections = THWEPOF_Utils::get_sections_admin();
		if($sections && is_array($sections)){
			$array_keys = array_keys( $sections );
			if($array_keys && is_array($array_keys) && isset($array_keys[0])){
				$this->set_current_section($array_keys[0]);
			}
		}
	}
		
	public function render_tabs(){
		$current_tab = $this->get_current_tab();
		$tabs = $this->get_tabs();

		if(empty($tabs)){
			return;
		}
		
		echo '<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">';
		foreach( $tabs as $id => $label ){
			$active = ($current_tab == $id) ? 'nav-tab-active' : '';
			$label  = __($label, 'woo-extra-product-options');
			$url    = $this->get_admin_url($id);

			echo '<a class="nav-tab '.$active.'" href="'. esc_url($url) .'">'.$label.'</a>';
		}
		echo '</h2>';	
	}

	public function wepo_notice_actions(){

		if( !(isset($_GET['thwepo_remind']) || isset($_GET['thwepo_dissmis']) || isset($_GET['thwepo_reviewed'])) ) {
			return;
		}

		$nonse = isset($_GET['thwepo_review_nonce']) ? $_GET['thwepo_review_nonce'] : false;
		$capability = THWEPOF_Utils::wepo_capability();

		if(!wp_verify_nonce($nonse, 'thwepof_notice_security') || !current_user_can($capability)){
			die();
		}

		$now = time();

		$thwepo_remind = isset($_GET['thwepo_remind']) ? sanitize_text_field( wp_unslash($_GET['thwepo_remind'])) : false;
		if($thwepo_remind){
			update_user_meta( get_current_user_id(), 'thwepo_review_skipped', true );
			update_user_meta( get_current_user_id(), 'thwepo_review_skipped_time', $now );
		}

		$thwepo_dissmis = isset($_GET['thwepo_dissmis']) ? sanitize_text_field( wp_unslash($_GET['thwepo_dissmis'])) : false;
		if($thwepo_dissmis){
			update_user_meta( get_current_user_id(), 'thwepo_review_dismissed', true );
			update_user_meta( get_current_user_id(), 'thwepo_review_dismissed_time', $now );
		}

		$thwepo_reviewed = isset($_GET['thwepo_reviewed']) ? sanitize_text_field( wp_unslash($_GET['thwepo_reviewed'])) : false;
		if($thwepo_reviewed){
			update_user_meta( get_current_user_id(), 'thwepo_reviewed', true );
			update_user_meta( get_current_user_id(), 'thwepo_reviewed_time', $now );
		}

		$arr_params = array('thwepo_remind', 'thwepo_dissmis', 'thwepo_reviewed', 'thwepo_review_nonce');
		$redirect_url = remove_query_arg($arr_params, false);

		wp_safe_redirect($redirect_url);
		exit;
	}

	public function output_review_request_link(){

		$current_screen = get_current_screen();
		if($current_screen->id !== 'product_page_thwepof_extra_product_options'){
			return;
		}

		if(!apply_filters('thwepof_show_dismissable_admin_notice', true)){
			return;
		}

		$capability = THWEPOF_Utils::wepo_capability();
		if (!current_user_can($capability)){
            return;
        }

		$thwepo_reviewed = get_user_meta( get_current_user_id(), 'thwepo_reviewed', true );
		if($thwepo_reviewed){
			return;
		}

		$now = time();
		$dismiss_life  = apply_filters('thwepof_dismissed_review_request_notice_lifespan', 6 * MONTH_IN_SECONDS);
		$reminder_life = apply_filters('thwepof_skip_review_request_notice_lifespan', 7 * DAY_IN_SECONDS);

		$is_dismissed   = get_user_meta( get_current_user_id(), 'thwepo_review_dismissed', true );
		$dismisal_time  = get_user_meta( get_current_user_id(), 'thwepo_review_dismissed_time', true );
		$dismisal_time  = $dismisal_time ? $dismisal_time : 0;
		$dismissed_time = $now - $dismisal_time;

		if( $is_dismissed && ($dismissed_time < $dismiss_life) ){
			return;
		}

		$is_skipped = get_user_meta( get_current_user_id(), 'thwepo_review_skipped', true );
		$skipping_time = get_user_meta( get_current_user_id(), 'thwepo_review_skipped_time', true );
		$skipping_time = $skipping_time ? $skipping_time : 0;
		$remind_time = $now - $skipping_time;

		if($is_skipped && ($remind_time < $reminder_life) ){
			return;
		}

		$thwepof_since = get_option('thwepof_since');
		if(!$thwepof_since){
			$now = time();
			update_option('thwepof_since', $now, 'no' );
		}

		$thwepof_since = $thwepof_since ? $thwepof_since : $now;
		$render_time = apply_filters('thwepof_show_review_banner_render_time' , 7 * DAY_IN_SECONDS);

		$render_time = $thwepof_since + $render_time;
		if($now > $render_time ){
			$this->render_review_request_notice();
		}
	}

	private function render_review_request_notice(){
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general_settings';
		$current_section = isset( $_GET['section'] ) ? sanitize_key( $_GET['section'] ) : '';

		$remind_url = add_query_arg(array('thwepo_remind' => true, 'thwepo_review_nonce' => wp_create_nonce( 'thwepof_notice_security')));
		$dismiss_url = add_query_arg(array('thwepo_dissmis' => true, 'thwepo_review_nonce' => wp_create_nonce( 'thwepof_notice_security')));
		$reviewed_url= add_query_arg(array('thwepo_reviewed' => true, 'thwepo_review_nonce' => wp_create_nonce( 'thwepof_notice_security')));
		?>

		<div class="notice notice-info thpladmin-notice is-dismissible thwepo-review-wrapper" data-nonce="<?php echo wp_create_nonce( 'thwepof_notice_security'); ?>">
			<div class="thwepo-review-image">
				<img src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/review-left.png'); ?>" alt="themehigh">
			</div>
			<div class="thwepo-review-content">
				<h3><?php _e('We would love to hear from you?', 'woo-extra-product-options'); ?></h3>
				
				<p><?php _e('We have a quick favor to ask. You have been our patron for the longest and would love to hear about your experience with us so far. Would you mind heading to WordPress and writing a quick review on our plugin? Review or not, we still love you!', 'woo-extra-product-options'); ?></p>
				
				<div class="action-row">
			        <a class="thwepo-notice-action thwepo-yes" onclick="window.open('https://wordpress.org/support/plugin/woo-extra-product-options/reviews/?rate=5#new-post', '_blank')" style="margin-right:16px; text-decoration: none">
			        	<?php _e("Yes, today", 'woo-extra-product-options'); ?>
			        </a>

			        <a class="thwepo-notice-action thwepo-done" href="<?php echo esc_url($reviewed_url); ?>" style="margin-right:16px; text-decoration: none">
			        	<?php _e('Already, Did', 'woo-extra-product-options'); ?>
			        </a>

			        <a class="thwepo-notice-action thwepo-remind" href="<?php echo esc_url($remind_url); ?>" style="margin-right:16px; text-decoration: none">
			        	<?php _e('Maybe later', 'woo-extra-product-options'); ?>
			        </a>

			        <a class="thwepo-notice-action thwepo-dismiss" href="<?php echo esc_url($dismiss_url); ?>" style="margin-right:16px; text-decoration: none">
			        	<?php _e("Nah, Never", 'woo-extra-product-options'); ?>
			        </a>
				</div>
			</div>
			<div class="thwepo-themehigh-logo">
				<span class="logo" style="float: right">
            		<a target="_blank" href="https://www.themehigh.com">
                		<img src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/logo.svg'); ?>" style="height:19px;margin-top:4px;" alt="themehigh"/>
                	</a>
                </span>
			</div>
	    </div>

		<?php
	}

	public function review_banner_custom_css(){
		?>
		<style type="text/css">

			/* Review Banner CSS */
			.thwepo-review-wrapper {
			    padding: 15px 28px 5px 10px !important;
			    margin-top: 35px;
			}
			.thwepo-review-image {
			    float: left;
			}
			.thwepo-review-content {
			    padding-right: 180px;
			}
			.thwepo-review-content p {
			    padding-bottom: 6px;
			}
			.thwepo-notice-action{
			    padding: 8px 18px 8px 18px;
			    background: #fff;
			    color: #007cba;
			    border-radius: 5px;
			    border: 1px solid  #007cba;
			    display: inline-block;
			    cursor: pointer;
			}
			.thwepo-notice-action.thwepo-yes {
			    background-color: #007cba;
			    color: #fff;
			}
			.thwepo-notice-action:hover:not(.thwepo-yes) {
			    background-color: #f2f5f6;
			}
			.thwepo-notice-action.thwepo-yes:hover {
			    opacity: .9;
			}
			.thwepo-notice-action .dashicons{
			    display: none;
			}
			.thwepo-themehigh-logo {
			    position: absolute;
			    right: 20px;
			    top: calc(50% - 13px);
			}
			.thwepo-notice-action {
			    background-repeat: no-repeat;
			    padding-left: 40px;
			    background-position: 16px 10px;
			    margin-bottom: 10px;
			}
			.thwepo-review-content h3 {
			    margin-top: 5px;
			    margin-bottom: 10px;
			}
			.thwepo-yes{
			    background-image: url(<?php echo THWEPOF_URL; ?>admin/assets/css/tick.svg);
			}
			.thwepo-remind{
			    background-image: url(<?php echo THWEPOF_URL; ?>admin/assets/css/reminder.svg);
			}
			.thwepo-dismiss{
			    background-image: url(<?php echo THWEPOF_URL; ?>admin/assets/css/close.svg);
			}
			.thwepo-done{
			    background-image: url(<?php echo THWEPOF_URL; ?>admin/assets/css/done.svg);
			}
			@media(min-width: 2000px){
				.thwepo-review-image img{
					max-width: 80%;
				}
			}
			@media(max-width: 1180px){
				.thwepo-notice-action {
				    margin-right: 7px !important;
				    background-image: none !important;
				    padding: 5px 8px 5px 10px;
				}
			}
			@media(max-width: 768px){
				.thwepo-review-image {
				    display: none;
				}
				.thwepo-review-content {
				    padding-right: 0px;
				}
				.thwepo-themehigh-logo {
				    position: relative;
				    text-align: center;
				    right: 0px;
				}
				.thwepo-themehigh-logo .logo {
				    float: none !important;
				}
				.thwepo-review-content .action-row {
				    text-align: center;
				}
			}
			@media(max-width: 480px){
				.thwepo-notice-action {
				    margin-right: 5px !important;
				    padding: 2px 5px;
				    font-size: 13px;
				}
			}
			@media(max-width: 425px){
				.thwepo-notice-action {
				    padding: 0px 4px;
				    font-size: 11px;
				}
			}
		</style>
		<?php 
	}

	public function get_admin_url($tab = false, $section = false){
		$url = 'edit.php?post_type=product&page=thwepof_extra_product_options';
		if($tab && !empty($tab)){
			$url .= '&tab='. $tab;
		}
		if($section && !empty($section)){
			$url .= '&section='. $section;
		}
		return admin_url($url);
	}

	public function print_notices($msg, $type='updated', $return=false){
		$notice = '<div class="thwepof-notice '. $type .'"><p>'. __($msg, 'woo-extra-product-options') .'</p></div>';
		if(!$return){
			echo $notice;
		}
		return $notice;
	}

   /*--------------------------------------------
	*------ SECTION FORM FRAGMENTS - START ------
	*--------------------------------------------*/
	public function init_constants(){
		$this->cell_props_L = array( 
			'label_cell_props' => 'width="13%"', 
			'input_cell_props' => 'width="34%"', 
			'input_width' => '250px',  
		);
		/*
		$this->cell_props_R = array( 
			'label_cell_props' => 'width="14%"', 
			'input_cell_props' => 'width="33%"', 
			'input_width' => '250px', 
		);
		
		$this->cell_props_CB = array( 
			'label_props' => 'style="margin-right: 40px;"', 
		);
		$this->cell_props_CBS = array( 
			'label_props' => 'style="margin-right: 15px;"', 
		);
		$this->cell_props_CBL = array( 
			'label_props' => 'style="margin-right: 52px;"', 
		);
		
		$this->cell_props_CP = array(
			'label_cell_props' => 'width="13%"', 
			'input_cell_props' => 'width="34%"', 
			'input_width' => '218px',
		);
		*/
	} 

	public function render_form_field_element($field, $atts = array(), $render_cell = true){
		if($field && is_array($field)){
			$args = shortcode_atts( array(
				'label_cell_props' => '',
				'input_cell_props' => '',
				'label_cell_colspan' => '',
				'input_cell_colspan' => '',
			), $atts );
		
			$ftype     = isset($field['type']) ? $field['type'] : 'text';
			$flabel    = isset($field['label']) && !empty($field['label']) ? __($field['label'], 'woo-extra-product-options') : '';
			$sub_label = isset($field['sub_label']) && !empty($field['sub_label']) ? __($field['sub_label'], 'woo-extra-product-options') : '';
			$tooltip   = isset($field['hint_text']) && !empty($field['hint_text']) ? __($field['hint_text'], 'woo-extra-product-options') : '';
			
			$field_html = '';
			
			if($ftype == 'text'){
				$field_html = $this->render_form_field_element_inputtext($field, $atts);
				
			}else if($ftype == 'textarea'){
				$field_html = $this->render_form_field_element_textarea($field, $atts);
				   
			}else if($ftype == 'select'){
				$field_html = $this->render_form_field_element_select($field, $atts);     
				
			}else if($ftype == 'multiselect'){
				$field_html = $this->render_form_field_element_multiselect($field, $atts);     
				
			}else if($ftype == 'colorpicker'){
				$field_html = $this->render_form_field_element_colorpicker($field, $atts);              
            
			}else if($ftype == 'checkbox'){
				$field_html = $this->render_form_field_element_checkbox($field, $atts, $render_cell);   
				$flabel 	= '&nbsp;';  
			}
			
			if($render_cell){
				$required_html = isset($field['required']) && $field['required'] ? '<abbr class="required" title="required">*</abbr>' : '';
				
				$label_cell_props = !empty($args['label_cell_props']) ? $args['label_cell_props'] : '';
				$input_cell_props = !empty($args['input_cell_props']) ? $args['input_cell_props'] : '';
				
				?>
				<td <?php echo $label_cell_props ?> >
					<?php echo $flabel; echo $required_html; 
					if($sub_label){
						?>
						<br/><span class="thpladmin-subtitle"><?php echo $sub_label; ?></span>
						<?php
					}
					?>
				</td>
				<?php $this->render_form_fragment_tooltip($tooltip); ?>
				<td <?php echo $input_cell_props ?> ><?php echo $field_html; ?></td>
				<?php
			}else{
				echo $field_html;
			}
		}
	}

	private function prepare_form_field_props($field, $atts = array()){
		$field_props = '';
		$args = shortcode_atts( array(
			'input_width' => '',
			'input_name_prefix' => 'i_',
			'input_name_suffix' => '',
		), $atts );
		
		$ftype = isset($field['type']) ? $field['type'] : 'text';
		
		if($ftype == 'multiselect'){
			$args['input_name_suffix'] = $args['input_name_suffix'].'[]';
		}
		
		$fname  = $args['input_name_prefix'].$field['name'].$args['input_name_suffix'];
		$fvalue = isset($field['value']) ? esc_html($field['value']) : '';
		
		$input_width  = $args['input_width'] ? 'width:'.$args['input_width'].';' : '';
		$field_props  = 'name="'. $fname .'" value="'. $fvalue .'" style="'. $input_width .'"';
		$field_props .= ( isset($field['placeholder']) && !empty($field['placeholder']) ) ? ' placeholder="'.$field['placeholder'].'"' : '';
		$field_props .= ( isset($field['onchange']) && !empty($field['onchange']) ) ? ' onchange="'.$field['onchange'].'"' : '';
		
		return $field_props;
	}
	
	private function render_form_field_element_inputtext($field, $atts = array()){
		$field_html = '';
		if($field && is_array($field)){
			$field_props = $this->prepare_form_field_props($field, $atts);
			$field_html = '<input type="text" '. $field_props .' />';
		}
		return $field_html;
	}
	
	private function render_form_field_element_textarea($field, $atts = array()){
		$field_html = '';
		if($field && is_array($field)){
			$args = shortcode_atts( array(
				'rows' => '5',
				'cols' => '100',
			), $atts );
		
			$fvalue = isset($field['value']) ? esc_textarea($field['value']) : '';
			$field_props = $this->prepare_form_field_props($field, $atts);
			$field_html = '<textarea '. $field_props .' rows="'.$args['rows'].'" cols="'.$args['cols'].'" >'.$fvalue.'</textarea>';
		}
		return $field_html;
	}
	
	private function render_form_field_element_select($field, $atts = array()){
		$field_html = '';
		if($field && is_array($field)){
			$fvalue = isset($field['value']) ? $field['value'] : '';
			$field_props = $this->prepare_form_field_props($field, $atts);
			
			$field_html = '<select '. $field_props .' >';
			foreach($field['options'] as $value => $label){
				$selected = $value === $fvalue ? 'selected' : '';
				$field_html .= '<option value="'. trim($value) .'" '.$selected.'>'. __($label, 'woo-extra-product-options') .'</option>';
			}
			$field_html .= '</select>';
		}
		return $field_html;
	}
	
	private function render_form_field_element_multiselect($field, $atts = array()){
		$field_html = '';
		if($field && is_array($field)){
			$field_props = $this->prepare_form_field_props($field, $atts);
			
			$field_html = '<select multiple="multiple" '. $field_props .' class="thwepo-enhanced-multi-select" >';
			foreach($field['options'] as $value => $label){
				//$selected = $value === $fvalue ? 'selected' : '';
				$field_html .= '<option value="'. trim($value) .'" >'. __($label, 'woo-extra-product-options') .'</option>';
			}
			$field_html .= '</select>';
		}
		return $field_html;
	}
	
	private function render_form_field_element_radio($field, $atts = array()){
		$field_html = '';
		/*if($field && is_array($field)){
			$field_props = $this->prepare_form_field_props($field, $atts);
			
			$field_html = '<select '. $field_props .' >';
			foreach($field['options'] as $value => $label){
				$selected = $value === $fvalue ? 'selected' : '';
				$field_html .= '<option value="'. trim($value) .'" '.$selected.'>'. THWEPO_i18n::__t($label) .'</option>';
			}
			$field_html .= '</select>';
		}*/
		return $field_html;
	}
	
	private function render_form_field_element_checkbox($field, $atts = array(), $render_cell = true){
		$field_html = '';
		if($field && is_array($field)){
			$args = shortcode_atts( array(
				'label_props' => '',
				'cell_props'  => 3,
				'render_input_cell' => false,
			), $atts );
		
			$fid 	= 'a_f'. $field['name'];
			$flabel = isset($field['label']) && !empty($field['label']) ? __($field['label'], 'woo-extra-product-options') : '';
			
			$field_props  = $this->prepare_form_field_props($field, $atts);
			$field_props .= isset($field['checked']) && $field['checked'] === 1 ? ' checked' : '';
			
			$field_html  = '<input type="checkbox" id="'. $fid .'" '. $field_props .' />';
			$field_html .= '<label for="'. $fid .'" '. $args['label_props'] .' > '. $flabel .'</label>';
		}
		if(!$render_cell && $args['render_input_cell']){
			return '<td '. $args['cell_props'] .' >'. $field_html .'</td>';
		}else{
			return $field_html;
		}
	}
	
	private function render_form_field_element_colorpicker($field, $atts = array()){
		$field_html = '';
		if($field && is_array($field)){
			$field_props = $this->prepare_form_field_props($field, $atts);
			
			$field_html  = '<span class="thpladmin-colorpickpreview '.$field['name'].'_preview" style=""></span>';
            $field_html .= '<input type="text" '. $field_props .' class="thpladmin-colorpick"/>';
		}
		return $field_html;
	}
	
	public function render_form_fragment_tooltip($tooltip = false){
		$tooltip_html = '';

		if($tooltip){
			$tooltip_html = '<a href="javascript:void(0)" title="'. $tooltip .'" class="thwepof_tooltip"><img src="'. THWEPOF_URL.'admin/assets/help.png" title=""/></a>';
		}
		?>
		<td style="width: 26px; padding:0px;"><?php echo $tooltip_html; ?></td>
		<?php
	}
	
	public function render_form_fragment_h_separator($atts = array()){
		$args = shortcode_atts( array(
			'colspan' 	   => 6,
			'padding-top'  => '5px',
			'border-style' => 'dashed',
    		'border-width' => '1px',
			'border-color' => '#e6e6e6',
			'content'	   => '',
		), $atts );
		
		$style  = $args['padding-top'] ? 'padding-top:'.$args['padding-top'].';' : '';
		$style .= $args['border-style'] ? ' border-bottom:'.$args['border-width'].' '.$args['border-style'].' '.$args['border-color'].';' : '';
		
		?>
        <tr><td colspan="<?php echo $args['colspan']; ?>" style="<?php echo $style; ?>"><?php echo $args['content']; ?></td></tr>
        <?php
	}
	
	public function render_field_form_fragment_h_spacing($padding = 5){
		$style = $padding ? 'padding-top:'.$padding.'px;' : '';
		?>
        <tr><td colspan="6" style="<?php echo $style ?>"></td></tr>
        <?php
	}
	
	public function render_form_field_blank($colspan = 3){
		?>
        <td colspan="<?php echo $colspan; ?>">&nbsp;</td>  
        <?php
	}
	
	public function render_form_section_separator($props, $atts=array()){
		?>
		<tr valign="top"><td colspan="<?php echo $props['colspan']; ?>" style="height:10px;"></td></tr>
		<tr valign="top"><td colspan="<?php echo $props['colspan']; ?>" class="thpladmin-form-section-title" ><?php echo $props['title']; ?></td></tr>
		<tr valign="top"><td colspan="<?php echo $props['colspan']; ?>" style="height:0px;"></td></tr>
		<?php
	}

}
endif;