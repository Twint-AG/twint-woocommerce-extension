<?php
/**
 * Woo Extra Product Options - Field Forms
 *
 * @author    ThemeHigh
 * @category  Admin
 */

if(!defined('WPINC')){	die; }

if(!class_exists('THWEPOF_Admin_Form_Field')):

class THWEPOF_Admin_Form_Field extends THWEPOF_Admin_Form{
	private $field_props = array();

	public function __construct() {
		parent::__construct();
		$this->init_constants();
	}

	private function init_constants(){
		$this->field_props = $this->get_field_form_props();
	}

	private function get_field_types(){
		return array(
			'inputtext'     => __('Text', 'woo-extra-product-options'),
			'hidden'        => __('Hidden', 'woo-extra-product-options'),
			'number'        => __('Number', 'woo-extra-product-options'), 
			'tel'           => __('Telephone', 'woo-extra-product-options'),
			'password'      => __('Password', 'woo-extra-product-options'),
			'email'         => __('Email', 'woo-extra-product-options'),
			'url'           => __('Url', 'woo-extra-product-options'),
			'range'         => __('Slider/Range', 'woo-extra-product-options'),
			'textarea'      => __('Textarea', 'woo-extra-product-options'),
			'select'        => __('Select', 'woo-extra-product-options'),
			'checkbox'      => __('Checkbox', 'woo-extra-product-options'),
			'checkboxgroup' => __('Checkbox Group', 'woo-extra-product-options'),
			'radio'         => __('Radio Button', 'woo-extra-product-options'),
			'datepicker'    => __('Date Picker', 'woo-extra-product-options'),
			'colorpicker'   => __('Colorpicker', 'woo-extra-product-options'),
			'switch'        => __('Switch', 'woo-extra-product-options'),
			'heading'       => __('Heading', 'woo-extra-product-options'),
			'paragraph'     => __('Paragraph', 'woo-extra-product-options'),
			'separator'     => __('Separator', 'woo-extra-product-options')
		);
	}

	public function get_field_form_props(){
		$field_types = $this->get_field_types();
		$positions = $this->get_available_positions();
		$html_title_tags = $this->get_html_title_tags();
		
		$validators = array(
			''			=> __('Select validation', 'woo-extra-product-options'),
			'email'		=> __('Email', 'woo-extra-product-options'),
			'number'	=> __('Number', 'woo-extra-product-options'),
		);

		$title_positions = array(
			'left' 	=> __('Left of the field', 'woo-extra-product-options'),
			'above'	=> __('Above field', 'woo-extra-product-options'),
		);

		return array(
			'name' 		  => array('type'=>'text', 'name'=>'name', 'label'=>__('Name', 'woo-extra-product-options'), 'required'=>1),
			'type' 		  => array('type'=>'select', 'name'=>'type', 'label'=>__('Field Type','woo-extra-product-options'), 'required'=>1, 'options'=>$field_types, 'onchange'=>'thwepofFieldTypeChangeListner(this)'),
			'value' 	  => array('type'=>'text', 'name'=>'value', 'label'=>__('Default Value', 'woo-extra-product-options')),
			'options' 	  => array('type'=>'text', 'name'=>'options', 'label'=>__('Options', 'woo-extra-product-options'), 'placeholder'=>__('separate options with pipe(|)', 'woo-extra-product-options')),
			'placeholder' => array('type'=>'text', 'name'=>'placeholder', 'label'=>__('Placeholder', 'woo-extra-product-options')),
			'validator'   => array('type'=>'select', 'name'=>'validator', 'label'=>__('Validation', 'woo-extra-product-options'), 'placeholder'=>__('Select validation', 'woo-extra-product-options'), 'options'=>$validators),
			'cssclass'    => array('type'=>'text', 'name'=>'cssclass', 'label'=>__('Wrapper Class', 'woo-extra-product-options'), 'placeholder'=>__('separate classes with comma', 'woo-extra-product-options')),
			'input_class'    => array('type'=>'text', 'name'=>'input_class', 'label'=>__('Input Class', 'woo-extra-product-options'), 'placeholder'=>__('separate classes with comma', 'woo-extra-product-options')),
			'position' 	  => array('type'=>'select', 'name'=>'position', 'label'=>__('Position', 'woo-extra-product-options'), 'options'=>$positions),
			
			'minlength'   => array('type'=>'number', 'name'=>'minlength', 'label'=>__('Min. Length', 'woo-extra-product-options'), 'min'=>0, 'hint_text'=>__('The minimum number of characters allowed', 'woo-extra-product-options')),
			'maxlength'   => array('type'=>'number', 'name'=>'maxlength', 'label'=>__('Max. Length', 'woo-extra-product-options'), 'min'=>0, 'hint_text'=>__('The maximum number of characters allowed', 'woo-extra-product-options')),

			'step'   => array('type'=>'number', 'name'=>'step', 'label'=>__('Step. Value', 'woo-extra-product-options'), 'min'=>0, 'hint_text'=>__('Specifies the legal number intervals', 'woo-extra-product-options')),

			'cols' => array('type'=>'text', 'name'=>'cols', 'label'=>__('Cols', 'woo-extra-product-options'), 'hint_text'=>__('The visible width of a text area', 'woo-extra-product-options')),
			'rows' => array('type'=>'text', 'name'=>'rows', 'label'=>__('Rows', 'woo-extra-product-options'), 'hint_text'=>__('The visible height of a text area', 'woo-extra-product-options')),
			
			'checked'  => array('type'=>'checkbox', 'name'=>'checked', 'label'=>__('Checked by default', 'woo-extra-product-options'), 'value'=>'yes', 'checked'=>0),

			'required' => array('type'=>'checkbox', 'name'=>'required', 'label'=>__('Required', 'woo-extra-product-options'), 'value'=>'yes', 'checked'=>0, 'status'=>1),
			'enabled'  => array('type'=>'checkbox', 'name'=>'enabled', 'label'=>__('Enabled', 'woo-extra-product-options'), 'value'=>'yes', 'checked'=>1, 'status'=>1),
			'readonly'  => array('type'=>'checkbox', 'name'=>'readonly', 'label'=>__('Readonly', 'woo-extra-product-options'), 'value'=>'yes', 'checked'=>0, 'status'=>1),
			'view_password'  => array('type'=>'checkbox', 'name'=>'view_password', 'label'=>__('Show view password Icon', 'woo-extra-product-options'), 'value'=>'yes', 'checked'=>0, 'status'=>1),
			
			'title'          => array('type'=>'text', 'name'=>'title', 'label'=>__('Label', 'woo-extra-product-options')),
			'title_position' => array('type'=>'select', 'name'=>'title_position', 'label'=>__('Label Position', 'woo-extra-product-options'), 'options'=>$title_positions, 'value'=>'left'),
			'title_type'     => array('type'=>'select', 'name'=>'title_type', 'label'=>__('Heading Type', 'woo-extra-product-options'), 'value'=>'h1', 'options'=>$html_title_tags),
			'title_class'    => array('type'=>'text', 'name'=>'title_class', 'label'=>__('Label Class', 'woo-extra-product-options'), 'placeholder'=>__('separate classes with comma', 'woo-extra-product-options')),

			'input_mask'   => array('type'=>'text', 'name'=>'input_mask', 'label'=>__('Input Masking Pattern', 'woo-extra-product-options'), 'hint_text'=>__('Helps to ensure input to a predefined format like (999) 999-9999.', 'woo-extra-product-options')),
		);
	}

	public function output_field_forms(){
		$this->output_field_form_pp();
		$this->output_form_fragments();
	}

	private function output_field_form_pp(){
		?>
        <div id="thwepof_field_form_pp" class="thpladmin-modal-mask">
          <?php $this->output_popup_form_fields(); ?>
        </div>
        <?php
	}

	/*****************************************/
	/********** POPUP FORM WIZARD ************/
	/*****************************************/
	private function output_popup_form_fields(){
		?>
		<div class="thpladmin-modal">
			<div class="modal-container">
				<span class="modal-close" onclick="thwepofCloseModal(this)">×</span>
				<div class="modal-content">
					<div class="modal-body">
						<div class="form-wizard wizard">
							<aside>
								<side-title class="wizard-title"><?php _e('Save Field', 'woo-extra-product-options'); ?></side-title>
								<ul class="pp_nav_links">
									<li class="text-primary active first" data-index="0">
										<i class="dashicons dashicons-admin-generic text-primary"></i><?php _e('Basic Info', 'woo-extra-product-options'); ?>
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>
									<li class="text-primary" data-index="1">
										<i class="dashicons dashicons-art text-primary"></i><?php _e('Display Styles', 'woo-extra-product-options'); ?>
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>
									<li class="text-primary last" data-index="2">
										<i class="dashicons dashicons-filter text-primary"></i><?php _e('Display Rules', 'woo-extra-product-options'); ?>
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>
								</ul>
							</aside>
							<main class="form-container main-full">
								<form method="post" id="thwepof_field_form" action="">
									<input type="hidden" name="i_action" value="" >
									<!--<input type="hidden" name="i_rowid" value="" >-->
									<input type="hidden" name="i_name_old" value="" >
									<input type="hidden" name="i_rules" value="" >

									<div class="data-panel data_panel_0">
										<?php $this->render_form_tab_general_info(); ?>
									</div>
									<div class="data-panel data_panel_1">
										<?php $this->render_form_tab_display_details(); ?>
									</div>
									<div class="data-panel data_panel_2">
										<?php $this->render_form_tab_display_rules(); ?>
									</div>
									<?php wp_nonce_field( 'save_field_property', 'save_field_nonce' ); ?>
								</form>
							</main>
							<footer>
								<span class="Loader"></span>
								<div class="btn-toolbar">
									<button class="save-btn pull-right btn btn-primary" onclick="thwepofSaveField(this)">
										<span><?php _e('Save & Close', 'woo-extra-product-options'); ?></span>
									</button>
									<button class="next-btn pull-right btn btn-primary-alt" onclick="thwepofWizardNext(this)">
										<span><?php _e('Next', 'woo-extra-product-options'); ?></span><i class="i i-plus"></i>
									</button>
									<button class="prev-btn pull-right btn btn-primary-alt" onclick="thwepofWizardPrevious(this)">
										<span><?php _e('Back', 'woo-extra-product-options'); ?></span><i class="i i-plus"></i>
									</button>
								</div>
							</footer>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/*----- TAB - General Info -----*/
	private function render_form_tab_general_info(){
		$this->render_form_tab_main_title(__('Basic Details', 'woo-extra-product-options'));

		?>
		<div style="display: inherit;" class="data-panel-content">
			<?php
			$this->render_form_fragment_general();
			?>
			<table class="thwepof_field_form_tab_general_placeholder thwepof_pp_table"></table>
		</div>
		<?php
	}

	/*----- TAB - Display Details -----*/
	private function render_form_tab_display_details(){
		$this->render_form_tab_main_title(__('Display Settings', 'woo-extra-product-options'));

		?>
		<div style="display: inherit;" class="data-panel-content">
			<table class="thwepof_pp_table compact">
				<?php
				$this->render_form_elm_row($this->field_props['cssclass']);
				//$this->render_form_elm_row($this->field_props['input_class']);
				$this->render_form_elm_row($this->field_props['title_class']);

				$this->render_form_elm_row($this->field_props['title_position']);
				//$this->render_form_elm_row($this->field_props['title_type']);
				//$this->render_form_elm_row($this->field_props['title_color']);

				//$this->render_form_elm_row_cb($this->field_props['hide_in_cart']);
				//$this->render_form_elm_row_cb($this->field_props['hide_in_checkout']);
				//$this->render_form_elm_row_cb($this->field_props['hide_in_order']);
				//$this->render_form_elm_row_cb($this->field_props['hide_in_order_admin']);
				?>
			</table>
		</div>
		<?php
	}

	/*----- TAB - Display Rules -----*/
	private function render_form_tab_display_rules(){
		$this->render_form_tab_main_title(__('Display Rules', 'woo-extra-product-options'));

		?>
		<div style="display: inherit;" class="data-panel-content">
			<table class="thwepof_pp_table thwepof-display-rules">
				<?php
				$this->render_form_fragment_rules('field'); 
				?>
			</table>
		</div>
		<?php
	}

	/*-------------------------------*/
	/*------ Form Field Groups ------*/
	/*-------------------------------*/
	private function render_form_fragment_general($input_field = true){
		?>
		<div class="err_msgs"></div>
        <table class="thwepof_pp_table">
        	<?php
			$this->render_form_elm_row($this->field_props['type']);
			$this->render_form_elm_row($this->field_props['name']);
			?>
        </table>  
        <?php
	}

	private function output_form_fragments(){
		$this->render_form_field_inputtext();
		$this->render_form_field_hidden();
		$this->render_form_field_number();
		$this->render_form_field_tel();
		$this->render_form_field_password();
		$this->render_form_field_email();
		$this->render_form_field_url();
		$this->render_form_field_range();
		$this->render_form_field_textarea();
		$this->render_form_field_select();
		$this->render_form_field_checkbox();
		$this->render_form_field_radio();

		$this->render_form_field_checkboxgroup();
		$this->render_form_field_datepicker();
		$this->render_form_field_colorpicker();
		$this->render_form_field_switch();
		$this->render_form_field_heading();
		$this->render_form_field_paragraph();
		$this->render_form_field_separator();
		
		$this->render_field_form_fragment_product_list();
		$this->render_field_form_fragment_category_list();
		$this->render_field_form_fragment_tag_list();
	}

	private function render_form_field_inputtext(){
		?>
        <table id="thwepof_field_form_id_inputtext" class="thwepo_pp_table" style="display:none;">
        	<?php
			$this->render_form_elm_row($this->field_props['title']);
			//$this->render_form_elm_row($this->field_props['title_position']);
			$this->render_form_elm_row($this->field_props['value']);
			$this->render_form_elm_row($this->field_props['placeholder']);
			$this->render_form_elm_row($this->field_props['minlength']);
			$this->render_form_elm_row($this->field_props['maxlength']);
			//$this->render_form_elm_row($this->field_props['cssclass']);
			//$this->render_form_elm_row($this->field_props['title_class']);
			$this->render_form_elm_row($this->field_props['validator']);
			$this->render_form_elm_row($this->field_props['input_mask']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>
        </table>
        <?php   
	}

	private function render_form_field_hidden(){
		?>
        <table id="thwepof_field_form_id_hidden" class="thwepo_pp_table" style="display:none;">
			<?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['value']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>  
        </table>
        <?php   
	}

	private function render_form_field_number(){
		$min_attribute = $this->field_props['minlength'];
        $min_attribute['label'] = __('Min. Value', 'woo-extra-product-options');
		$min_attribute['hint_text'] = __('The minimum value allowed', 'woo-extra-product-options');

        $max_attribute = $this->field_props['maxlength'];
        $max_attribute['label'] = __('Max. Value', 'woo-extra-product-options');
		$max_attribute['hint_text'] = __('The maximum value allowed', 'woo-extra-product-options');

		$prop_value = $this->field_props['value'];
		$prop_value['type'] = 'number';

		?>
        <table id="thwepof_field_form_id_number" class="thwepo_pp_table" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			//$this->render_form_elm_row($this->field_props['title_position']);
			$this->render_form_elm_row($prop_value);
			$this->render_form_elm_row($this->field_props['placeholder']);

			$this->render_form_elm_row($min_attribute);
			$this->render_form_elm_row($max_attribute);
			$this->render_form_elm_row($this->field_props['step']);
			//$this->render_form_elm_row($this->field_props['cssclass']);
			//$this->render_form_elm_row($this->field_props['title_class']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>     
        </table>
        <?php   
	}

	private function render_form_field_tel(){
		?>
        <table id="thwepof_field_form_id_tel" class="thwepo_pp_table" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			//$this->render_form_elm_row($this->field_props['title_position']);
			$this->render_form_elm_row($this->field_props['value']);
			$this->render_form_elm_row($this->field_props['placeholder']);
			//$this->render_form_elm_row($this->field_props['cssclass']);
			//$this->render_form_elm_row($this->field_props['title_class']);
			$this->render_form_elm_row($this->field_props['validator']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>    
        </table>
        <?php   
	}

	private function render_form_field_password(){
		?>
        <table id="thwepof_field_form_id_password" class="thwepo_pp_table" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['placeholder']);
			$this->render_form_elm_row($this->field_props['validator']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			$this->render_form_elm_row_cb($this->field_props['view_password']);
			?>  
        </table>
        <?php   
	}

	private function render_form_field_email(){
		?>
        <table id="thwepof_field_form_id_email" class="thwepo_pp_table" style="display:none;">
        	<?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['value']);
			$this->render_form_elm_row($this->field_props['placeholder']);
			$this->render_form_elm_row($this->field_props['validator']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>
        </table>
        <?php   
	}

	private function render_form_field_url(){
		?>
        <table id="thwepof_field_form_id_url" class="thwepo_pp_table" style="display:none;">
        	<?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['value']);
			$this->render_form_elm_row($this->field_props['placeholder']);
			$this->render_form_elm_row($this->field_props['validator']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>
        </table>
        <?php   
	}

	private function render_form_field_range(){
		$min_attribute = $this->field_props['minlength'];
        $min_attribute['label'] = __('Min. Value', 'woo-extra-product-options');
		$min_attribute['hint_text'] = __('The minimum value allowed', 'woo-extra-product-options');

        $max_attribute = $this->field_props['maxlength'];
        $max_attribute['label'] = __('Max. Value', 'woo-extra-product-options');
		$max_attribute['hint_text'] = __('The maximum value allowed', 'woo-extra-product-options');

		?>
        <table id="thwepof_field_form_id_range" class="thwepo_pp_table" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['value']);
			// $this->render_form_elm_row($this->field_props['placeholder']);

			$this->render_form_elm_row($min_attribute);
			$this->render_form_elm_row($max_attribute);
			$this->render_form_elm_row($this->field_props['step']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>     
        </table>
        <?php   
	}
	
	private function render_form_field_textarea(){
		?>
        <table id="thwepof_field_form_id_textarea" class="thwepo_pp_table" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			//$this->render_form_elm_row($this->field_props['title_position']);
			$this->render_form_elm_row($this->field_props['value']);
			$this->render_form_elm_row($this->field_props['placeholder']);
			//$this->render_form_elm_row($this->field_props['cssclass']);
			//$this->render_form_elm_row($this->field_props['title_class']);
			$this->render_form_elm_row($this->field_props['cols']);
			$this->render_form_elm_row($this->field_props['rows']);
			$this->render_form_elm_row($this->field_props['minlength']);
			$this->render_form_elm_row($this->field_props['maxlength']);
			$this->render_form_elm_row($this->field_props['validator']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>      
        </table>
        <?php   
	}
	
	private function render_form_field_select(){
		?>
        <table id="thwepof_field_form_id_select" class="thwepo_pp_table" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['placeholder']);
			$this->render_form_elm_row($this->field_props['options']);
			$this->render_form_elm_row($this->field_props['value']);
			//$this->render_form_elm_row($this->field_props['cssclass']);
			//$this->render_form_elm_row($this->field_props['title_class']);
			//$this->render_form_elm_row($this->field_props['title_position']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>
        </table>
        <?php   
	}
	
	private function render_form_field_radio(){
		?>
        <table id="thwepof_field_form_id_radio" class="thwepo_pp_table" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			//$this->render_form_elm_row($this->field_props['title_position']);
			$this->render_form_elm_row($this->field_props['options']);
			$this->render_form_elm_row($this->field_props['value']);
			//$this->render_form_elm_row($this->field_props['cssclass']);
			//$this->render_form_elm_row($this->field_props['title_class']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>
        </table>
        <?php   
	}
	
	private function render_form_field_checkbox(){
		$prop_value = $this->field_props['value'];
		$prop_value['label'] = __('Value', 'woo-extra-product-options');

		?>
        <table id="thwepof_field_form_id_checkbox" class="thwepo_pp_table" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($prop_value);			
			//$this->render_form_elm_row($this->field_props['cssclass']);
			//$this->render_form_elm_row($this->field_props['title_class']);

			$this->render_form_elm_row_cb($this->field_props['checked']);
			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>
        </table>
        <?php   
	}

	private function render_form_field_switch(){
		$prop_value = $this->field_props['value'];
		$prop_value['label'] = __('Value', 'woo-extra-product-options');

		?>
        <table id="thwepof_field_form_id_switch" class="thwepo_pp_table" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($prop_value);			

			$this->render_form_elm_row_cb($this->field_props['checked']);
			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>
        </table>
        <?php   
	}
	
	private function render_form_field_checkboxgroup(){

		$min_checked = $this->field_props['minlength'];
        $min_checked['label'] = __('Min. Selections', 'woo-extra-product-options');
		$min_checked['hint_text'] = __('The minimum checked item', 'woo-extra-product-options');

        $max_checked = $this->field_props['maxlength'];
        $max_checked['label'] = __('Max. Selections', 'woo-extra-product-options');
		$max_checked['hint_text'] = __('The maximum checked item', 'woo-extra-product-options');

		?>
        <table id="thwepof_field_form_id_checkboxgroup" class="thwepo_pp_table" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			//$this->render_form_elm_row($this->field_props['title_position']);
			$this->render_form_elm_row($this->field_props['options']);
			$this->render_form_elm_row($this->field_props['value']);

			$this->render_form_elm_row($min_checked);
			$this->render_form_elm_row($max_checked);
			//$this->render_form_elm_row($this->field_props['cssclass']);
			//$this->render_form_elm_row($this->field_props['title_class']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>
        </table>
        <?php   
	}
	
	private function render_form_field_datepicker(){
		$prop_value = $this->field_props['value'];
		$prop_value['label'] = __('Default Date', 'woo-extra-product-options');
		//$prop_value['hint_text'] = 'Specify a date in the format mm/dd/yyyy.';
		//$prop_value['hint_text'] = "Specify a date in the format {month} {dd}, {year}, or number of days from today (e.g. +7) or a string of values and periods ('y' for years, 'm' for months, 'w' for weeks, 'd' for days, e.g. '+1m +7d'), or leave empty for today.";
		$prop_value['hint_text'] = __('Enter default date in the format {month} {dd}, {year}', 'woo-extra-product-options');

		?>
        <table id="thwepof_field_form_id_datepicker" class="thwepo_pp_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			//$this->render_form_elm_row($this->field_props['title_position']);
			$this->render_form_elm_row($prop_value);
			$this->render_form_elm_row($this->field_props['placeholder']);
			//$this->render_form_elm_row($this->field_props['cssclass']);
			//$this->render_form_elm_row($this->field_props['title_class']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			$this->render_form_elm_row_cb($this->field_props['readonly']);
			?> 
        </table>
        <?php   
	}
	
	private function render_form_field_colorpicker(){
		$prop_value = $this->field_props['value'];
		$prop_value['label'] = __('Default Color', 'woo-extra-product-options');
		$prop_value['type'] = 'colorpicker';
		
		?>
        <table id="thwepof_field_form_id_colorpicker" class="thwepo_pp_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row_cp($prop_value);
			//$this->render_form_elm_row($this->field_props['cssclass']);
			//$this->render_form_elm_row($this->field_props['title_class']);
			//$this->render_form_elm_row($this->field_props['title_position']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?> 
        </table>
        <?php   
	}
	
	private function render_form_field_heading(){
		$prop_value = $this->field_props['value'];
		$prop_value['label'] = __('Heading Text', 'woo-extra-product-options');

		?>
        <table id="thwepof_field_form_id_heading" class="thwepo_pp_table" style="display:none;">
			<?php
			$this->render_form_elm_row($prop_value);
			$this->render_form_elm_row($this->field_props['title_type']);
			$this->render_form_elm_row($this->field_props['cssclass']);

			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>
        </table>
        <?php   
	}
	
	private function render_form_field_paragraph(){
		$prop_value = $this->field_props['value'];
		$prop_value['type'] = 'textarea';
		$prop_value['label'] = __('Content', 'woo-extra-product-options');
		$prop_value['required'] = true;

		?>
        <table id="thwepof_field_form_id_paragraph" class="thwepo_pp_table" style="display:none;">
			<?php
			$this->render_form_elm_row($prop_value);
			$this->render_form_elm_row($this->field_props['cssclass']);

			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>
        </table>
        <?php  
	}

	private function render_form_field_separator(){
		?>
        <table id="thwepof_field_form_id_separator" class="thwepo_pp_table" style="display:none;">
			<?php
			$this->render_form_elm_row($this->field_props['cssclass']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>
        </table>
        <?php  
	}

	/*
	private function render_form_fragment_options(){
		?>
		<tr>
			<td class="sub-title"><?php _e('Options', 'woo-extra-product-options'); ?></td>
			<?php $this->render_form_fragment_tooltip(); ?>
			<td></td>
		</tr>
		<tr>
			<td colspan="3" class="p-0">
				<table border="0" cellpadding="0" cellspacing="0" class="thwepo-option-list thpladmin-options-table"><tbody>
					<tr>
						<td class="key"><input type="text" name="i_options_key[]" placeholder="Option Value"></td>
						<td class="value"><input type="text" name="i_options_text[]" placeholder="Option Text"></td>
						<td class="price"><input type="text" name="i_options_price[]" placeholder="Price"></td>
						<td class="price-type">    
							<select name="i_options_price_type[]">
								<option selected="selected" value="">Fixed</option>
								<option value="percentage">Percentage</option>
							</select>
						</td>
						<td class="action-cell">
							<a href="javascript:void(0)" onclick="thwepoAddNewOptionRow(this)" class="btn btn-tiny btn-primary" title="Add new option">+</a><a href="javascript:void(0)" onclick="thwepoRemoveOptionRow(this)" class="btn btn-tiny btn-danger" title="Remove option">x</a><span class="btn btn-tiny sort ui-sortable-handle"></span>
						</td>
					</tr>
				</tbody></table>            	
			</td>
		</tr>
        <?php
	}
	*/


	function notusing(){
		?>
		<!--<div class="container-fluid">
				<div class="row">
				    <div class="col-sm-1">
				    	<img src="http://localhost/thpro/wp-content/plugins/woocommerce-email-customizer/admin/assets/images/header.svg" alt="Header">
				      	<p>Text</p>
				    </div>
				    <div class="col-sm-1">
				    	<img src="http://localhost/thpro/wp-content/plugins/woocommerce-email-customizer/admin/assets/images/header.svg" alt="Header">
				      <p>Hidden</p>
				    </div>
				    <div class="col-sm-1">
				    	<img src="http://localhost/thpro/wp-content/plugins/woocommerce-email-customizer/admin/assets/images/header.svg" alt="Header">
				      <p>Number</p>
				    </div>
				    <div class="col-sm-1">
				    	<img src="http://localhost/thpro/wp-content/plugins/woocommerce-email-customizer/admin/assets/images/header.svg" alt="Header">
				      <p>Telephone</p>
				    </div>
				    <div class="col-sm-1">
				    	<img src="http://localhost/thpro/wp-content/plugins/woocommerce-email-customizer/admin/assets/images/header.svg" alt="Header">
				      <p>Password</p>
				    </div>
				    <div class="col-sm-1">
				    	<img src="http://localhost/thpro/wp-content/plugins/woocommerce-email-customizer/admin/assets/images/header.svg" alt="Header">
				      <p>Textarea</p>
				    </div>
				    <div class="col-sm-1">
				    	<img src="http://localhost/thpro/wp-content/plugins/woocommerce-email-customizer/admin/assets/images/header.svg" alt="Header">
				      	<p>Text</p>
				    </div>
				    <div class="col-sm-1">
				    	<img src="http://localhost/thpro/wp-content/plugins/woocommerce-email-customizer/admin/assets/images/header.svg" alt="Header">
				      <p>Hidden</p>
				    </div>
				    <div class="col-sm-1">
				    	<img src="http://localhost/thpro/wp-content/plugins/woocommerce-email-customizer/admin/assets/images/header.svg" alt="Header">
				      <p>Number</p>
				    </div>
				    <div class="col-sm-1">
				    	<img src="http://localhost/thpro/wp-content/plugins/woocommerce-email-customizer/admin/assets/images/header.svg" alt="Header">
				      <p>Telephone</p>
				    </div>
				    <div class="col-sm-1">
				    	<img src="http://localhost/thpro/wp-content/plugins/woocommerce-email-customizer/admin/assets/images/header.svg" alt="Header">
				      <p>Password</p>
				    </div>
				    <div class="col-sm-1">
				    	<img src="http://localhost/thpro/wp-content/plugins/woocommerce-email-customizer/admin/assets/images/header.svg" alt="Header">
				      <p>Textarea</p>
				    </div>
				</div>
			</div>
			-->
		<?php
	}
}

endif;