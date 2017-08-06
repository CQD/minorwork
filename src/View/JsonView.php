<?php

namespace MinorWork\View;

class JsonView
{
    private $params = [];

    /**
     * Prepare to render result
     *
     * The framework will call `toString()` to get rendered result.
     *
     * @param string $params
     */
    public function prepare($params = [])
    {
        $this->params = $params;
    }

    /**
     * Actually render the template
     * @return string rendered result
     */
    public function __toString()
    {
        return json_encode($this->params);
    }
}
