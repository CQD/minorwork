<?php

namespace MinorWork\Session;

/**
 * Session interface for MinorWork framework.
 */
interface SessionInterface
{
    /**
     * Get data from session.
     *
     * @param string $key Key of required data
     * @param mixed $default If key not found in session, this will be returned.
     * @return mixed Data stored in session, or null if not found and no `$default` given
     */
    public function get($key, $default = null);

    /**
     * Get data from session.
     *
     * @param array $keys Array of keys to get from session
     * @return array Data stored in session, keyed by $keys. Value will be null if key not found in session.
     */
    public function getMany($keys);

    /**
     * Assign value to a key. If value is null, that key will be cleared.
     *
     * @param string $key Key in session.
     * @param mixed $value Value of specified key.
     */
    public function set($key, $value);

    /**
     * Set multiple key-value pair into session.
     *
     * @param array $data
     */
    public function setMany($data);

    /**
     * Same as `set()`, but key will only survive one more request, and will be cleared after that.
     *
     * @param string $key Key in session.
     * @param mixed $value Value of specified key.
     */
    public function flash($key, $value);

    /**
     * Same as `setMany()`, but keys will only survive one more request, and will be cleared after that.
     *
     * @param array $data
     */
    public function flashMany($data);
}
