<?php
namespace MinorWork\View;

use PHPUnit\Framework\TestCase;

class JsonViewTest extends TestCase
{
    public function testRender()
    {
        $v = new JsonView;

        $v->prepare(['a' => 'b']);
        $this->assertEquals((string) $v, '{"a":"b"}');

        $v->prepare(['a' => ['b' => 'c']]);
        $this->assertEquals((string) $v, '{"a":{"b":"c"}}');
    }
}
