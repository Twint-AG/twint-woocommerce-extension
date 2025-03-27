=== WooCommerce Weight Based Shipping ===
Contributors: dangoodman
Tags: woocommerce shipping, table rate shipping, woocommerce free shipping, weight-based shipping, rule-based shipping
Requires PHP: 7.2
Requires at least: 4.6
Tested up to: 6.7
WC requires at least: 5.0
WC tested up to: 9.7


Weight Based Shipping is a flexible and widely-used solution to calculate shipping costs based on the total cart weight and value.

== Description ==

Weight Based Shipping is a flexible and widely-used solution for sites using WooCommerce that allows store owners to calculate shipping costs based on the weight and value of the products in the cart. It offers various customization options to set shipping rules based on different conditions, enabling tailored shipping rates for different customer needs and scenarios.

<p>&nbsp;</p>
= Tiered weight-based rates for weight ranges =
<p></p>
Weight Based Shipping allows creating incremental shipping costs based on defined weight ranges. For example, you can set a specific rate for orders up to 5 kg, a higher rate for 5–10 kg, and another rate for weights above 10 kg. This structure enables you to charge progressively more as the package weight increases, offering a fair and predictable pricing model that matches shipping costs to the weight of the items.

<p>&nbsp;</p>
= Free shipping over a threshold =
<p></p>
Offer free shipping once an order reaches a specific threshold, such as a minimum cart total or a minimum/maximum weight. For example, you might provide free shipping on orders over $50 or on packages weighing more than 20 kg. This approach encourages customers to buy more to qualify for free shipping, boosting average order value while keeping lighter, smaller orders cost-effective.

<p>&nbsp;</p>
= Shipping classes [Pro] =
<p></p>
Shipping classes support provides flexibility in configuring shipping rates for your products. You can set up custom shipping rates for specific product groups — frozen, fragile, bulky, drop-shipped, etc. Additionally, you have the option to offer free shipping for selected products or exclude certain items from free shipping. The feature also allows you to enable or disable specific shipping methods on a per-product basis.
[compare features](https://weightbasedshipping.com/upgrade)&nbsp;&nbsp;&nbsp;[more details](https://weightbasedshipping.com)


<p>&nbsp;</p>
Feel free to [contact support](https://wordpress.org/support/plugin/weight-based-shipping-for-woocommerce/) if you have any questions.

Like the plugin? Leave a [review](https://wordpress.org/support/plugin/weight-based-shipping-for-woocommerce/reviews/#new-post)!


== Changelog ==

= 6.5.0 =
* Fix the global shipping method not being activated by WooCommerce 9.7.
* Tested with WooCommerce 9.7.

= 6.4.1 =
* Use the new global method by default for new installations.

= 6.3.1 =
* Tested with WooCommerce 9.6.

= 6.3.0 =
* UI tweaks.
* Tested with WooCommerce 9.5.

= 6.2.0 =
* Limit the width of the Destination column.

= 6.1.0 =
* Rename the column Amount to Value.
* Add a note regarding multiple matching shipping rules.
* Tested with WordPress 6.7, WooCommerce 9.4.

= 6.0.0 =
* Make the new UI the default. No breaking changes.

= 5.11.0 =
* Fix order subtotal might be incorrectly detected for block-based Cart.
* WBS6 preview: UI tweaks.

= 5.10.0 =
* Fix WooCommerce PayPal Payment admin messages cause WBS rules to appear empty.
* Tested with WooCommerce 9.3.

= 5.9.4 =
* Tested with WooCommerce 9.2.

= 5.9.3 =
* Tested with WordPress 6.6, WooCommerce 9.1.

= 5.9.2 =
* Tested with WooCommerce 8.9, 9.0.

= 5.9.1 =
* Tested with WordPress 6.5, WooCommerce 8.8.
* WBS6 preview: minor fix of the Save button visibility

= 5.9.0 =
* Prevent running with unsupported PHP, WordPress, or WooCommerce versions.
* Fix an error when multiple installations of the plugin are active.

= 5.8.0 =
* Ship WBS6 Preview with the free version.
* WBS6 preview: improve the WooCommerce sticky header height detection.

= 5.7.2 =
* Tested with WooCommerce 8.6, 8.7.
* WBS6 preview improvements (Plus version only).

= 5.7.1 =
* Tested with WooCommerce 8.5.
* WBS6 preview improvements (Plus version only).

= 5.7.0 =
* Tested with WooCommerce 8.4.
* WBS6 preview improvements (Plus version only).

= 5.6.3 =
* Tested with WordPress 6.4, WooCommerce 8.3.
* Drop PHP 7.1 support.

= 5.6.2 =
* Tested with WooCommerce 8.2.

= 5.6.1 =
* Tested with WooCommerce 8.1.

= 5.6.0 =
* Enable WBS6 preview (Plus version only).

= 5.5.7 =
* Tested with WooCommerce 8.0, WordPress 6.3.
* Drop WooCommerce pre-5.0 support.

= 5.5.6 =
* Show a notice on the global shipping method suggesting to try shipping zones instead.
* Remove WooCommerce pre-2.6 compat code.
* Prepare WBS6 preview.

= 5.5.5 =
* Tested with WooCommerce 7.9.

= 5.5.4 =
* Tested with WooCommerce 7.8.

= 5.5.3 =
* Tested with WooCommerce 7.7.

= 5.5.2 =
* Tested with WooCommerce 7.6.

= 5.5.1 =
* Declare compatibility with HPOS.
* Tested with WordPress 6.2.

= 5.5.0 =
* Check nonce on config update.
* Remove the legacy config import option (used for 4.x -> 5.x migration).
* Tested with WooCommerce 7.5.

= 5.4.1 =
* Tested with WooCommerce 7.4.

= 5.4.0 =
* Use the cart price provided by WooCommerce by default for fresh installations of the plugin. It makes Order Subtotal accounting for virtual items' prices and increases compatibility with third-party plugins.
* Make sure a user has manage_woocommerce capability in order to update the shipping rules.
* Tested with PHP 8.2.

= 5.3.27 =
* Raise the minimum required WordPress version to 4.6.

= 5.3.26 =
* Tested with WooCommerce 7.1.

= 5.3.25 =
* Tested with WooCommerce 7.0, WordPress 6.1.

= 5.3.24 =
* Tested with WooCommerce 6.9.

= 5.3.23 =
* Tested with WooCommerce 6.7

= 5.3.22 =
* Tested with WooCommerce 6.5, WordPress 6.0.

= 5.3.21 =
* Fixed a PHP warning triggered by some other plugins about a missing InstalledVersions.php file.
* Tested with WooCommerce 6.4.

= 5.3.20 =
* Tested with WooCommerce 6.3.

= 5.3.19 =
* Tested with WordPress 5.9, WooCommerce 6.1.

= 5.3.18 =
* Tested with WooCommerce 6.0.

= 5.3.17 =
* Tested with WooCommerce 5.9.

= 5.3.16 =
* Tested with WooCommerce 5.8.
* Drop PHP 5.6 support.

= 5.3.15 =
* Tested with WooCommerce 5.7.

= 5.3.14 =
* Tested with WooCommerce 5.6.

= 5.3.13 =
* Tested with WordPress 5.8, WooCommerce 5.5.

= 5.3.12 =
* Tested with WooCommerce 5.3.

= 5.3.11 =
* Tested with WooCommerce 5.2.

= 5.3.10 =
* Tested with WooCommerce 5.1, WordPress 5.7.

= 5.3.9 =
* Bump the minimum supported PHP version to 5.6.
* Tested with WooCommerce 5.0.

= 5.3.8 =
* Tested with WooCommerce 4.9.
* Require minimum WooCommerce 3.2.

= 5.3.7.1 =
* Tested with WooCommerce 4.8, WordPress 5.6.

= 5.3.7 =
* Fix the issue with the global WBS method not being triggered by WooCommerce for customers having no location set.
* Tested with WooCommerce 4.7.

= 5.3.6.1 =
* Tested with WooCommerce 4.6.

= 5.3.6 =
* Raise the minimum required WooCommerce version to 3.1.2.
* Tested with WooCommerce 4.5.

= 5.3.5 =
* Fix unsaved settings warning with WooCommerce 4.4.1.

= 5.3.4.5 =
* Tested with WordPress 5.5.

= 5.3.4.4 =
* Fix a typo in the settings link.

= 5.3.4.3 =
* Tested with WooCommerce 4.3.

= 5.3.4.2 =
* Tested with WooCommerce 4.2.

= 5.3.4.1 =
* Tested with WooCommerce 4.1.

= 5.3.4 =
* Fix small appearance issues with recent WordPress/WooCommerce.

= 5.3.3.2 =
* Tested with WooCommerce 4.0, WordPress 5.4.

= 5.3.3.1 =
* Tested with WooCommerce 3.9.

= 5.3.3 =
* Fix appearance with WordPress 5.3.

= 5.3.2.2 =
* Update supported WooCommerce version to 3.8, WordPress to 5.3.

= 5.3.2.1 =
* Update supported WooCommerce version to 3.7.

= 5.3.2 =
* Workaround VaultPress false-positive.

= 5.3.1 =
* Fix '400 Bad Request' error on saving settings.

= 5.3.0 =
* Add 'after discount applied' option to the Order Subtotal condition to match against order price with coupons and other discounts applied.

= 5.2.6 =
* Fix WooCommerce 3.6.0+ compatibility issue causing no shipping options shown to a customer under some circumstances.

= 5.2.5 =
* Fix PHP 5.3 compatibility issue.

= 5.2.4.1 =
* Update supported WordPress version to 5.1.

= 5.2.4 =
* Partial support for decimal quantities.

= 5.2.3 =
* Update supported WordPress version to 5.0.

= 5.2.2 =
* Improve prerequisites checking.
* Update supported WooCommerce version to 3.5.

= 5.2.1 =
* Update supported WooCommerce version.

= 5.2.0 =
* Don't ignore duplicate shipping classes entries. When multiple rates specified for a class in a rule, they all will be in effect starting from this version.

= 5.1.5 =
* Fix issue with Weight Rate causing zero price in case of a small order weight and large step ("per each") value.
* Fix appearance issues with WooCommerce 3.2.

= 5.1.4 =
* Fix blank settings page in Safari when Yoast SEO is active.

= 5.1.3 =
* Fix WooCommerce pre-2.6 compatibility.
* Minor appearance fixes.

= 5.1.2 =
* Fix blank settings page in Firefox when Yoast SEO is active.

= 5.1.1 =
* Fix settings not saved on hosts overriding arg_separator.output php.ini option.

= 5.1.0 =
* Support WooCommerce convention on shipping option ids to fix shipping method detection in third-party code, like Cash On Delivery payment method and Conditional Shipping and Payments plugin.

= 5.0.9 =
* Show a warning on PHP 5.3 with Zend Guard Loader active known to crash with 500/503 server error.

= 5.0.8 =
* Fix IE11 error preventing from adding/importing rules.

= 5.0.7 =
* Fix welcome screen buttons appearance in WP 4.7.5.

= 5.0.6 =
* A bunch of minor fixes.

= 5.0.5 =
* Fix PHP 5.3.x error while importing legacy rules.
* Fix WooCommerce 3.x deprecation notice about get_variation_id.

= 5.0.4 =
* Fix WooCommerce 3.x deprecation notices.
* Deactivate other active versions of the plugin upon activation (fixed).

= 5.0.3-beta =
* Fix 'fatal error: call to undefined function Wbs\wc_get_shipping_method_count()'.

= 5.0.2-beta =
* Avoid conflicts with other plugins using same libraries.
* Deactivate other active versions of the plugin upon activation.

= 5.0.1-beta =
* Fix Destinations not being saved on WooCommerce 3.0.

= 5.0.0-beta =
* Rewritten from scratch, better performance and look'n'feel.
* Shipping Zones support.

= 4.2.3 =
* Fix links to premium plugins.

= 4.2.2 =
* Fix rules not imported from an older version when updating from pre-4.0 to 4.2.0 or 4.2.1.

= 4.2.1 =
* Fix saving rules order.

= 4.2.0 =
* Allow sorting rules by drag'n'drop in admin panel.

= 4.1.4 =
* WooCommerce 2.6 compatibility fixes.

= 4.1.3 =
* Minimize chances of a float-point rounding error in the weight step count calculation (https://wordpress.org/support/topic/weight-rate-charge-skip-calculate).

= 4.1.2 =
* Don't fail on invalid settings, allow editing them instead.

= 4.1.1 =
* Backup old settings on upgrade from pre-4.0 versions.

= 4.1.0 =
* Fix WC_Settings_API->get_field_key() missing method usage on WC 2.3.x.
* Use package passed to calculate_shipping() funciton instead of global cart object for better integration with 3d-party plugins.
* Get rid of wbs_remap_shipping_class hook.
* Use class autoloader for better performance and code readability.

= 4.0.0 =
* Admin UI redesign.

= 3.0.0 =
* Country states/regions targeting support.

= 2.6.9 =
* Fixed: inconsistent decimal input handling in Shipping Classes section (https://wordpress.org/support/topic/please-enter-in-monetary-decimal-issue).

= 2.6.8 =
* Fixed: plugin settings are not changed on save with WooCommerce 2.3.10 (WooCommerce 2.3.10 compatibility issue).

= 2.6.6 =
* Introduced 'wbs_profile_settings_form' filter for better 3d-party extensions support.
* Removed partial localization.

= 2.6.5 =
* Min/Max Shipping Price options.

= 2.6.3 =
* Improved upgrade warning system.
* Fixed warning about Shipping Classes Overrides changes.

= 2.6.2 =
* Fixed Shipping Classes Overrides: always apply base Handling Fee.

= 2.6.1 =
* Introduced "Subtotal With Tax" option.

= 2.6.0 =
* Min/Max Subtotal condition support.

= 2.5.1 =
* Introduce "wbs_remap_shipping_class" filter to provide 3dparty plugins an ability to alter shipping cost calculation.
* WordPress 4.1 compatibility testing.

= 2.5.0 =

* Shipping classes support.
* Ability to choose all countries except specified.
* Select All/None buttons for countries.
* Purge shipping price calculations cache on configuration changes to reflect actual config immediatelly.
* Profiles table look tweaks.
* Other small tweaks.

= 2.4.2 =

* Fixed: deleting non-currently selected configuration deletes first configuration from the list.

= 2.4.1 =

* Updated pot-file required for translations.
* Added three nice buttons to plugin settings page.
* Prevent buttons in Actions column from wrapping on multiple lines.

= 2.4.0 =

* By default, apply Shipping Rate to the extra weight part exceeding Min Weight. Also, a checkbox added to switch off this feature.

= 2.3.0 =

* Duplicate profile feature.
* New 'Weight Step' option for rough gradual shipping price calculation.
* Added more detailed description to the Handling Fee and Shipping Rate fields to make their purpose clear.
* Plugin prepared for localization.
* Refactoring.

= 2.2.3 =

* Fixed: first time saving settings with fresh installations does not save anything while reporting a success.
* Replace short php tags with their full equivalents to make code more portable.

= 2.2.2 =

Fix "parse error: syntax error, unexpected T_FUNCTION in woocommerce-weight-based-shipping.php on line 610" http://wordpress.org/support/topic/fatal-error-1164.

= 2.2.1 =

Allow zero weight shipping. Thus, only Handling Fee is added to the final price.

Previously, weight based shipping option has not been shown to user if total weight of their cart is zero. Since version 2.2.1 this is changed so shipping option is available to user with price set to Handling Fee. If it does not suite your needs well you can return previous behavior by setting Min Weight to something a bit greater zero, e.g. 0.001, so that zero-weight orders will not match constraints and the shipping option will not be shown.


== Screenshots ==

1. Tiered weight-based shipping configuration
2. Free shipping over threshold and tiered weight-based shipping
3. Per-product shipping with shipping classes