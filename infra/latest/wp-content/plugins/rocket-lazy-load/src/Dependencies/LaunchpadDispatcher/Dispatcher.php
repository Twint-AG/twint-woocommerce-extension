<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadDispatcher;

use RocketLazyLoadPlugin\Dependencies\LaunchpadDispatcher\Interfaces\SanitizerInterface;
use RocketLazyLoadPlugin\Dependencies\LaunchpadDispatcher\Sanitizers\BoolSanitizer;
use RocketLazyLoadPlugin\Dependencies\LaunchpadDispatcher\Sanitizers\FloatSanitizer;
use RocketLazyLoadPlugin\Dependencies\LaunchpadDispatcher\Sanitizers\IntSanitizer;
use RocketLazyLoadPlugin\Dependencies\LaunchpadDispatcher\Sanitizers\StringSanitizer;

class Dispatcher
{
    protected $deprecated_filters = [];

    protected $deprecated_actions = [];

    public function do_action(string $name, ...$parameters)
    {
        $this->call_deprecated_actions($name, ...$parameters);
        do_action($name, ...$parameters);
    }

    public function apply_filters(string $name, SanitizerInterface $sanitizer, $default, ...$parameters)
    {
        $result_deprecated = $this->call_deprecated_filters($name, $default, ...$parameters);

        $result = apply_filters($name, $result_deprecated, ...$parameters);

        $sanitized_result = $sanitizer->sanitize($result);

        if( false === $sanitized_result && $sanitizer->is_default($sanitized_result, $result) ) {
            return $default;
        }

        return $sanitized_result;
    }

    public function apply_string_filters(string $name, string $default, ...$parameters): string
    {
        return $this->apply_filters($name, new StringSanitizer(), $default, ...$parameters);
    }

    public function apply_bool_filters(string $name, bool $default, ...$parameters): bool
    {
        return $this->apply_filters($name, new BoolSanitizer(), $default, ...$parameters);
    }

    public function apply_int_filters(string $name, int $default, ...$parameters): int
    {
        return $this->apply_filters($name, new IntSanitizer(), $default, ...$parameters);
    }

    public function apply_float_filters(string $name, float $default, ...$parameters): float
    {
        return $this->apply_filters($name, new FloatSanitizer(), $default, ...$parameters);
    }

    public function add_deprecated_action(string $name, string $deprecated_name, string $version, string $message = '')
    {
        $this->deprecated_actions[$name][] = [
            'name' => $deprecated_name,
            'version' => $version,
            'message' => $message
        ];
    }

    public function add_deprecated_filter(string $name, string $deprecated_name, string $version, string $message = '')
    {
        $this->deprecated_filters[$name][] = [
            'name' => $deprecated_name,
            'version' => $version,
            'message' => $message
        ];
    }

    protected function call_deprecated_actions(string $name, ...$parameters)
    {
        if( ! key_exists($name, $this->deprecated_actions)) {
            return;
        }

        foreach ($this->deprecated_actions[$name] as $action) {
            do_action_deprecated($action['name'], $parameters, $action['version'], $name, $action['message']);
            $this->call_deprecated_actions($action['name'], ...$parameters);
        }
    }

    protected function call_deprecated_filters(string $name, $default, ...$parameters)
    {
        if( ! key_exists($name, $this->deprecated_filters)) {
            return $default;
        }

        foreach ($this->deprecated_filters[$name] as $filter) {
            $filter_parameters = array_merge([$default], $parameters);
            $default = apply_filters_deprecated($filter['name'], $filter_parameters, $filter['version'], $name, $filter['message']);
            $default = $this->call_deprecated_filters($filter['name'], $default, ...$parameters);
        }

        return $default;
    }
}