<?php
/**
 * 
 *
 * @author      ThemeHiGH
 * @category    Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Condition')):

class WEPOF_Condition {
	const EQUALS = 'equals';
	const NOT_EQUALS = 'not_equals';
	const IN = 'in';
	const NOT_IN = 'not_in';
	//const EMPTY = 'empty';
	//const NOT_EMPTY = 'not_empty';
	
	const PRODUCT = 'product';
	const CATEGORY = 'category';
	const TAG = 'tag';
		
	public $subject = '';
	public $comparison = '';
	public $value = '';
		
	public function __construct() {
		
	}	
	
	public function is_valid(){
		if(!empty($this->subject) && !empty($this->comparison) && is_array($this->value)){
			return true;
		}
		return false;
	}
	
	public function is_satisfied($product, $categories, $tags=false){		
		$satisfied = true;
		if($this->is_valid()){
			$values = $this->value;
			
			if($this->subject == self::PRODUCT){
				$product = THWEPOF_Utils::get_original_product_id($product);

				if($this->comparison == self::EQUALS && !in_array('-1', $values)) {
					if(!in_array($product, $values)){
						return false;
					}
				}else if($this->comparison == self::NOT_EQUALS){
					if(in_array($product, $values)){
						return false;
					}
				}
			}else if($this->subject == self::CATEGORY){
				$commonCategories = array_intersect($categories, $values);
				
				if($this->comparison == self::EQUALS && !in_array('-1', $values)) {
					if(empty($commonCategories)){
						return false;
					}
				}else if($this->comparison == self::NOT_EQUALS){
					if(!empty($commonCategories)){
						return false;
					}
				}
			}else if($this->subject == self::TAG){
				$commonTags = array_intersect($tags, $values);
				
				if($this->comparison == self::EQUALS && !in_array('-1', $values)) {
					if(empty($commonTags)){
						return false;
					}
				}else if($this->comparison == self::NOT_EQUALS){
					if(!empty($commonTags)){
						return false;
					}
				}
			}
		}
		return $satisfied;
	}
	
	public function set_subject($subject){
		$this->subject = $subject;
	}	
	public function get_subject(){
		return $this->subject;
	}
	
	public function set_comparison($comparison){
		$this->comparison = $comparison;
	}	
	public function get_comparison(){
		return $this->comparison;
	}
	
	public function set_value($value){
		$this->value = $value;
	}	
	public function get_value(){
		return $this->value;
	}
}

endif;