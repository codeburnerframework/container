<?php

class ContainerTest extends PHPUnit_Framework_TestCase
{

	public function setUp()
	{
		$this->container = new Codeburner\Container\Container;
		parent::setUp();
	}

	public function testMakeClass()
	{
		$this->assertInstanceof('stdClass', $this->container->make('stdClass'));
	}

	public function testMakeSingleton()
	{
		$this->container->singleton('a', 'stdClass');

		$this->assertTrue($this->container->make('a') instanceof stdClass && $this->container->make('a') == $this->container->make('a'));
	}

	public function testMakeInvalid()
	{
		$this->setExpectedException('Exception');

		$this->container->make('SomeClassName');
	}

	public function testSimpleDependencyInjection()
	{
		$obj = $this->container->make('OneDependencyClass');

		$this->assertInstanceof('OneDependencyClass', $obj);
	}

	public function testMultipleDependencyInjection()
	{
		$obj = $this->container->make('TwoDependenciesClass');

		$this->assertInstanceof('TwoDependenciesClass', $obj);
	}

	public function testNotSingletonDependenciesResolution()
	{
		$a = $this->container->make('DinamicAttributeDependencyClass', [], true);
		$b = $this->container->make('DinamicAttributeDependencyClass', [], true);

		$this->assertTrue($a->dac->number != $b->dac->number);
	}

	public function testSet()
	{
		$this->container->set('a', function ($container) {
			return 'should work';
		}, true);

		$this->assertEquals('should work', $this->container->get('a'));
	}

	public function testIsBound()
	{
		$this->container->set('a', 'stdClass');

		$this->assertTrue($this->container->has('a'));
	}

	public function testFlush()
	{
		$this->container->set('a', 'stdClass');

		$this->assertTrue($this->container->has('a'));

		$this->container->flush();

		$this->assertFalse($this->container->has('a'));
	}

	public function testIsSingleton()
	{
		$this->container->singleton('a', 'stdClass');

		$this->assertTrue($this->container->isSingleton('a'));
	}

	public function testSetSingleton()
	{
		$this->container->set('a', 'stdClass', true);

		$this->assertTrue($this->container->isSingleton('a'));
	}

	public function testSingleton()
	{
		$this->container->singleton('b', 'stdClass');

		$this->assertTrue($this->container->isSingleton('b'));
	}

	public function testSetIf()
	{
		$this->container->setIf('a', 'stdClass');

		$this->container->setIf('a', 'stdClass', true);

		$this->assertTrue($this->container->isSingleton('a'));
	}

	public function testSetInstance()
	{
		$instance = new stdClass;

		$instance->test = 'should work';

		$this->container->instance('a', $instance);

		$this->assertEquals('should work', $this->container->get('a')->test);
	}

	public function testSetToSingleton()
	{
		$instance = new stdClass;

		$instance->test = 'should work';

		$this->container->setTo('OneDependencyClass', 'stdClass', $instance);

		$instance = $this->container->make('OneDependencyClass');

		$this->assertTrue(property_exists($instance->std, 'test'));
	}

	public function testSetToResolvableClosure()
	{

		$this->container->setTo('OneDependencyClass', 'stdClass', function () {
			$instance = new stdClass;
			$instance->test = 'should work';

			return $instance;
		});

		$instance = $this->container->make('OneDependencyClass');

		$this->assertTrue(property_exists($instance->std, 'test'));
	}

	public function testSetToResolvableString()
	{
		$this->container->setTo('OneDependencyClass', 'stdClass', $this->container->make('stdClass'));

		$this->assertInstanceof('OneDependencyClass', $this->container->make('OneDependencyClass'));
	}

	public function testExtendResolvable()
	{
		$this->container->set('a', 'stdClass');

		$this->container->extend('a', function ($stdClass, $container) {
			$stdClass->test = 'should work';

			return $stdClass;
		});

		$this->assertTrue(property_exists($this->container->get('a'), 'test'));
	}

	public function testExtendSingleton()
	{
		$this->container->set('a', 'stdClass', true);

		$this->container->extend('a', function ($stdClass, $container) {
			$stdClass->test = 'should work';

			return $stdClass;
		});

		$this->assertTrue(property_exists($this->container->get('a'), 'test'));
	}

	public function testShare()
	{
		$this->container->set('a', 'stdClass');

		$this->container->share('a');

		$this->assertTrue($this->container->isSingleton('a'));
	}

	public function testMakeseting()
	{
		$this->container->set('a', 'stdClass');

		$this->assertInstanceof('stdClass', $this->container->make('a'));
	}

	public function testContainerAware()
	{
		$instance = $this->container->make('ContainerAwareClass');

		$instance->setContainer($this->container);

		$this->assertInstanceof('Codeburner\Container\Container', $instance->getContainer());
	}

	public function testCallFunction()
	{
		$this->assertTrue($this->container->call(function () {
			return true;
		}));
	}

	public function testCallFunctionWithDependencies()
	{
		$this->assertTrue($this->container->call(function (TwoDependenciesClass $b) {
			return isset($b->odc) && isset($b->std);
		}));
	}

	public function testForcedDependency()
	{
		$test = new stdClass;
		$test->test = true;

		$this->assertTrue($this->container->call(function (stdClass $std) {
			return isset($std->test);
		}, ['std' => $test]));
	}

}
