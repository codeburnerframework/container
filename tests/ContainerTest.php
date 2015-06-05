<?php

use Codeburner\Container\Container;

class ContainerTest extends PHPUnit_Framework_TestCase
{

	public function testIsBound()
	{
		$container = new Container;
		$container->bind('a', 'stdClass');
		$this->assertTrue($container->isBound('a'));
	}

	public function testIsResolved()
	{
		$container = new Container;
		$container->bind('a', 'stdClass', true);
		$this->assertTrue($container->resolved('a'));
	}

	public function testFlush()
	{
		$container = new Container;
		$container->bind('a', 'stdClass');
		$this->assertTrue($container->isBound('a'));
		$container->flush();
		$this->assertFalse($container->isBound('a'));
	}

	public function testOffsetGet()
	{
		$container = new Container;
		$container->bind('a', 'stdClass');
		$this->assertEquals(new stdClass, $container['a']);
	}

	/**
	 * @depends testOffsetGet
	 */
	public function testOffsetSet()
	{
		$container = new Container;
		$container['a'] = 'stdClass';
		$this->assertEquals(new stdClass, $container['a']);
	}

	public function testOffsetExists()
	{
		$container = new Container;
		$container->bind('a', 'stdClass');
		$this->assertTrue(isset($container['a']));
	}

}
