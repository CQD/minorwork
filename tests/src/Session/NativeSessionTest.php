<?php

namespace MinorWork\Session;

use PHPUnit\Framework\TestCase;

class NativeSessionTest extends TestCase
{
    public function testGetSet()
    {
        $s = new NativeSession;
        $s->set('A', 'b');
        $this->assertEquals($s->get('A'), 'b');
        $this->assertEquals($_SESSION['A'], 'b');

        // test default value
        $this->assertNull($s->get('B'));
        $this->assertEquals($s->get('A', 'my default'), 'b');
        $this->assertEquals($s->get('C', 'my default'), 'my default');

        // test clear key
        $s->set('A', null);
        $this->assertNull($s->get('A'));
        $this->assertEquals($s->get('A', 'my default'), 'my default');
        $this->assertArrayNotHasKey('A', $_SESSION);

        // test multiple set
        $data = [
            'A' => 'a',
            'B' => 'b',
            'C' => 'c',
            'D' => 'd',
        ];
        $s->setMany($data);
        $this->assertEquals($data, $_SESSION);
        $this->assertEquals($data, $s->getMany(array_keys($data)));
    }

    public function testFlash()
    {
        $data = [
            'A' => 'a',
            'B' => 'b',
            'C' => 'c',
            'D' => 'd',
        ];
        $flashData = [
            'FA' => 'fa',
            'FB' => 'fb',
            'FC' => 'fc',
        ];
        $sessionData = $data;
        $sessionData[NativeSession::FLASHROOTKEY] = $flashData;

        $allData = array_merge($data, $flashData);

        // new session
        $s = new NativeSession;
        $s->setMany($data);
        $s->flashMany($flashData);

        $this->assertEquals($allData, $s->getMany(array_keys($allData)));

        unset($s);
        $this->assertEquals($sessionData, $_SESSION);

        // new session
        $s = new NativeSession;
        $this->assertEquals($allData, $s->getMany(array_keys($allData)));
        $s->flash('FA', 'HA!');
        unset($s);

        $sessionData[NativeSession::FLASHROOTKEY] = ['FA' => 'HA!'];
        $this->assertEquals($sessionData, $_SESSION);

        // new session
        $allData['FA'] = 'HA!';
        $allData['FB'] = null;
        $allData['FC'] = null;
        $s = new NativeSession;
        $this->assertEquals($allData, $s->getMany(array_keys($allData)));

        unset($s);
        $this->assertEquals($data, $_SESSION);
    }
}