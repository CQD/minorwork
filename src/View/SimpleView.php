<?php

namespace MinorWork\View;

class SimpleView extends ViewBase
{
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
