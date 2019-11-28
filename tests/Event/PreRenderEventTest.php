<?php

namespace Twig\Tests\Event;

use PHPUnit\Framework\TestCase;
use Twig\Event\PreRenderEvent;

class PreRenderEventTest extends TestCase
{

    public function testGetContext()
    {
        $context = ['foo' => 'bar'];
        $event = new PreRenderEvent($context);
        $this->assertEquals($context, $event->getContext());
    }

    public function testSetContext()
    {
        $context = ['foo' => 'bar'];
        $event = new PreRenderEvent(['bar' => 'foor']);
        $event->setContext($context);
        $this->assertEquals($context, $event->getContext());
    }

    public function testAddContext()
    {
        $event = new PreRenderEvent(['bar' => 'foo']);
        $event->addContext('foo', 'bar');
        $result = $event->getContext();
        $this->assertTrue(is_array($result));
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('foo', $result);
        $this->assertSame('bar', $result['foo']);
        $this->assertArrayHasKey('bar', $result);
        $this->assertSame('foo', $result['bar']);
    }
}
