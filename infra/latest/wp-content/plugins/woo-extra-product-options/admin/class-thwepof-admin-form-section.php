<?php
/**
 * Woo Extra Product Options - Section Forms
 *
 * @author    ThemeHigh
 * @category  Admin
 */

if(!defined('WPINC')){	die; }

if(!class_exists('THWEPOF_Admin_Form_Section')):

class THWEPOF_Admin_Form_Section extends THWEPOF_Admin_Form{
	private $section_props = array();

	public function __construct() {
		parent::__construct();
		$this->section_props = $this->get_section_form_props();
	}
	
	public function get_section_form_props(){
		$positions = $this->get_available_positions();
		$html_text_tags = $this->get_html_text_tags();
		
		return array(
			'name' 		 => array('name'=>'name', 'label'=>__('Name/ID', 'woo-extra-product-options'), 'type'=>'text', 'required'=>1),
			'position' 	 => array('name'=>'position', 'label'=>__('Display Position', 'woo-extra-product-options'), 'type'=>'select', 'options'=>$positions, 'required'=>1),
			//'box_type' 	 => array('name'=>'box_type', 'label'=>'Box Type', 'type'=>'select', 'options'=>$box_types),
			'order' 	 => array('name'=>'order', 'label'=>__('Display Order', 'woo-extra-product-options'), 'type'=>'number'),
			'cssclass' 	 => array('name'=>'cssclass', 'label'=>__('CSS Class', 'woo-extra-product-options'), 'type'=>'text'),
			'show_title' => array('name'=>'show_title', 'label'=>__('Show section title in product page.', 'woo-extra-product-options'), 'type'=>'checkbox', 'value'=>'yes', 'checked'=>1),
			
			'title_cell_with' => array('name'=>'title_cell_with', 'label'=>__('Col-1 Width', 'woo-extra-product-options'), 'type'=>'text', 'value'=>''),
			'field_cell_with' => array('name'=>'field_cell_with', 'label'=>__('Col-2 Width', 'woo-extra-product-options'), 'type'=>'text', 'value'=>''),
			
			'title' 		   => array('name'=>'title', 'label'=>__('Title', 'woo-extra-product-options'), 'type'=>'text', 'required'=>1),
			'title_type' 	   => array('name'=>'title_type', 'label'=>__('Title Type', 'woo-extra-product-options'), 'type'=>'select', 'value'=>'h3', 'options'=>$html_text_tags),
			'title_class' 	   => array('name'=>'title_class', 'label'=>__('Title Class', 'woo-extra-product-options'), 'type'=>'text'),
			'title_color' 	   => array('name'=>'title_color', 'label'=>__('Title Color', 'woo-extra-product-options'), 'type'=>'colorpicker'),
		);
	}

	public function output_section_forms(){
		?>
        <div id="thwepof_section_form_pp" class="thpladmin-modal-mask">
          <?php $this->output_popup_form_section(); ?>
        </div>
        <?php
	}
	
	/*****************************************/
	/********** POPUP FORM WIZARD ************/
	/*****************************************/

	private function output_popup_form_section(){
		?>
		<div class="thpladmin-modal thwepof-section-pp">
			<div class="modal-container">
				<span class="modal-close" onclick="thwepofCloseModal(this)">×</span>
				<div class="modal-content">
					<div class="modal-body">
						<div class="form-wizard wizard">
							<aside>
								<side-title class="wizard-title"><?php _e('Save Section', 'woo-extra-product-options'); ?></side-title>
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
								<form method="post" id="thwepof_section_form" action="">
									<input type="hidden" name="s_action" value="" >
									<input type="hidden" name="s_name" value="" >
									<input type="hidden" name="s_name_copy" value="" >
									<input type="hidden" name="i_position_old" value="" >
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
									<?php wp_nonce_field( 'save_section_property', 'save_section_nonce' ); ?>
								</form>
							</main>
							<footer>
								<span class="Loader"></span>
								<div class="btn-toolbar">
									<button class="save-btn pull-right btn btn-primary" onclick="thwepofSaveSection(this)">
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
			<div class="err_msgs"></div>
			<table class="thwepof_pp_table">
				<?php
				$this->render_form_elm_row($this->section_props['name']);
				$this->render_form_elm_row($this->section_props['title']);
				$this->render_form_elm_row($this->section_props['position']);
				//$this->render_form_elm_row($this->section_props['cssclass']);
				$this->render_form_elm_row($this->section_props['order']);
				$this->render_form_elm_row($this->section_props['title_cell_with']);
				$this->render_form_elm_row($this->section_props['field_cell_with']);

				$this->render_form_elm_row_cb($this->section_props['show_title']);

				
				//$this->render_form_elm_row($this->section_props['title_type']);
				//$this->render_form_elm_row($this->section_props['title_class']);
				//$this->render_form_elm_row_cp($this->section_props['title_color']);
				?>
			</table>
		</div>
		<?php
	}

	/*----- TAB - Display Details -----*/
	private function render_form_tab_display_details(){
		$this->render_form_tab_main_title(__('Display Settings', 'woo-extra-product-options'));

		?>
		<div style="display: inherit;" class="data-panel-content">
			<table class="thwepof_pp_table">
				<?php
				$this->render_form_elm_row($this->section_props['cssclass']);
				$this->render_form_elm_row($this->section_props['title_class']);
				$this->render_form_elm_row($this->section_props['title_type']);
				$this->render_form_elm_row_cp($this->section_props['title_color']);
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
				$this->render_form_fragment_rules('section'); 
				?>
			</table>
		</div>
		<?php
	}
}

endif;