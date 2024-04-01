<?php

declare(strict_types=1);

if (! function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param string $key The environment variable key.
     * @param mixed|null $default The default value to return if the environment variable does not exist.
     * @return mixed The environment variable value, or the default value.
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }
        return $value;
    }
}
