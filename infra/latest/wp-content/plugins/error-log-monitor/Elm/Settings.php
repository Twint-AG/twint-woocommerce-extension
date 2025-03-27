<?php

/**
 * Like scbOptions, but stores settings in a site-wide option.
 */
class Elm_Settings extends scbOptions {
	public function get($field = null, $default = null) {
		$data = array_merge($this->defaults, get_site_option($this->key, array()));
		return scbForms::get_value($field, $data, $default);
	}

	public function update($newdata, $clean = true) {
		if ( $clean ) {
			$newdata = $this->_clean($newdata);
		}
		update_site_option($this->key, array_merge($this->get(), $newdata));
	}

	public function delete() {
		delete_site_option($this->key);
	}

	protected function _clean($data) {
		return wp_array_slice_assoc($data, array_keys($this->defaults));
	}

	function _activation() {
		add_site_option($this->key, $this->defaults);
	}
}