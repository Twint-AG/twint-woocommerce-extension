<?php
/**
 * Woo Extra Product Options - Field Editor
 *
 * @author    ThemeHiGH
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('THWEPOF_Admin_Settings_Pro')):
class THWEPOF_Admin_Settings_Pro extends THWEPOF_Admin_Settings {
	protected static $_instance = null;

	private $section_form = null;
	private $field_form = null;

	private $field_props = array();

	public function __construct() {
		parent::__construct('pro');
		$this->page_id = 'pro';

		// $this->section_form = new THWEPOF_Admin_Form_Section();
		// $this->field_form = new THWEPOF_Admin_Form_Field();
		// $this->field_props = $this->field_form->get_field_form_props();

		// add_filter( 'woocommerce_attribute_label', array($this, 'woo_attribute_label'), 10, 2 );
		
		// //add_filter('thwepof_load_products', array($this, 'load_products'));
		// add_filter('thwepof_load_products_cat', array($this, 'load_products_cat'));
	}

	public static function instance() {
		if(is_null(self::$_instance)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	public function render_page(){
		$this->render_tabs();
		$this->render_content();
	}

	private function render_content(){
		$thwepof_since = get_option('thwepof_since');
        $now = time();
        $render_time  = apply_filters('thwepof_get_pro_button_offer', 6 * MONTH_IN_SECONDS);
        $render_time = $thwepof_since + $render_time;

        if($now > $render_time){
            $url = "https://www.themehigh.com/?edd_action=add_to_cart&download_id=17&cp=lyCDSy_wepo&utm_source=free&utm_medium=premium_tab&utm_campaign=wepo_upgrade_link";
        }else{
            $url = "https://www.themehigh.com/product/woocommerce-extra-product-options/?utm_source=free&utm_medium=premium_tab&utm_campaign=wepo_upgrade_link";
        }

        ?>
        <div class="th-nice-box">
            <div class="th-ad-banner">
                <div class="th-ad-content">
                    <div class="th-ad-content-container">
                        <div class="th-ad-content-desc">
                            <p>Unlock more features & provide your customers with a fully personalized and unique shopping page experience.</p>  
                        </div>
                        <div class="upgrade-pro-btn-div">
                            <a class="btn-upgrade-pro above" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" onclick="this.classList.add('clicked')">Upgrade to Pro</a>
                        </div> 
                    </div>
                </div>
                <div class="th-ad-terms">
                    <div class="th-ad-guarantee">
                        <img src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/guarantee.svg'); ?>">
                    </div>
                    <p class="th-ad-term-head">30 DAYS MONEY BACK GUARANTEE
                    <span class="th-ad-term-desc">100% Refund, if you are not satisfied with your purchase.</span></p>
                </div>
            </div>
            <div class="th-wrapper-main">
                <div class="th-try-demo">
                    <h3 class="trydemo-heading">Exclusive Perks of Upgrading to the Best</h3>
                    <p class="try-demo-desc">With the premium version of the Extra Product Options for WooCommerce Plugin, you can effortlessly define products with the most-sought fields and features that are inherited from world-class WooCommerce stores.</p>
                    <div class="th-pro-btn"><a class="btn-get-pro" onclick="this.classList.add('clicked')" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" ><?php echo __('Get Pro', 'woo-extra-product-options');?></a><a class="btn-try-demo" href="https://flydemos.com/wepo/?utm_source=free&utm_medium=banner&utm_campaign=trydemo"
                        target="_blank" rel="noopener noreferrer" onclick="this.classList.add('clicked')" ><?php echo __('Try Demo', 'woo-extra-product-options');?></a></div>
                    <!-- <img class="vedio" src="" alt="no img">  ADD vedio tutorial-->
                </div>
                <section class="th-wepo-key-feature">
                    
                    <h3 class="th-feautre-head">Key Features Of Extra Product Options Pro</h3>
                
                    <p class="th-feautre-desc">Some of the advanced features in the Extra Product Options premium plugin are listed below.</p>
                    <div class="th-wepo-feature-list-ul">
                        <ul class="th-wepo-feature-list">
                            <li>27 extra product fields</li>
                            <li>Add, edit, rearrange, duplicate & delete fields and sections</li>
                            <li>Display fields & sections conditionally</li>
                            <li>Show sections in tabular & accordion layouts</li>
                            <li>7 different pricing options including discounts</li>
                            <li>Option to add extra price as flat fee</li>
                            <li>Display the price table on the product page</li>
                            <li>Product group, Image group,  & Color palette field types with multi select property</li>
                            <li>Single & multiple file upload options</li>
                            <li>Add date & time range picker for the booking websites</li>
                            <li>Add custom validations</li>
                            <li>Add confirm field validations</li>
                            <li>Manage field display in the cart, checkout, order details pages & emails</li>
                            <li>Compatibility with popular themes & plugins</li>
                            <li>WPML compatibility</li>
                            <li>Developer friendly with custom hooks</li>
                            <li>Create your own custom classes for styling the fields & sections</li>
                        </ul>   
                    </div>
                    <div class="th-get-pro">
                        <div class="th-get-pro-img">
                            <img src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/rocket.svg'); ?>">
                        </div>
                        <div class='th-wrapper-get-pro'>
                            <div class="th-get-pro-desc">
                                <p class="th-get-pro-desc-head">Switch to the Pro version and be a part of our limitless features
                                    <!-- <span class="th-get-pro-desc-contnt"><?php //echo __('Switch to a world of seamless checkout with an ocean of possibilities to customize.', 'woo-extra-product-options');?></span> -->
                                </p>
                            </div>
                            <div class="th-get-pro-btn">
                                <a class="btn-upgrade-pro orange" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" onclick="this.classList.add('clicked')" >Get Pro</a>
                            </div>
                        </div>
                    </div>
                </section>
                <div class="th-star-support">
                    <div class="th-user-star">
                        <p class="th-user-star-desc">30000+ Users & 160+ 5 Star Reviews&nbsp;</p>
                        <div class="th-user-star-img">
                            <img src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/star.svg'); ?>">
                        </div>
                    </div>
                    <div class="th-pro-support">
                        <div class="th-pro-support-img">
                            <img src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/support.svg'); ?>">
                        </div>
                        <p class="th-pro-support-desc">Enjoy the <em>premium support</em> experience with our dedicated support team.</p>
                    </div>
                    <div class="th-hor-line"></div>
                </div>
                
                <section class="th-field-types">
                    <h3 class="th-field-types-head">Available Field types</h3>
                    <p class="th-field-types-desc">Following are the custom product field types available in the Extra Product Options Premium plugin.</p>
                    <div class="th-wepo-field-type-img">
                        <div class="th-fields">
                            <ul class="th-wepo-field-list">
                                <li><?php echo __('Text', 'woo-extra-product-options');?></li>
                                <li><?php echo __('Hidden', 'woo-extra-product-options');?></li>
                                <li><?php echo __('Password', 'woo-extra-product-options');?></li>
                                <li><?php echo __('Telephone', 'woo-extra-product-options');?></li>
                                <li><?php echo __('Number', 'woo-extra-product-options');?></li>
                                <li><?php echo __('Email', 'woo-extra-product-options');?></li>
                                <li><?php echo __('URL', 'woo-extra-product-options');?></li>
                                <li><?php echo __('Textarea', 'woo-extra-product-options');?></li>
                                <li><?php echo __('Slider/Range', 'woo-extra-product-options');?></li>
                                <li><?php echo __('Switch', 'woo-extra-product-options');?></li>
                                <li><?php echo __('Radio', 'woo-extra-product-options');?></li>
                                <li><?php echo __('Select', 'woo-extra-product-options');?></li>
                                <li><?php echo __('Multiselect ', 'woo-extra-product-options');?><span class="th-crown"><img src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/crown.svg'); ?>"></span></li>
                                <li><?php echo __('Checkbox', 'woo-extra-product-options');?></li>
                                <li><?php echo __('Checkbox Group', 'woo-extra-product-options');?></li>
                                <li><?php echo __('Date Picker', 'woo-extra-product-options');?></li>
                                <li><?php echo __('Time Picker', 'woo-extra-product-options');?><span class="th-crown"><img src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/crown.svg'); ?>"></span></li>
                                <li><?php echo __('Date & Time Range Picker', 'woo-extra-product-options');?><span class="th-crown"><img src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/crown.svg'); ?>"></span></li>
                                <li><?php echo __('Color Picker', 'woo-extra-product-options');?></li>
                                <li><?php echo __('Color Palette', 'woo-extra-product-options');?><span class="th-crown"><img src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/crown.svg'); ?>"></span></li>
                                <li><?php echo __('Image Group', 'woo-extra-product-options');?><span class="th-crown"><img src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/crown.svg'); ?>"></span></li>
                                <li><?php echo __('Product Group', 'woo-extra-product-options');?><span class="th-new"><img src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/new.png'); ?>"></span><span class="th-crown"><img src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/crown.svg'); ?>"></span></li>
                                <li><?php echo __('File Upload', 'woo-extra-product-options');?><span class="th-crown"><img src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/crown.svg'); ?>"></span></li>
                                <li><?php echo __('Heading', 'woo-extra-product-options');?></li>
                                <li><?php echo __('Paragraph/Label', 'woo-extra-product-options');?></li>
                                <li><?php echo __('HTML', 'woo-extra-product-options');?><span class="th-crown"><img src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/crown.svg'); ?>"></span></li>
                                <li><?php echo __('Separator', 'woo-extra-product-options');?></li>
                            </ul>
                        </div>
                        <div class="th-fields-img">
                            <img src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/fields.png'); ?>">
                        </div>
                    </div>
                </section>
                <div class="th-fields-section-function">
                    <div class="th-section-function">   
                        <section class="th-display-rule-section" style="margin-right: 6px;">
                            <div class="th-wepo-pro">
                                <img src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/pro.svg'); ?>">
                            </div>
                            <div class="th-display-rule-section-head">Advanced Pricing Options </div>
                            <p class="th-display-rule-section-desc">Modify the existing product prices by choosing from flexible pricing methods provided by the Extra Product Options plugin.</p>
                            <ul class="th-display-section-list">
                                <li><b>Fixed Pricing:</b> A fixed amount will be added to the total price.</li>
                                <li><b>Custom Pricing:</b> A value entered by the user will be added to the total price. Use Case Example: This option helps to receive donations.</li>
                                <li><b>Percentage of Product Pricing:</b> A specific percentage of product price is added to the total price</li>
                                <li><b>Dynamic Pricing:</b> You can set a price for the ‘n’ number of units. This additional price will be added to the product price based on the number of units.</li>
                                <li><b>Dynamic(Exclude base price):</b> Same as Dynamic pricing, but instead of adding the Extra price, it replaces the product price.</li>
                                <li><b>Character Count:</b> You can add an extra amount to the product price based on the number of characters the shopper provides.</li>
                                <li><b>Custom Formula:</b> An additional price can be charged to the product price based on the custom formula you set.</li>
                            </ul>
                            <div class="additional_note">
                               <span><b>Note:</b> You can even use -ve price value (eg:-20) for applying the discount to the product price.</span>
                            </div>
                        </section>
                    </div>
                    <div class="th-fields-function">
                        <section class="th-display-rule-fields th-display-rule-section th-right-box">
                            <div class="th-wepo-pro">
                                <img src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/pro.svg'); ?>">
                            </div>
                            <h3 class="th-display-rule-fields-head">Display Rules for Fields and Sections</h3>
                            <p class="th-display-rule-fields-desc">You will be able to display fields and sections conditionally on your WooCommerce product page using the display rules feature.</p>
                            <p class="th-display-rule-fields-desc">The available set of conditions are based on the:</p>
                            <div class="th-dispaly-rule-list">
                                <ul class="th-display-field-list">
                                    <li>Products</li>
                                    <li>Categories</li>
                                    <li>Tags</li>
                                    <li>User Roles</li>
                                    <li>Product Variation</li>
                                    <li>Product Quantity</li>
                                    <li>Other Custom Field Values</li>
                                </ul>
                            </div>
                        </section>

                        <section class="th-price-fields">
                            <div class="th-wepo-pro">
                                <img src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/pro.svg');?>">
                            </div>
                            <h3 class="th-price-fields-head">Show Pricing Table </h3>
                            <p class="th-price-fields-desc">Display additional charges for each option in a tabular style, explaining the base price and the extra amount added.</p>
                            <div class="th-price-table-img">
                                <img src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/price-table.png'); ?>">
                            </div>
                        </section>
                    </div>
                </div>
                <div class="th-fields-section-function">
                    <section class="th-layouts-section">
                        <div class="th-wepo-pro">
                            <img src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/pro.svg');?>">
                        </div>
                        <h3 class="th-price-fields-head">Display Sections in 3 Different Layouts</h3>
                        <p class="th-price-fields-desc">Choose the layout style for the sections you created from 3 different options like the default, tabs and accordion.</p>
                        <div class="th-layouts-img">
                            <img src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/layouts.png'); ?>">
                        </div>
                    </section>
                </div>
                <div class="th-review-section">
                    <div class="review-image-section">
                        <div class="review-quote-img">
                            <img src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/reviewquotes.png'); ?>">
                        </div>
                    </div>
                    <div id="indicator" class="th-review-navigator" style="text-align:center">
                        <a class="prev" onclick='plusSlides(-1)'></a>
                        <a class="next" onclick='plusSlides(1)'></a>
                        <span class="dot th-review-nav-btn" onclick="currentSlide(1)"></span>
                        <span class="dot th-review-nav-btn" onclick="currentSlide(2)"></span>
                        <span class="dot th-review-nav-btn" onclick="currentSlide(3)"></span>
                        <span class="dot th-review-nav-btn" onclick="currentSlide(4)"></span>
                        <span class="dot th-review-nav-btn" onclick="currentSlide(5)"></span>
                    </div>
                    <div class="th-user-review-section">
                        <div class="th-review-quote">
                        <img src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/quotes.svg'); ?>">
                        </div>
                        <div class="th-user-review">
                            <h3 class="th-review-heading">Excellent plugin, and fantastic support</h3>
                            <p class="th-review-content">This is an excellent plugin.<br>It did everything I needed.<br>And when I needed help, I was able to count on a fantastic support team.<br>Excellent reception.<br>Very worth the investment.</p>
                            <p class="th-review-user-name">Guilherme Souza</p>
                        </div>
                    </div>
                </div>
                <section class="th-faq-tab">
                    <div class="th-faq-desc">
                        <h3>FAQ's</h3>
                        <p class="th-faq-para">Don't worry! Here are the answer to your frequent doubt and questions. If you feel you haven't been answered relevantly, feel free to contact our efficient support team.</p>
                    
                    </div>
                    <div class="th-faq-qstns" >
                        <button class="accordion" onclick="thwepofAccordionexpand(this)">
                            <div class="accordion-qstn">
                                <p>How to upgrade to the premium version of the plugin and how can I apply the license key to activate the pro plugin?</p>
                                <img class="accordion-img" src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/blck-down-arrow.svg'); ?>">
                                <img class="accordion-img-opn" src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/blue-down-arrow.svg'); ?>">
                            </div>
                            <div class="panel">
                                <p>Please follow the steps given in the below links to purchase the plugin and activate the license.</p>
                                <p>
                                    <a href="https://www.themehigh.com/docs/download-and-install-your-plugin/" target="_blank" rel="noopener noreferrer">https://www.themehigh.com/docs/download-and-install-your-plugin/</a><br>
                                </p>
                                <p class="th-faq-links">
                                    <a href="https://www.themehigh.com/docs/manage-license/" target="_blank" rel="noopener noreferrer">https://www.themehigh.com/docs/manage-license/</a><br>
                                </p>
                                <p class="th-faq-notes">
                                    Note: Please confirm whether all the fields that you had created in the free version have been migrated to the premium version after upgrading. If so you can safely deactivate and delete the free version from your site.
                                </p>
                            </div>
                        </button>                   
                        <button class="accordion" onclick="thwepofAccordionexpand(this)">
                            <div class="accordion-qstn">
                                <p>Do I have to keep both the free version and the pro version after buying the pro version?</p>
                                <img class="accordion-img" src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/blck-down-arrow.svg'); ?>">
                                <img class="accordion-img-opn" src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/blue-down-arrow.svg'); ?>">
                            </div>
                            <div class="panel">
                                <p class="th-faq-answer">Please note that free and premium versions are different plugins entirely. So, you can deactivate and remove the free version of the plugin from your website, if you start using the premium version.</p>
                            </div>
                        </button>
                        
                        <button class="accordion" onclick="thwepofAccordionexpand(this)">
                            <div class="accordion-qstn">
                                <p>How to migrate our configuration from the free version to the pro version?</p>
                                <img class="accordion-img" src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/blck-down-arrow.svg'); ?>">
                                <img class="accordion-img-opn" src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/blue-down-arrow.svg'); ?>">
                            </div>
                            <div class="panel">
                                <p class="th-faq-answer">At the time when you upgrade the plugin from the free to the premium version, the free plugin settings will get automatically migrated to the premium version.
     
                                Please confirm whether all the fields that you created in the free version have been migrated to the premium version after upgrading. If so you can safely deactivate and delete the free version from your site.</p>
                            </div>
                        </button>
                        <button class="accordion" onclick="thwepofAccordionexpand(this)">
                            <div class="accordion-qstn">
                                <p>Will I get a refund if the pro plugin doesn't meet my requirements?</p>
                                <img class="accordion-img" src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/blck-down-arrow.svg'); ?>">
                                <img class="accordion-img-opn" src="<?php echo esc_url(THWEPOF_URL .'admin/assets/css/blue-down-arrow.svg'); ?>">
                            </div>
                            
                            <div class="panel">
                                <p>Please note that as per our refund policy, we will provide a refund within one month from the date of purchase, if you are not satisfied with the product. Please refer to the below link for more details:
                                </p>
                                <p class="th-faq-answer">
                                    <a href="https://www.themehigh.com/refund-policy/" target="_blank" rel="noopener noreferrer">https://www.themehigh.com/refund-policy/</a><br>
                                </p>
                            </div>
                        </button>
                        
                    </div>
                </section>
                <section class="switch-to-pro-tab">
                    <div class="th-switch-to-pro">
                        <h3 class="switch-to-pro-heading">Switch to Pro version and be a part of our limitless features</h3>
                        <p>Switch to Pro and unlock access to a few of the most sought-after features on your product page and experience one-of-a-kind personalization like never before.</p>
                        <!-- <div class="th-button-get-pro-link"> -->
                            <a class="button-get-pro" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" onclick="this.classList.add('clicked')"><?php echo __('Get Pro', 'woo-extra-product-options');?></a> 
                        <!-- </div> -->
                        
                    </div>
                </section>
            </div>
        </div>
        <?php
    }

}
endif;