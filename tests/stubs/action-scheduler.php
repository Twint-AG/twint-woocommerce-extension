<?php

// Static analysis signatures for the Action Scheduler API supplied by WooCommerce.
// This file is scanned by PHPStan and must never be loaded at runtime.

/** @return int Action ID, or zero when scheduling fails. */
function as_enqueue_async_action($hook, $args = [], $group = '', $unique = false, $priority = 10) {}

/** @return int Action ID, or zero when scheduling fails. */
function as_schedule_single_action($timestamp, $hook, $args = [], $group = '', $unique = false, $priority = 10) {}

/** @return void */
function as_unschedule_all_actions($hook, $args = [], $group = '') {}

/** @return array */
function as_get_scheduled_actions($args = [], $return_format = 'OBJECT') {}
