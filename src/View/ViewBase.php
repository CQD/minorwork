<?php

namespace MinorWork\View;

abstract class ViewBase
{
    protected $template = '';
    protected $params = [];

    public function __construct(){}

    /**
     * Prepare to render result
     *
     * The framework will call `toString()` to get rendered result.
     *
     * @param string $template
     * @param string $params
     */
    public function prepare($template, $params = [])
    {
        $this->template = $template;
        $this->params = $params;
    }

    /**
     * Return rendered result.
     *
     * @return string rendered result string to be displayed.
     */
    abstract public function __toString();
}
