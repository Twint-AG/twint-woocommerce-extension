<?php
/**
 * Woo Extra Product Options - Field Editor
 *
 * @author    ThemeHiGH
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('THWEPOF_Admin_Settings_General')):
class THWEPOF_Admin_Settings_General extends THWEPOF_Admin_Settings {
	protected static $_instance = null;

	private $section_form = null;
	private $field_form = null;

	private $field_props = array();

	public function __construct() {
		parent::__construct('general_settings');
		$this->page_id = 'general_settings';

		$this->section_form = new THWEPOF_Admin_Form_Section();
		$this->field_form = new THWEPOF_Admin_Form_Field();
		$this->field_props = $this->field_form->get_field_form_props();

		add_filter( 'woocommerce_attribute_label', array($this, 'woo_attribute_label'), 10, 2 );
		
		//add_filter('thwepof_load_products', array($this, 'load_products'));
		add_filter('thwepof_load_products_cat', array($this, 'load_products_cat'));
	}

	public static function instance() {
		if(is_null(self::$_instance)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}	
	
	public function load_products(){
		$args = array( 'post_type' => 'product', 'order' => 'ASC', 'posts_per_page' => -1, 'fields' => 'ids' );
		if(!apply_filters("thwepof_conditions_show_only_active_products", true)){
			$args['post_status'] = 'any';
		}
		$products = get_posts( $args );
		$productsList = array();
		
		if(count($products) > 0){
			foreach($products as $pid){				
				$productsList[] = array("id" => $pid, "title" => get_the_title($pid));
			}
		}		
		return $productsList;
	}
	
	/*public function load_products_cat(){
		$product_cat = array();
		$pcat_terms = get_terms('product_cat', 'orderby=count&hide_empty=0');
		
		foreach($pcat_terms as $pterm){
			$product_cat[] = array("id" => $pterm->slug, "title" => $pterm->name);
		}		
		return $product_cat;
	}*/
	public function load_products_cat(){
		$ignore_translation = apply_filters('thwepof_ignore_wpml_translation_for_product_category', true);
		//$is_wpml_active = function_exists('icl_object_id');
		$is_wpml_active = THWEPOF_Utils::is_wpml_active();
		
		$product_cat = array();
		$pcat_terms = get_terms('product_cat', 'orderby=count&hide_empty=0');
		
		foreach($pcat_terms as $pterm){
			$pcat_slug = $pterm->slug;
			$pcat_slug = THWEPOF_Utils::check_for_wpml_traslation($pcat_slug, $pterm, $is_wpml_active, $ignore_translation);
							
			$product_cat[] = array("id" => $pcat_slug, "title" => $pterm->name);
		}		
		return $product_cat;
	}


	private function sort_field_set($fieldset){
		foreach($fieldset as $hook => &$hooked_fields){
			uasort( $hooked_fields, array( $this, 'sort_fields_by_order' ) );
		}
		return $fieldset;
	}

	public function sort_fields_by_order($a, $b){
	    if($a->get_property('order') == $b->get_property('order')){
	        return 0;
	    }
	    return ($a->get_property('order') < $b->get_property('order')) ? -1 : 1;
	}
	
	public function render_page(){
		$this->render_tabs();
		$this->render_sections();
		$this->render_content();
	}

	public function render_sections() {
		$result = false;
		if(isset($_POST['reset_fields'])){
			$result = $this->reset_to_default();
		}

		$s_action = isset($_POST['s_action']) ? wc_clean(wp_unslash($_POST['s_action'])) : false;

		if($s_action == 'new' || $s_action == 'copy'){
			$result = $this->create_section();
		}else if($s_action == 'edit'){
			$result = $this->edit_section();
		}else if($s_action == 'remove'){
			$result = $this->remove_section();
		}

		$removed_section = get_transient('removed_section');
		if($removed_section){
			$result = $this->print_notices(__('Section removed successfully.', 'woo-extra-product-options'), 'updated', true);
			delete_transient('removed_section');
		}

		$reset_all = get_transient('reset_all_fields');
		if($reset_all){
			$this->print_notices(__('Product fields successfully reset.', 'woo-extra-product-options'), 'updated', false);
			delete_transient('reset_all_fields');
		}
			
		$sections = THWEPOF_Utils::get_sections_admin();
		if(empty($sections)){
			return;
		}
		THWEPOF_Utils::sort_sections($sections);
		
		$array_keys = array_keys($sections);
		$current_section = $this->get_current_section();
				
		echo '<ul class="thpladmin-sections">';
		$i=0; 
		foreach( $sections as $name => $section ){
			$url = $this->get_admin_url($this->page_id, sanitize_title($name));
			$props_json = htmlspecialchars(THWEPOF_Utils_Section::get_property_json($section));
			$rules_json = htmlspecialchars($section->get_property('conditional_rules_json'));
			$s_class = $current_section == $name ? 'current' : '';
			
			?>
			<li><a href="<?php echo esc_url($url); ?>" class="<?php echo $s_class; ?>"><?php echo sanitize_text_field($section->get_property('title')); ?></a></li>
            <li>
            	<form id="section_prop_form_<?php echo esc_attr($name); ?>" method="post" action="">
                    <input type="hidden" name="f_rules[<?php echo $i; ?>]" class="f_rules" value="<?php echo $rules_json; ?>" />
                </form>
				<span class='s_edit_btn dashicons dashicons-edit tips' data-tip='<?php _e('Edit Section', 'woo-extra-product-options'); ?>'  
				onclick="thwepofOpenEditSectionForm(<?php echo $props_json; ?>)"></span>
            </li>
			<li>
				<span class="s_copy_btn dashicons dashicons-admin-page tips" data-tip="<?php _e('Duplicate Section', 'woo-extra-product-options'); ?>"  
				onclick="thwepofOpenCopySectionForm(<?php echo $props_json; ?>)"></span>
			</li>
			<li>
                <form method="post" action="">
                    <input type="hidden" name="s_action" value="remove" />
                    <input type="hidden" name="i_name" value="<?php echo esc_attr($name); ?>" />
					<span class='s_delete_btn dashicons dashicons-no tips' data-tip='<?php _e('Delete Section', 'woo-extra-product-options'); ?>'  
					onclick='thwepofRemoveSection(this)'></span>
					<?php wp_nonce_field( 'remove_section', 'remove_section_'.$name ); ?>
				</form>
            </li>
            <?php
            if(end($array_keys) != $name){
            	echo '<li style="margin-right: 5px;">|</li>';
            }
			
			$i++;
		}
		echo '<li><a href="javascript:void(0)" onclick="thwepofOpenNewSectionForm()" class="btn btn-tiny btn-primary ml-20">+ '. __( 'Add new section', 'woo-extra-product-options') .'</a></li>';
		echo '</ul>';		
		
		if($result){
			echo $result;
		}
	}

	private function truncate_str($string, $offset){
		if($string && strlen($string) > $offset){
			$string = trim(substr($string, 0, $offset)).'...';
		}
		
		return $string;
	}

	private function render_content(){
    	$action = isset($_POST['i_action']) ? wc_clean(wp_unslash($_POST['i_action'])) : false;
    	$section_name = $this->get_current_section();
		$section = THWEPOF_Utils::get_section_admin($section_name);
		
		if(!$section){
			$section = THWEPOF_Utils_Section::prepare_default_section();
		}

		if($action === 'new' || $action === 'copy'){
			echo $this->save_or_update_field($section, $action);	
		}else if($action === 'edit'){
			echo $this->save_or_update_field($section, $action);
		}
		
		if(isset($_POST['save_fields'])){
			echo $this->save_fields($section);
		}

		$section = THWEPOF_Utils::get_section_admin($section_name);
		if(!$section){
			$section = THWEPOF_Utils_Section::prepare_default_section();
		}
		?> 

        <div class="wrap woocommerce"><div class="icon32 icon32-attributes" id="icon-woocommerce"><br/></div>                
		    <form method="post" id="thwepof_product_fields_form" action="">
		    	<?php wp_nonce_field( 'update_product_option_table', 'product_option_nonce' ); ?>

	            <table id="thwepof_product_fields" class="wc_gateways widefat thpladmin_fields_table" cellspacing="0">
	                <thead>
	                    <tr><?php $this->output_actions_row(); ?></tr>
	                    <tr><?php $this->output_fields_table_heading(); ?></tr>						
	                </thead>
	                <tfoot>
	                    <tr><?php $this->output_fields_table_heading(); ?></tr>
	                    <tr><?php $this->output_actions_row(); ?></tr>
	                </tfoot>

	                <tbody class="ui-sortable">
	                <?php 
	                if(THWEPOF_Utils_Section::is_valid_section($section) && THWEPOF_Utils_Section::has_fields($section)){
						$i=0;												
						foreach( $section->get_property('fields') as $field ) :	
							$name = $field->get_property('name');
							$type = $field->get_property('type');
							$is_checked = $field->get_property('checked') ? 1 : 0; 		
							$is_required = $field->is_required() ? 1 : 0; 
							$is_enabled = $field->is_enabled() ? 1 : 0;
							$is_readonly = $field->is_readonly() ? 1 : 0;

							$props_json = htmlspecialchars(THWEPOF_Utils_Field::get_property_set_json($field, $this->field_props));
							$rules_json = htmlspecialchars($field->get_property('conditional_rules_json'));

							$title = '';
							if($type === 'paragraph'){
								$title = $field->get_property('value');
							}else{
								$title = $field->get_property('title');
							}
							$title = esc_attr($title);
							$title = stripslashes($title);
							$title_short = $this->truncate_str($title, 40);

							$placeholder = esc_html($field->get_property('placeholder'));
							$placeholder_short = $this->truncate_str($placeholder, 30);
						?>
							<tr class="row_<?php echo $i; echo($is_enabled == 1 ? '' : ' thwepof-disabled') ?>">
								<td width="1%" class="sort ui-sortable-handle">
									<input type="hidden" name="f_name[<?php echo $i; ?>]" class="f_name" value="<?php echo esc_attr($name); ?>" />
									<input type="hidden" name="f_order[<?php echo $i; ?>]" class="f_order" value="<?php echo $i; ?>" />
									<input type="hidden" name="f_deleted[<?php echo $i; ?>]" class="f_deleted" value="0" />
									<input type="hidden" name="f_enabled[<?php echo $i; ?>]" class="f_enabled" value="<?php echo $is_enabled; ?>" />

									<input type="hidden" name="f_props[<?php echo $i; ?>]" class="f_props" value='<?php echo $props_json; ?>' />
									<input type="hidden" name="f_rules[<?php echo $i; ?>]" class="f_rules" value="<?php echo $rules_json; ?>" />
								</td>
								<td class="td_select"><input type="checkbox" name="select_field"/></td>
								<td class="td_name"><?php echo esc_attr($name); ?></td>
								<td class="td_type"><?php _e($type, 'woo-extra-product-options'); ?></td>
								<td class="td_title">
									<label title="<?php _e($title, 'woo-extra-product-options'); ?>">
										<?php _e($title_short, 'woo-extra-product-options'); ?>
									</label>
								</td>
								<td class="td_placeholder">
									<label title="<?php _e($placeholder, 'woo-extra-product-options'); ?>">
										<?php _e($placeholder_short, 'woo-extra-product-options'); ?>
									</label>
								</td>
								<td class="td_validate"><?php echo esc_html($field->get_property('validator')); ?></td>
								<td class="td_required status">
									<?php echo($is_required == 1 ? '<span class="dashicons dashicons-yes tips" data-tip="'.__('Yes', 'woo-extra-product-options').'"></span>' : '-' ) ?>
								</td>
								<td class="td_enabled status">
									<?php echo($is_enabled == 1 ? '<span class="dashicons dashicons-yes tips" data-tip="'.__('Yes', 'woo-extra-product-options').'"></span>' : '-' ) ?>
								</td>

								<td class="td_actions" align="center">
									<?php if($is_enabled){ ?>
										<span class="f_edit_btn dashicons dashicons-edit tips" data-tip="<?php _e('Edit Field', 'woo-extra-product-options'); ?>" onclick="thwepofOpenEditFieldForm(this, <?php echo $i; ?>)"></span>
									<?php }else{ ?>
										<span class="f_edit_btn dashicons dashicons-edit disabled"></span>
									<?php } ?>
		
									<span class="f_copy_btn dashicons dashicons-admin-page tips" data-tip="<?php _e('Duplicate Field', 'woo-extra-product-options'); ?>" onclick="thwepofOpenCopyFieldForm(this, <?php echo $i; ?>)"></span>
								</td>
							</tr>						
	                <?php 
						$i++; 
						endforeach; 
					}else{
						echo '<tr><td colspan="10" align="center" class="empty-msg-row">'.__("No custom fields found. Click on <b>Add field</b> button to create new fields.", "woo-extra-product-options").'</td></tr>';
					}
					?>
	                </tbody>
	            </table> 
            </form>
            <?php
            $this->section_form->output_section_forms();
            $this->field_form->output_field_forms();
            
            //$this->output_add_section_form_pp();
			//$this->output_edit_section_form_pp();
            //$this->output_add_field_form_pp();
			//$this->output_edit_field_form_pp();
			//$this->output_popup_form_fragments();
			
			?>
    	</div>
    <?php
    }

    private function output_fields_table_heading(){
		?>
		<th class="sort"></th>
		<th class="check-column"><input type="checkbox" style="margin:0px 4px -1px -1px;" onclick="thwepofSelectAllProductFields(this)"/></th>
		<th class="name"><?php _e('Name', 'woo-extra-product-options'); ?></th>
		<th class="id"><?php _e('Type', 'woo-extra-product-options'); ?></th>
		<th><?php _e('Label', 'woo-extra-product-options'); ?></th>
		<th><?php _e('Placeholder', 'woo-extra-product-options'); ?></th>
		<th><?php _e('Validations', 'woo-extra-product-options'); ?></th>
        <th class="status"><?php _e('Required', 'woo-extra-product-options'); ?></th>
		<th class="status"><?php _e('Enabled', 'woo-extra-product-options'); ?></th>	
        <th class="status"><?php _e('Actions', 'woo-extra-product-options'); ?></th>	
        <?php
	}

	private function output_actions_row(){
		?>
        <th colspan="5">
            <button type="button" onclick="thwepofOpenNewFieldForm()" class="btn btn-small btn-primary"><?php _e('+ Add field', 'woo-extra-product-options'); ?></button>
            <button type="button" onclick="thwepofRemoveSelectedFields()" class="btn btn-small"><?php _e('Remove', 'woo-extra-product-options'); ?></button>
            <button type="button" onclick="thwepofEnableSelectedFields()" class="btn btn-small"><?php _e('Enable', 'woo-extra-product-options'); ?></button>
            <button type="button" onclick="thwepofDisableSelectedFields()" class="btn btn-small"><?php _e('Disable', 'woo-extra-product-options'); ?></button>
        </th>
        <th colspan="5">
        	<input type="submit" name="save_fields" class="btn btn-small btn-primary" value="<?php _e('Save changes', 'woo-extra-product-options') ?>" style="float:right" />
            <input type="submit" name="reset_fields" class="btn btn-small" value="<?php _e('Reset to default options', 'woo-extra-product-options') ?>" style="float:right; margin-right: 5px;" 
			onclick="return confirm('Are you sure you want to reset to default fields? all your changes will be deleted.');"/>
        </th>  
    	<?php 
	}

	public function reset_to_default() {
		check_admin_referer( 'update_product_option_table', 'product_option_nonce' );

		$capability = THWEPOF_Utils::wepo_capability();
		if(!current_user_can($capability)){
			wp_die();
		}

		delete_option(THWEPOF_Utils::OPTION_KEY_CUSTOM_SECTIONS);
		delete_option(THWEPOF_Utils::OPTION_KEY_SECTION_HOOK_MAP);
		delete_option(THWEPOF_Utils::OPTION_KEY_NAME_TITLE_MAP);

		$current_section = $this->get_current_section();
		set_transient( 'reset_all_fields', 'yes' , MINUTE_IN_SECONDS );

		if($current_section !== 'default'){
			$default_url = $this->get_admin_url('general_settings');
			wp_safe_redirect( $default_url );
			exit;
		}
	}

	public function woo_attribute_label( $label, $key ) {
		if(!empty($label)){
			$options_extra = THWEPOF_Utils::get_product_fields_full();
			if($options_extra){
				if(array_key_exists($label, $options_extra)) {
					$label = $options_extra[$label]->get_property('title');
				}
			}
		}
		return $label;
	}
	
   /*------------------------------------*
	*----- SECTION FUNCTIONS - START ----*
	*------------------------------------*/
	public function create_section(){
		check_admin_referer( 'save_section_property', 'save_section_nonce' );

		$capability = THWEPOF_Utils::wepo_capability();
		if(!current_user_can($capability)){
			wp_die();
		}

		$section = THWEPOF_Utils_Section::prepare_section_from_posted_data($_POST);
		$section = $this->prepare_copy_section($section, $_POST);

		$result1 = THWEPOF_Utils::update_section($section);
		$result2 = $this->update_options_name_title_map();
		
		if($result1 == true){
			return $this->print_notices(__('New section added successfully.', 'woo-extra-product-options'), 'updated', true);
		}else{
			return $this->print_notices(__('New section not added due to an error.', 'woo-extra-product-options'), 'error', true);
		}		
	}
	
	public function edit_section(){
		check_admin_referer( 'save_section_property', 'save_section_nonce' );

		$capability = THWEPOF_Utils::wepo_capability();
		if(!current_user_can($capability)){
			wp_die();
		}

		$section  = THWEPOF_Utils_Section::prepare_section_from_posted_data($_POST, 'edit');
		$name 	  = $section->get_property('name');
		$position = $section->get_property('position');
		$old_position = !empty($_POST['i_position_old']) ? wc_clean(wp_unslash($_POST['i_position_old'])) : '';
		
		if($old_position && $position && ($old_position != $position)){			
			$this->remove_section_from_hook($position_old, $name);
		}
		
		$result = THWEPOF_Utils::update_section($section);
		
		if($result == true){
			return $this->print_notices(__('Section details updated successfully.', 'woo-extra-product-options'), 'updated', true);
		}else{
			return $this->print_notices(__('Section details not updated due to an error.', 'woo-extra-product-options'), 'error', true);
		}		
	}

	public function remove_section(){
		$section_name = !empty($_POST['i_name']) ? wc_clean(wp_unslash($_POST['i_name'])) : false;

		check_admin_referer( 'remove_section', 'remove_section_'.$section_name );

		$capability = THWEPOF_Utils::wepo_capability();
		if(!current_user_can($capability)){
			wp_die();
		}

		if($section_name){	
			$result = $this->delete_section($section_name);			
										
			if ($result == true) {
				$current_section = $this->get_current_section();
				set_transient( 'removed_section', $section_name , MINUTE_IN_SECONDS );

				if($current_section === $section_name){
					$default_url = $this->get_admin_url('general_settings');
					wp_safe_redirect( $default_url );
					exit;
				}

			} else {
				return $this->print_notices(__('Section not removed due to an error.', 'woo-extra-product-options'), 'error', true);
			}
		}
	}

	public function prepare_copy_section($section, $posted){
		$s_name_copy = isset($posted['s_name_copy']) ? wc_clean(wp_unslash($posted['s_name_copy'])) : '';
		if($s_name_copy){
			$section_copy = THWEPOF_Utils::get_section_admin($s_name_copy);
			if(THWEPOF_Utils_Section::is_valid_section($section_copy)){
				$field_set = $section_copy->get_property('fields');
				if(is_array($field_set) && !empty($field_set)){
					$section->set_property('fields', $field_set);
				}
			}
		}
		return $section;
	}


	private function update_options_name_title_map(){
	 	$name_title_map = array();
	 	$sections = THWEPOF_Utils::get_sections_admin();
		if($sections && is_array($sections)){
			foreach($sections as $section_name => $section){
				if(THWEPOF_Utils_Section::is_valid_section($section)){					
					$fields = $section->get_property('fields');					
					if($fields && is_array($fields)){
						foreach($fields as $field_name => $field){
							if(THWEPOF_Utils_Field::is_valid_field($field) && THWEPOF_Utils_Field::is_enabled($field)){
								//$name_title_map[$field_name] = $field->get_display_label();
								$name_title_map[$field_name] = $field->get_property('title');
							}
						}
					}
				}
			}
		}
	 
		$result = THWEPOF_Utils::save_name_title_map($name_title_map);
		return $result;
	}

	public function delete_section($section_name){
		if($section_name){	
			$sections = THWEPOF_Utils::get_sections_admin();
			if(is_array($sections) && isset($sections[$section_name])){
				$section = $sections[$section_name];
				
				if(THWEPOF_Utils_Section::is_valid_section($section)){
					$hook_name = $section->get_property('position');
					
					$this->remove_section_from_hook($hook_name, $section_name);
					unset($sections[$section_name]);
								
					$result = THWEPOF_Utils::save_sections($sections);		
					return $result;
				}
			}
		}
		return false;
	}
	
	private function remove_section_from_hook($hook_name, $section_name){
		if(isset($hook_name) && isset($section_name) && !empty($hook_name) && !empty($section_name)){	
			$hook_map = THWEPOF_Utils::get_section_hook_map();
			
			if(is_array($hook_map) && isset($hook_map[$hook_name])){
				$hooked_sections = $hook_map[$hook_name];
				if(is_array($hooked_sections) && !in_array($section_name, $hooked_sections)){
					unset($hooked_sections[$section_name]);				
					$hook_map[$hook_name] = $hooked_sections;
					THWEPOF_Utils::save_section_hook_map($hook_map);
				}
			}				
		}
	}
   /*-----------------------------------*
	*----- SECTION FUNCTIONS - END -----*
	*-----------------------------------*/

   /*-----------------------------------*
	*----- FIELD FUNCTIONS - START -----*
	*-----------------------------------*/
	private function save_or_update_field($section, $action) {
		try {
			check_admin_referer( 'save_field_property', 'save_field_nonce' );

			$capability = THWEPOF_Utils::wepo_capability();
			if(!current_user_can($capability)){
				wp_die();
			}

			$field = THWEPOF_Utils_Field::prepare_field_from_posted_data($_POST, $this->field_props);
			
			if($action === 'edit'){
				$section = THWEPOF_Utils_Section::update_field($section, $field);
			}else{
				$section = THWEPOF_Utils_Section::add_field($section, $field);
			}
			
			$result1 = THWEPOF_Utils::update_section($section);
			$result2 = $this->update_options_name_title_map();

			if($result1 == true) {
				$this->print_notices(__('Your changes were saved.', 'woo-extra-product-options'), 'updated', false);
			}else {
				$this->print_notices(__('Your changes were not saved due to an error (or you made none!).', 'woo-extra-product-options'), 'error', false);
			}
		} catch (Exception $e) {
			$this->print_notices(__('Your changes were not saved due to an error.', 'woo-extra-product-options'), 'error', false);
		}
	}

	private function save_fields($section) {
		try {
			check_admin_referer( 'update_product_option_table', 'product_option_nonce' );

			$capability = THWEPOF_Utils::wepo_capability();
			if(!current_user_can($capability)){
				wp_die();
			}

			$f_names = !empty( $_POST['f_name'] ) ? wc_clean(wp_unslash($_POST['f_name'])) : array();	
			if(empty($f_names)){
				$this->print_notices(__('Your changes were not saved due to no fields found.', 'woo-extra-product-options'), 'error', false);
				return;
			}
			
			$f_order   = !empty( $_POST['f_order'] ) ? wc_clean(wp_unslash($_POST['f_order'])) : array();	
			$f_deleted = !empty( $_POST['f_deleted'] ) ? wc_clean(wp_unslash($_POST['f_deleted'])) : array();
			$f_enabled = !empty( $_POST['f_enabled'] ) ? wc_clean(wp_unslash($_POST['f_enabled'])) : array();
						
			$sname = $section->get_property('name');
			$field_set = THWEPOF_Utils_Section::get_fields($section);
						
			$max = max( array_map( 'absint', array_keys( $f_names ) ) );
			for($i = 0; $i <= $max; $i++) {
				$name = $f_names[$i];
				
				if(isset($field_set[$name])){
					if(isset($f_deleted[$i]) && $f_deleted[$i] == 1){
						unset($field_set[$name]);
						continue;
					}
					
					$field = $field_set[$name];
					$field->set_property('order', isset($f_order[$i]) ? wc_clean(wp_unslash($f_order[$i])) : 0);
					$field->set_property('enabled', isset($f_enabled[$i]) ? wc_clean(wp_unslash($f_enabled[$i])) : 0);
					
					$field_set[$name] = $field;
				}
			}
			$section->set_property('fields', $field_set);
			$section = THWEPOF_Utils_Section::sort_fields($section);
			
			$result = THWEPOF_Utils::update_section($section);
			
			if ($result == true) {
				$this->print_notices(__('Your changes were saved.', 'woo-extra-product-options'), 'updated', false);
			} else {
				$this->print_notices(__('Your changes were not saved due to an error (or you made none!).', 'woo-extra-product-options'), 'error', false);
			}
		} catch (Exception $e) {
			$this->print_notices(__('Your changes were not saved due to an error.', 'woo-extra-product-options'), 'error', false);
		}
	}
   /*-----------------------------------*
	*----- FIELD FUNCTIONS - END -------*
	*-----------------------------------*/	
}
endif;