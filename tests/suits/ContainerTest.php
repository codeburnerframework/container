<?php

use Codeburner\Container\{Container, ContainerInterface};
use Codeburner\Container\Exceptions\{ContainerException, NotFoundException};

class ContainerTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var Container $container
     */
    protected $container;

    /**
     * Test constructor
     *
     * @return void
     */
	public function setUp()
	{
		$this->container = new Container;
	}

	public function testMakeClass()
	{
		$this->assertInstanceof(stdClass::class, $this->container->make(stdClass::class));
	}

	public function testBindSingleton()
	{
		$this->container->singleton('my-std-class', stdClass::class);

		$this->assertTrue($this->container->get('my-std-class') instanceof stdClass);
        $this->assertTrue($this->container->get('my-std-class') == $this->container->get('my-std-class'));
	}

	public function testMakeInvalid()
	{
		$this->setExpectedException(Throwable::class);

		$this->container->make(SomeClassName::class);
	}

	public function testSimpleDependencyInjection()
	{
		$obj = $this->container->make(OneDependencyClass::class);

		$this->assertInstanceof(OneDependencyClass::class, $obj);
        $this->assertInstanceof(stdClass::class, $obj->std);
	}

	public function testMultipleDependencyInjection()
	{
		$obj = $this->container->make(TwoDependenciesClass::class);

        $this->assertInstanceof(TwoDependenciesClass::class, $obj);
        $this->assertInstanceof(OneDependencyClass::class, $obj->odc);
        $this->assertInstanceof(stdClass::class, $obj->std);
	}

	public function testNotSingletonDependenciesResolution()
	{
		$a = $this->container->make(DinamicAttributeDependencyClass::class, [], true);
		$b = $this->container->make(DinamicAttributeDependencyClass::class, [], true);

		$this->assertTrue($a->dac->number != $b->dac->number);
	}

	public function testSet()
	{
		$this->container->set('test-callable', function ($container) {
			return 'should work';
		});

		$this->assertEquals('should work', $this->container->get('test-callable'));
	}

	public function testIsBound()
	{
		$this->container->set('std-class', stdClass::class);

		$this->assertTrue($this->container->has('std-class'));
	}

	public function testFlush()
	{
		$this->container->set('std-class', stdClass::class);

		$this->assertTrue($this->container->has('std-class'));

		$this->container->flush();

		$this->assertFalse($this->container->has('std-class'));
	}

	public function testIsSingleton()
	{
		$this->container->singleton('my-binding', stdClass::class);

		$this->assertTrue($this->container->isSingleton('my-binding'));
	}

	public function testSetSingleton()
	{
		$this->container->set('my-binding', stdClass::class, true);

		$this->assertTrue($this->container->isSingleton('my-binding'));
	}

	public function testSingleton()
	{
		$this->container->singleton('my-binding', stdClass::class);

		$this->assertTrue($this->container->isSingleton('my-binding'));
	}

	public function testSetIf()
	{
		$this->container->setIf('my-binding', stdClass::class);

		$this->container->setIf('my-binding', stdClass::class, true);

		$this->assertTrue(! $this->container->isSingleton('my-binding'));
	}

	public function testSetInstance()
	{
		$instance = new stdClass;

		$instance->test = 'should work';

		$this->container->instance('my-binding', $instance);

		$this->assertEquals('should work', $this->container->get('my-binding')->test);
	}

	public function testSetToSingleton()
	{
		$instance = new stdClass;

		$instance->test = 'should work';

		$this->container->setTo(OneDependencyClass::class, stdClass::class, $instance);

		$instance = $this->container->make(OneDependencyClass::class);

		$this->assertTrue(property_exists($instance->std, 'test'));
	}

	public function testSetToResolvableClosure()
	{
		$this->container->setTo(OneDependencyClass::class, stdClass::class, function () {
			$instance = new stdClass;

			$instance->test = 'should work';

			return $instance;
		});

		$instance = $this->container->make(OneDependencyClass::class);

		$this->assertTrue(property_exists($instance->std, 'test'));
	}

	public function testSetToResolvableString()
	{
		$this->container->setTo(OneDependencyClass::class, stdClass::class, $this->container->make(stdClass::class));

		$this->assertInstanceof(OneDependencyClass::class, $this->container->make(OneDependencyClass::class));
	}

	public function testExtendResolvable()
	{
		$this->container->set('my-binding', stdClass::class);

		$this->container->extend('my-binding', function ($stdClass, $container) {
			$stdClass->test = 'should work';

			return $stdClass;
		});

		$this->assertTrue(property_exists($this->container->get('my-binding'), 'test'));
	}

	public function testExtendSingleton()
	{
		$this->container->set('my-binding', stdClass::class, true);

		$this->container->extend('my-binding', function ($stdClass, $container) {
			$stdClass->test = 'should work';

			return $stdClass;
		});

		$this->assertTrue(property_exists($this->container->get('my-binding'), 'test'));
	}

	public function testShare()
	{
		$this->container->set('my-binding', stdClass::class);

		$this->container->share('my-binding');

		$this->assertTrue($this->container->isSingleton('my-binding'));
	}

	public function testMakeseting()
	{
		$this->container->set('my-binding', stdClass::class);

		$this->assertInstanceof(stdClass::class, $this->container->get('my-binding'));
	}

	public function testContainerAware()
	{
		$instance = $this->container->make('ContainerAwareClass');

		$instance->setContainer($this->container);

		$this->assertInstanceof(ContainerInterface::class, $instance->getContainer());
	}

	public function testCallFunction()
	{
		$this->assertTrue(
            $this->container->call(function () {
    			return true;
    		})
        );
	}

	public function testCallFunctionWithDependencies()
	{
		$this->assertTrue(
            $this->container->call(function (TwoDependenciesClass $b) {
    			return isset($b->odc) && isset($b->std);
    		})
        );
	}

	public function testForcedDependency()
	{
		$test = new stdClass;
		$test->test = true;

		$this->assertTrue(
            $this->container->call(
                function (stdClass $std) {
        			return isset($std->test);
        		},
                [
                    'std' => $test
                ]
            )
        );
	}

    public function testGetDefaultValue()
    {
        $obj = $this->container->make(DefaultDependencyClass::class);

        $this->assertInstanceof(TwoDependenciesClass::class, $obj->tdc);
        $this->assertTrue($obj->default);
    }

    public function testNotFound()
    {
        $this->setExpectedException(Throwable::class);

        $this->container->get('some-unknown-key');
    }

    public function testNotFoundExtend()
    {
        $this->setExpectedException(Throwable::class);

        $this->container->extend('some-unknown-key', function ($obj, $container) {
            return true;
        });
    }

    public function testNotFoundShare()
    {
        $this->setExpectedException(Throwable::class);

        $this->container->share('some-unknown-key');
    }

    public function testErrorResolving()
    {
        $this->setExpectedException(Throwable::class);

        $this->container->set('test', function () {
            throw new Exception('error during execution');
        });

        $this->container->get('test');
    }

    public function testInstanceWithoutObject()
    {
        $this->setExpectedException(Throwable::class);

        $this->container->instance('test', 'some-random-parameter');
    }

    public function testShareException()
    {
        $this->setExpectedException(Throwable::class);

        $this->container->instance('test', $this->container->make(OneDependencyClass::class));

        $this->container->share('test');
    }

    public function testUnresolvableClass()
    {
        $this->setExpectedException(Throwable::class);

        $this->container->make(UnresolvableDependencyClass::class);
    }

    public function testIsInstance()
    {
        $this->container->instance('test', $this->container->make(OneDependencyClass::class));

        $this->assertTrue($this->container->isInstance('test'));
    }

}
