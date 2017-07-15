<?php

namespace MinorWork\View;

class SimpleView
{
    private $template = '';
    private $params = [];

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
     * Actually render the template
     * @return string rendered result
     */
    public function __toString()
    {
        $template = $this->template;

        foreach ($this->params as $key => $value) {
            if (!is_scalar($value) && !method_exists($value, '__toString') ) {
                $value = '[' . gettype($value) . ']';
            }
            $keys[] = "{{$key}}";
            $values[] = $value;
        }

        return isset($keys) ? str_replace($keys, $values, $template) : $template;
    }
}
