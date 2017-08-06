<?php

namespace MinorWork\Session;

/**
 * PHP Native Session handler.
 */
class NativeSession implements SessionInterface
{
    const FLASHROOTKEY = 'MinorWorkInternal.flash';

    private $newFlash = [];
    private $oldFlash;

    public function __construct()
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        if (!isset($_SESSION) && 'cli' === PHP_SAPI) {
            $_SESSION = []; // @codeCoverageIgnore
        }

        if (!isset($_SESSION)) {
            throw new \Exception("Could not init session!"); // @codeCoverageIgnore
        }

        $this->oldFlash = isset($_SESSION[self::FLASHROOTKEY]) ? $_SESSION[self::FLASHROOTKEY] : null;
    }

    public function __destruct()
    {
        if ($this->newFlash) {
            $_SESSION[self::FLASHROOTKEY] = $this->newFlash;
        } else {
            unset($_SESSION[self::FLASHROOTKEY]);
        }
    }

    public function get($key, $default = null)
    {
        // Check keys in the order of:
        // - normal key
        // - previous flash data
        // - current flash data
        foreach ([$_SESSION, $this->oldFlash, $this->newFlash] as $S) {
            if (isset($S[$key])) {
                return $S[$key];
            }
        }

        // No data found, return default value
        return $default;
    }

    public function getMany($keys)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    public function set($key, $value)
    {
        $this->setManyTo($_SESSION, [$key => $value]);
    }

    public function setMany($data)
    {
        $this->setManyTo($_SESSION, $data);
    }

    public function flash($key, $value)
    {
        $this->setManyTo($this->newFlash, [$key => $value]);
    }

    public function flashMany($data)
    {
        $this->setManyTo($this->newFlash, $data);
    }

    private function setManyTo(&$target, $data)
    {
        // Remove null data
        foreach ($data as $key => $value) {
            if (null === $value) {
                unset($target[$key]);
                unset($data[$key]);
            }
        }

        // set normal data in one shoot
        $target = array_replace($target, $data);
        return $target;
    }
}
