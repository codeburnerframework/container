<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2015 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Container;

use Closure, Exception, ReflectionClass, ReflectionException, ReflectionFunction, ReflectionParameter;
use Codeburner\Container\Exceptions\{ContainerException, NotFoundException};

/**
 * Explicit Avoiding autoload for the container interface,
 * because it is aways needed.
 */

require_once __DIR__ . '/ContainerInterface.php';

/**
 * The container class is reponsable to construct all objects
 * of the project automatically, with total abstraction of dependencies.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @version 1.0.0
 */

class Container implements ContainerInterface
{

    /**
     * Holds all resolved or resolvable instances into the container.
     *
     * @var array
     */

    protected $collection;

    /**
     * Class specific defined dependencies.
     *
     * @var array
     */

    protected $dependencies;

    /**
     * Cache of classes inspector and resolver.
     *
     * @var array
     */

    protected $resolving;

    /**
     * Cache of classes dependencies in callbacks ready for resolution.
     *
     * @var array
     */

    protected $resolved;

    /**
     * Call a user function injecting the dependencies.
     *
     * @param string|Closure $function   The function or the user function name.
     * @param array          $parameters The predefined dependencies.
     *
     * @return mixed
     */

    public function call($function, array $parameters = [])
    {
        $inspector = new ReflectionFunction($function);

        $dependencies = $inspector->getParameters();
        $dependencies = $this->process('', $parameters, $dependencies);

        return call_user_func_array($function, $dependencies);
    }

    /**
     * Makes an element or class injecting automatically all the dependencies.
     *
     * @param string $abstract   The class name or container element name to make.
     * @param array  $parameters Specific parameters definition.
     *
     * @throws ContainerException
     * @return object|null
     */

    public function make(string $abstract, array $parameters = [])
    {
        try {
            if (! isset($this->resolving[$abstract])) {
                $this->resolving[$abstract] = $this->construct($abstract);
            }

            return $this->resolving[$abstract]($abstract, $parameters);
        } catch (ReflectionException $e) {
            throw new ContainerException("Fail while attempt to make '$abstract'", 0, $e);
        }
    }

    /**
     * Construct a class and all the dependencies using the reflection library of PHP.
     *
     * @param string $abstract The class name or container element name to make.
     *
     * @throws ReflectionException
     * @return Closure
     */

    protected function construct(string $abstract) : Closure
    {
        $inspector = new ReflectionClass($abstract);

        if (($constructor = $inspector->getConstructor()) && ($dependencies = $constructor->getParameters())) {

            // if, and only if, a class has a constructor with parameters, we try to solve then
            // creating a resolving callback that in every call will recalculate all dependencies
            // for the given class, and offcourse, using a cached resolving callback if exists.

            return function (string $abstract, array $parameters) use ($inspector, $dependencies) {
                return $inspector->newInstanceArgs(
                    $this->process($abstract, $parameters, $dependencies)
                );
            };
        }

        return function (string $abstract) {
            return new $abstract;
        };
    }

    /**
     * Process all dependencies
     *
     * @param string $abstract     The class name or container element name to make
     * @param array  $parameters   User defined parameters that must be used instead of resolved ones
     * @param array  $dependencies Array of ReflectionParameter
     *
     * @throws ContainerException When a dependency cannot be solved.
     * @return array
     */

    protected function process(string $abstract, array $parameters, array $dependencies) : array
    {
        foreach ($dependencies as &$dependency) {
            if (isset($parameters[$dependency->name])) {
                   $dependency = $parameters[$dependency->name];
            } else $dependency = $this->resolve($abstract, $dependency);
        }

        return $dependencies;
    }

    /**
     * Resolve all the given class reflected dependencies.
     *
     * @param string               $abstract   The class name or container element name to resolve dependencies.
     * @param ReflectionParameter  $dependency The class dependency to be resolved.
     *
     * @throws ContainerException When a dependency cannot be solved.
     * @return Object
     */

    protected function resolve(string $abstract, ReflectionParameter $dependency)
    {
        $key = $abstract.$dependency->name;

        if (! isset($this->resolved[$key])) {
            $this->resolved[$key] = $this->generate($abstract, $dependency);
        }

        return $this->resolved[$key]($this);
    }

    /**
     * Generate the dependencies callbacks to jump some conditions in every dependency creation.
     *
     * @param string               $abstract   The class name or container element name to resolve dependencies.
     * @param ReflectionParameter  $dependency The class dependency to be resolved.
     *
     * @throws ContainerException When a dependency cannot be solved.
     * @return Closure
     */

    protected function generate(string $abstract, ReflectionParameter $dependency) : Closure
    {
        if ($class = $dependency->getClass()) {
            return $this->build($class->name, "{$abstract}{$class->name}");
        }

        try {
            $value = $dependency->getDefaultValue();

            return function () use ($value) {
                return $value;
            };
        } catch (ReflectionException $e) {
            throw new ContainerException("Cannot resolve '$dependency->name' of '$abstract'", 0, $e);
        }
    }

    /**
     * Create a build closure for a given class
     *
     * @param string $classname The class that need to be build
     * @param string $entry     Cache entry to search
     *
     * @return Closure
     */

    protected function build(string $classname, string $entry) : Closure
    {
        if (isset($this->dependencies[$entry])) {
            return $this->dependencies[$entry];
        }

        return function () use ($classname) {
            return $this->make($classname);
        };
    }

    /**
     * Reset the container, removing all the elements, cache and options.
     *
     * @return ContainerInterface
     */

    public function flush() : ContainerInterface
    {
        $this->collection = [];
        $this->dependencies = [];
        $this->resolving = [];
        $this->resolved = [];

        return $this;
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $abstract Identifier of the entry to look for.
     *
     * @throws NotFoundException  No entry was found for this identifier.
     * @throws ContainerException Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($abstract)
    {
        if (! isset($this->collection[$abstract])) {
            throw new NotFoundException("Element '$abstract' not found");
        }

        if ($this->collection[$abstract] instanceof Closure) {
            try {
                return $this->collection[$abstract]($this);
            } catch (Exception $e) {
                throw new ContainerException("An exception was thrown while attempt to make $abstract", 0, $e);
            }
        }

        return $this->collection[$abstract];
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($abstract)` returning true does not mean that `get($abstract)` will not throw an exception.
     * It does however mean that `get($abstract)` will not throw a `NotFoundException`.
     *
     * @param string $abstract Identifier of the entry to look for.
     *
     * @return boolean
     */

    public function has($abstract)
    {
        return isset($this->collection[$abstract]);
    }

    /**
     * Verify if an element has a singleton instance.
     *
     * @param  string The class name or container element name to resolve dependencies.
     * @return bool
     */

    public function isSingleton(string $abstract) : bool
    {
        return $this->has($abstract) && $this->collection[$abstract] instanceof Closure === false;
    }

    /**
     * Verify if an element is a instance of something.
     *
     * @param  string The class name or container element name to resolve dependencies.
     * @return bool
     */
    public function isInstance(string $abstract) : bool
    {
        return $this->has($abstract) && is_object($this->collection[$abstract]);
    }

    /**
     * Bind a new element to the container.
     *
     * @param string                $abstract The alias name that will be used to call the element.
     * @param string|closure|object $concrete The element class name, or an closure that makes the element, or the object itself.
     * @param bool                  $shared   Define if the element will be a singleton instance.
     *
     * @return ContainerInterface
     */

    public function set(string $abstract, $concrete, bool $shared = false) : ContainerInterface
    {
        if (is_object($concrete)) {
            return $this->instance($abstract, $concrete);
        }

        if ($concrete instanceof Closure === false) {
            $concrete = function (Container $container) use ($concrete) {
                return $container->make($concrete);
            };
        }

        if ($shared === true) {
               $this->collection[$abstract] = $concrete($this);
        } else $this->collection[$abstract] = $concrete;

        return $this;
    }

    /**
     * Bind a new element to the container IF the element name not exists in the container.
     *
     * @param string         $abstract The alias name that will be used to call the element.
     * @param string|closure $concrete The element class name, or an closure that makes the element.
     * @param bool           $shared   Define if the element will be a singleton instance.
     *
     * @return ContainerInterface
     */

    public function setIf(string $abstract, $concrete, bool $shared = false) : ContainerInterface
    {
        if (! isset($this->collection[$abstract])) {
            $this->set($abstract, $concrete, $shared);
        }

        return $this;
    }

    /**
     * Bind an specific instance to a class dependency.
     *
     * @param string         $class          The class full name.
     * @param string         $dependencyName The dependency full name.
     * @param string|closure $dependency     The specific object class name or a classure that makes the element.
     *
     * @return ContainerInterface
     */

    public function setTo(string $class, string $dependencyName, $dependency) : ContainerInterface
    {
        if ($dependency instanceof Closure === false) {
            if (is_object($dependency)) {
                $dependency = function () use ($dependency) {
                    return $dependency;
                };
            } else {
                $dependency = function () use ($dependency) {
                    return $this->get($dependency);
                };
            }
        }

        $this->dependencies[$class.$dependencyName] = $dependency;

        return $this;
    }

    /**
     * Bind an element that will be construct only one time, and every call for the element,
     * the same instance will be given.
     *
     * @param string         $abstract The alias name that will be used to call the element.
     * @param string|closure $concrete The element class name, or an closure that makes the element.
     *
     * @return ContainerInterface
     */

    public function singleton(string $abstract, $concrete) : ContainerInterface
    {
        $this->set($abstract, $concrete, true);

        return $this;
    }

    /**
     * Bind an object to the container.
     *
     * @param string $abstract The alias name that will be used to call the object.
     * @param object $instance The object that will be inserted.
     *
     * @throws ContainerException When $instance is not an object.
     * @return ContainerInterface
     */

    public function instance(string $abstract, $instance) : ContainerInterface
    {
        if (! is_object($instance)) {
            throw new ContainerException('Trying to store ' . gettype($instance) . ' as object.');
        }

        $this->collection[$abstract] = $instance;

        return $this;
    }

    /**
     * Modify an element with a given function that receive the old element as argument.
     *
     * @param string  $abstract  The alias name that will be used to call the element.
     * @param closure $extension The function that receives the old element and return a new or modified one.
     *
     * @throws NotFoundException  When no element was found with $abstract key.
     * @return ContainerInterface
     */

    public function extend(string $abstract, closure $extension) : ContainerInterface
    {
        $object = $this->get($abstract);

        $this->collection[$abstract] = $extension($object, $this);

        return $this;
    }

    /**
     * Makes an resolvable element an singleton.
     *
     * @param  string $abstract The alias name that will be used to call the element.
     *
     * @throws NotFoundException  When no element was found with $abstract key.
     * @throws ContainerException When the element on $abstract key is not resolvable.
     *
     * @return ContainerInterface
     */

    public function share(string $abstract) : ContainerInterface
    {
        $object = $this->get($abstract);

        $this->collection[$abstract] = $object;

        return $this;
    }

}
