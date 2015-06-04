<?php

include __DIR__ . '\..\src\Container.php';

use Codeburner\Container\Container;

class ContainerTest extends PHPUnit_Framework_TestCase
{

	public function testBind()
	{
		$container = new Container;
		$this->assertEquals($container, $container->bind('a', 'StdClass'));
	}

	/**
	 * @depends testBind
	 */
	public function testIsBound()
	{
		$container = new Container;
		$container->bind('a', 'StdClass');
		$this->assertTrue($container->isBound('a'));
	}

	/**
	 * @depends testBind
	 */
	public function testIsResolved()
	{
		$container = new Container;
		$container->bind('a', 'StdClass', true);
		$this->assertTrue($container->resolved('a'));
	}

	/**
	 * @depends testBind
	 * @depends testIsBound
	 */
	public function testFlush()
	{
		$container = new Container;
		$container->bind('a', 'StdClass');
		$this->assertTrue($container->isBound('a'));
		$container->flush();
		$this->assertFalse($container->isBound('a'));
	}

	/**
	 * @depends testBind
	 */
	public function testOffsetGet()
	{
		$container = new Container;
		$container->bind('a', 'StdClass');
		$this->assertEquals(new StdClass, $container['a']);
	}

	/**
	 * @depends testBind
	 * @depends testOffsetGet
	 */
	public function testOffsetSet()
	{
		$container = new Container;
		$container['a'] = 'StdClass';
		$this->assertEquals(new StdClass, $container['a']);
	}

	/**
	 * @depends testBind
	 */
	public function testOffsetExists()
	{
		$container = new Container;
		$container->bind('a', 'StdClass');
		$this->assertTrue(isset($container['a']));
	}

}
