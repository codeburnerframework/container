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
		$this->setExpectedException('ErrorException');

		try {
			$this->container->make('SomeClassName');
		} catch (Exception $e) {
			throw new ErrorException;
		}
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

	public function testGetNonExistentBinding()
	{
		$this->assertInstanceof('TwoDependenciesClass', $this->container['TwoDependenciesClass']);

		$this->assertInstanceof('OneDependencyClass', $this->container->OneDependencyClass);
	}

	public function testArrayAccessMethods()
	{
		$this->container['test'] = new stdClass;

		$this->container['test']->someAttribute = 'test case';
		
		$this->assertTrue(isset($this->container['test']));
		
		unset($this->container['test']);
		
		$this->assertFalse(isset($this->container['test']));
	}

	public function testMagicAccessMethods()
	{
		$this->container->test = new stdClass;

		$this->container->test->someAttribute = 'test case';
		
		$this->assertTrue(isset($this->container->test));
		
		unset($this->container->test);
		
		$this->assertFalse(isset($this->container->test));
	}

	public function testBind()
	{
		$this->container->bind('a', function ($container) {
			return 'should work';
		}, true);

		$this->assertEquals('should work', $this->container['a']);
	}

	public function testIsBound()
	{
		$this->container->bind('a', 'stdClass');

		$this->assertTrue($this->container->isBound('a'));
	}

	public function testFlush()
	{
		$this->container->bind('a', 'stdClass');

		$this->assertTrue($this->container->isBound('a'));

		$this->container->flush();

		$this->assertFalse($this->container->isBound('a'));
	}

	public function testIsSingleton()
	{
		$this->container->singleton('a', 'stdClass');

		$this->assertTrue($this->container->isSingleton('a'));
	}

	public function testBindSingleton()
	{
		$this->container->bind('a', 'stdClass', true);

		$this->assertTrue($this->container->isSingleton('a'));
	}

	public function testSingleton()
	{
		$this->container->singleton('b', 'stdClass');

		$this->assertTrue($this->container->isSingleton('b'));
	}

	public function testBindIf()
	{
		$this->container->bindIf('a', 'stdClass');

		$this->container->bindIf('a', 'stdClass', true);

		$this->assertTrue($this->container->isSingleton('a'));
	}

	public function testBindInstance()
	{
		$instance = new stdClass;
		
		$instance->test = 'should work';

		$this->container->instance('a', $instance);

		$this->assertEquals('should work', $this->container['a']->test);
	}

	public function testBindToSingleton()
	{
		$instance = new stdClass;
		
		$instance->test = 'should work';

		$this->container->bindTo('OneDependencyClass', 'stdClass', $instance);

		$instance = $this->container->make('OneDependencyClass');

		$this->assertTrue(property_exists($instance->std, 'test'));
	}

	public function testBindToResolvableClosure()
	{

		$this->container->bindTo('OneDependencyClass', 'stdClass', function () {
			$instance = new stdClass;
			$instance->test = 'should work';

			return $instance;		
		});

		$instance = $this->container->make('OneDependencyClass');

		$this->assertTrue(property_exists($instance->std, 'test'));
	}

	public function testBindToResolvableString()
	{
		$this->container->bindTo('OneDependencyClass', 'stdClass', 'stdClass');

		$this->assertInstanceof('OneDependencyClass', $this->container->make('OneDependencyClass'));
	}

	public function testExtendResolvable()
	{
		$this->container->bind('a', 'stdClass');

		$this->container->extend('a', function ($stdClass, $container) {
			$stdClass->test = 'should work';

			return $stdClass;
		});

		$this->assertTrue(property_exists($this->container['a'], 'test'));
	}

	public function testExtendSingleton()
	{
		$this->container->bind('a', 'stdClass', true);

		$this->container->extend('a', function ($stdClass, $container) {
			$stdClass->test = 'should work';

			return $stdClass;
		});

		$this->assertTrue(property_exists($this->container['a'], 'test'));
	}

	public function testShare()
	{
		$this->container->bind('a', 'stdClass');

		$this->container->share('a');

		$this->assertTrue($this->container->isSingleton('a'));
	}

	public function testMakeBinding()
	{
		$this->container->bind('a', 'stdClass');

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
