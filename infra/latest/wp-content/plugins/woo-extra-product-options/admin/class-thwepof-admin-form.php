<?php
/**
 * Woo Extra Product Options - Admin Forms
 *
 * @author    ThemeHigh
 * @category  Admin
 */

if(!defined('WPINC')){ die; }

if(!class_exists('THWEPOF_Admin_Form')):

abstract class THWEPOF_Admin_Form {
	public $cell_props = array();
	public $cell_props_CP = array();
	public $cell_props_CB = array();

	public function __construct() {
		$this->init_constants();
	}

	private function init_constants(){
		$this->cell_props = array( 
			'label_cell_props' => 'class="label"', 
			'input_cell_props' => 'class="field"',
			'input_width' => '260px',
		);
		$this->cell_props_CP = array(
			'label_cell_props' => 'class="label"', 
			'input_cell_props' => 'class="field thwepof_td_colorpick"',
			'input_width' => '223px',
		);
		
		$this->cell_props_CB = array( 
			'label_props' => 'style="margin-right: 40px;"', 
		);
	}
	
	public function get_html_text_tags(){
		return array( 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6', 'p'  => 'p', 'div' => 'div', 'span' => 'span', 'label' => 'label');
	}

	public function get_html_title_tags(){
		return array( 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3');
	}

	public function get_available_positions(){
		return array(
			'woo_before_add_to_cart_button'	=> __('Before Add To Cart Button', 'woo-extra-product-options'),
			'woo_after_add_to_cart_button' 	=> __('After Add To Cart Button', 'woo-extra-product-options')
		);
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
				
			}if($ftype == 'number'){
				$field_html = $this->render_form_field_element_number($field, $atts);
				
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

		$input_class = '';
		if($ftype == 'text'){
			$input_class = 'thwepo-inputtext';
		}if($ftype == 'number'){
			$input_class = 'thwepo-input-number';
		}else if($ftype == 'select'){
			$input_class = 'thwepo-select';
		}else if($ftype == 'multiselect'){
			$input_class = 'thwepo-select thwepo-enhanced-multi-select';
		}else if($ftype == 'colorpicker'){
			$input_class = 'thwepo-color thpladmin-colorpick';
		}
		
		if($ftype == 'multiselect'){
			$args['input_name_suffix'] = $args['input_name_suffix'].'[]';
		}

		$fname  = $args['input_name_prefix'].$field['name'].$args['input_name_suffix'];
		$fvalue = isset($field['value']) ? esc_html($field['value']) : '';
		
		$input_width  = $args['input_width'] ? 'width:'.$args['input_width'].';' : '';
		$field_props  = 'name="'. $fname .'" value="'. $fvalue .'" style="'. $input_width .'"';
		$field_props .= !empty($input_class) ? ' class="'. $input_class .'"' : '';
		$field_props .= ( isset($field['placeholder']) && !empty($field['placeholder']) ) ? ' placeholder="'.$field['placeholder'].'"' : '';
		$field_props .= ( isset($field['onchange']) && !empty($field['onchange']) ) ? ' onchange="'.$field['onchange'].'"' : '';

		if($ftype == 'number'){
			$field_props .= 'min="0"';
		}
		
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

	private function render_form_field_element_number($field, $atts = array() ){
		$field_html = '';
		if($field && is_array($field)){
			$field_props = $this->prepare_form_field_props($field, $atts);
			$field_html = '<input type="number" '. $field_props .' />';
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
		
			$fvalue = isset($field['value']) ? $field['value'] : '';
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
				$field_html .= '<option value="'. trim($value) .'" '.$selected.'>'. __($label, 'woo-extra-product-options') .'</option>';
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
            $field_html .= '<input type="text" '. $field_props .' class="thpladmin-colorpick" autocomplete="off"/>';
		}
		return $field_html;
	}
	
	public function render_form_fragment_tooltip($tooltip = false){
		$tooltip_html = '';

		if($tooltip){
			$tooltip_html = '<a href="javascript:void(0)" title="'. $tooltip .'" class="thwepof_tooltip"><img src="'. THWEPOF_URL.'admin/assets/css/help.png" title=""/></a>';
		}
		?>
		<td class="tip" style="width: 26px; padding:0px;"><?php echo $tooltip_html; ?></td>
		<?php
	}

	public function render_form_fragment_rules($type="field"){
		?>
		<tr>
        	<td class="">
                <?php _e('Show if all below conditions are met.', 'woo-extra-product-options'); ?>
            </td>
        </tr>
        <tr>                
            <td colspan="6">
            	<table id="thwepo_conditional_rules" width="100%"><tbody>
                    <tr class="thwepo_rule_set_row">                
                        <td>
                            <table class="thwepo_rule_set" width="100%"><tbody>
                                <tr class="thwepo_rule_row">
                                    <td>
                                        <table class="thwepo_rule" width="100%" style=""><tbody>
                                            <tr class="thwepo_condition_set_row">
                                                <td>
                                                    <table class="thwepo_condition_set" width="100%" style=""><tbody>
                                                        <tr class="thwepo_condition">
                                                            <td class="operand-type">
                                                                <select name="i_rule_subject" onchange="thwepofRuleOperandTypeChangeListner(this)">
                                                                    <option value=""></option>
                                                                    <option value="product"><?php _e('Product', 'woo-extra-product-options'); ?></option>
                                                                    <option value="category"><?php _e('Category', 'woo-extra-product-options'); ?> </option>
                                                                    <option value="tag"><?php _e('Tag', 'woo-extra-product-options'); ?></option>
                                                                </select>
                                                            </td>
                                                            <td class="operator">
                                                                <select name="i_rule_comparison">
                                                                    <option value=""></option>
                                                                    <option value="equals"><?php _e('Equals to/ In', 'woo-extra-product-options'); ?></option>
                                                                    <option value="not_equals"><?php _e('Not Equals to/ Not in', 'woo-extra-product-options'); ?></option>
                                                                </select>
                                                            </td>
                                                            <td class="operand thwepo_condition_value">
                                                            	<input type="text" name="i_rule_value" >
                                                            </td>
                                                            <td class="actions">
                                                                <a href="javascript:void(0)" class="thwepof_logic_link" onclick="thwepofAddNewConditionRow(this, 1)" title="">AND</a>
                                                                <a href="javascript:void(0)" class="thwepof_logic_link" onclick="thwepofAddNewConditionRow(this, 2)" title="">OR</a>
                                                                <a href="javascript:void(0)" class="thwepof_delete_icon dashicons dashicons-no" onclick="thwepofRemoveRuleRow(this)" title="Remove"></a>
                                                            </td>
                                                        </tr>
                                                    </tbody></table>
                                                </td>
                                            </tr>
                                        </tbody></table>
                                    </td>
                                </tr>
                            </tbody></table>            	
                        </td>            
                    </tr> 
        		</tbody></table>
        	</td>
        </tr>
        <?php
	}

	public function render_field_form_fragment_product_list(){
		?>
        <div id="thwepo_product_select" style="display:none;">
        <select multiple="multiple" name="i_rule_value" data-placeholder="<?php _e('Click to select products', 'woo-extra-product-options'); ?>" class="thwepof-enhanced-multi-select1 thwepof-operand thwepof-product-select" style="width:98%;" value="">
        </select>
        </div>
        <?php
	}

	public function render_field_form_fragment_category_list(){		
		$categories = apply_filters( "thwepof_load_products_cat", array() );

		if(!empty($categories)){
			array_unshift( $categories , array( "id" => "-1", "title" => "All Categories" ));
			?>
	        <div id="thwepo_product_cat_select" style="display:none;">
	        <select multiple="multiple" name="i_rule_value" data-placeholder="<?php _e('Click to select categories', 'woo-extra-product-options'); ?>" class="thwepof-enhanced-multi-select thwepo-operand" style="width:98%;" value="">
				<?php 	
	                foreach($categories as $category){
	                    echo '<option value="'. $category["id"] .'" >'. $category["title"] .'</option>';
	                }
	            ?>
	        </select>
	        </div>
	        <?php
	    }else{
	    	?>
	        <div id="thwepo_product_cat_select" style="display:none;">
	        <input type="text" name="i_rule_value" class="thwepo-operand" value="">
	        </div>
	        <?php
	    }
	}

	public function render_field_form_fragment_tag_list(){		
		$tags = $this->load_product_tags();
		
		if(!empty($tags)){
			array_unshift( $tags , array( "id" => "-1", "title" => "All Tags" ));
			?>
	        <div id="thwepo_product_tag_select" style="display:none;">
	        <select multiple="multiple" name="i_rule_value" data-placeholder="<?php _e('Click to select tags', 'woo-extra-product-options'); ?>" class="thwepof-enhanced-multi-select thwepo-operand" style="width:98%;" value="">
				<?php 	
	                foreach($tags as $tag){
	                    echo '<option value="'. $tag["id"] .'" >'. $tag["title"] .'</option>';
	                }
	            ?>
	        </select>
	        </div>
	        <?php
	    }else{
	    	?>
	        <div id="thwepo_product_tag_select" style="display:none;">
	        <input type="text" name="i_rule_value" class="thwepo-operand" value="">
	        </div>
	        <?php
	    }
	}

	public function load_product_tags($only_slug = false){
		$product_tags = $this->load_product_terms('product_tag', $only_slug);
		return $product_tags;
	}

	public function load_product_terms($taxonomy, $only_slug = false){
		$product_terms = array();
		$pterms = get_terms($taxonomy, 'orderby=count&hide_empty=0');

		$ignore_translation = true;
		$is_wpml_active = THWEPOF_Utils::is_wpml_active();
		if($is_wpml_active && $ignore_translation){
			$default_lang = THWEPOF_Utils::off_wpml_translation();
		}

		if(is_array($pterms)){
			foreach($pterms as $term){
				$dterm = $term;

				if($is_wpml_active && $ignore_translation){
					$dterm = THWEPOF_Utils::get_default_lang_term($term, $taxonomy, $default_lang);
				}

				if($only_slug){
					$product_terms[] = $dterm->slug;
				}else{
					$product_terms[] = array("id" => $dterm->slug, "title" => $dterm->name);
				}
			}
		}

		if($is_wpml_active && $ignore_translation){
			THWEPOF_Utils::may_on_wpml_translation($default_lang);
		}

		return $product_terms;
	}

	/*----- Tab Title -----*/
	public function render_form_tab_main_title($title){
		?>
		<main-title classname="main-title">
			<button class="device-mobile btn--back Button">
				<i class="button-icon button-icon-before i-arrow-back"></i>
			</button>
			<span class="device-mobile main-title-icon text-primary"><i class="i-check drishy"></i><?php echo $title; ?></span>
			<span class="device-desktop"><?php echo $title; ?></span>
		</main-title>
		<?php
	}

	/*----- Form Element Row -----*/
	public function render_form_elm_row($field, $args=array()){
		$name = isset($field['name']) ? $field['name'] : '';
		$class = 'form_field_'.$name;

		?>
		<tr class="<?php echo $class; ?>">
			<?php $this->render_form_field_element($field, $this->cell_props); ?>
		</tr>
		<?php
	}

	public function render_form_elm_row_cb($field, $args=array()){
		$name = isset($field['name']) ? $field['name'] : '';
		$class = 'form_field_'.$name;

		?>
		<tr class="<?php echo $class; ?>">
			<td colspan="2"></td>
			<td class="field">
	    		<?php $this->render_form_field_element($field, $this->cell_props_CB, false); ?>
	    	</td>
	    </tr>
		<?php
	}

	public function render_form_elm_row_cp($field, $args=array()){
		$name = isset($field['name']) ? $field['name'] : '';
		$class = 'form_field_'.$name;
		
		?>
		<tr class="<?php echo $class; ?>">
	    	<?php $this->render_form_field_element($field, $this->cell_props_CP); ?>
	    </tr>
		<?php
	}
}

endif;