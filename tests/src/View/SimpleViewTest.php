<?php
namespace MinorWork\View;

use PHPUnit\Framework\TestCase;

class SimpleViewTest extends TestCase
{
    public function testRender()
    {
        $sv = new SimpleView;

        $sv->prepare('A');
        $this->assertEquals((string) $sv, 'A');

        $sv->prepare('{A}', ['A'=>'B']);
        $this->assertEquals((string) $sv, 'B');

        $sv->prepare(
            "I am a {job}, I mean {job}.\n\nNot a {not_my_job}.",
            ['job' => 'Doctor', 'not_my_job' => 'mechanic']
        );
        $this->assertEquals((string) $sv, "I am a Doctor, I mean Doctor.\n\nNot a mechanic.");
    }
}
