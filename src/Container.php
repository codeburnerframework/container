<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @copyright 2015 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Container;

use ArrayAccess;
use ReflectionClass;
use Closure;

/**
 * Codeburner Container Component.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @see https://github.com/codeburnerframework/container
 */
class Container implements ArrayAccess
{

    /**
     * Holds all the resolved instances (singletons).
     *
     * @var array
     */
    protected $resolved = [];

    /**
     * All the resolvable callbacks.
     *
     * @var array
     */
    protected $resolvable = [];

    /**
     * Cache of classes inspector and resolver.
     *
     * @var array
     */
    protected $resolving = [];

    /**
     * Class specific defined dependencies.
     *
     * @var array
     */
    protected $dependencies = [];

    /**
     * Cache of classes dependencies in callbacks ready for resolution.
     *
     * @var array
     */
    protected $cached = [];

    /**
     * Verify if an element exists in container.
     *
     * @return bool
     */
    public function isBound($abstract)
    {
        return isset($this->resolved[$abstract]) || isset($this->resolvable[$abstract]);
    }

    /**
     * Verify if an element has a singleton instance.
     *
     * @return bool
     */
    public function isResolved($abstract)
    {
        return isset($this->resolved[$abstract]);
    }

    /**
     * Reset the container, removing all the elements, cache and options.
     *
     * @return void
     */
    public function flush()
    {
        $this->resolved = [];
        $this->resolvable = [];
        $this->dependencies = [];
        $this->cached = [];
    }

    /**
     * Bind a new element to the container.
     *
     * @param string         $abstract The alias name that will be used to call the element.
     * @param string|closure $concrete The element class name, or an closure that makes the element.
     * @param bool           $shared   Define if the element will be a singleton instance.
     *
     * @return Codeburner\Container\Container
     */
    public function bind($abstract, $concrete, $shared = false)
    {
        if (!$concrete instanceof Closure) {
            $concrete = function (Container $container) use ($concrete) {
                return $container->make($concrete);
            };
        }

        if ($shared === true) {
               $this->resolved[$abstract]   = $concrete($this);
        } else $this->resolvable[$abstract] = $concrete;

        return $this;
    }

    /**
     * Bind a new element to the container IF the element name not exists in the container.
     *
     * @param string         $abstract The alias name that will be used to call the element.
     * @param string|closure $concrete The element class name, or an closure that makes the element.
     * @param bool           $shared   Define if the element will be a singleton instance.
     *
     * @return Codeburner\Container\Container
     */
    public function bindIf($abstract, $concrete, $shared = false)
    {
        if (!isset($this->resolved[$abstract]) || !isset($this->resolvable[$abstract])) {
            $this->bind($abstract, $concrete, $shared);
        }

        return $this;
    }

    /**
     * Bind an specific instance to a class dependency.
     *
     * @param string         $class      The class full name.
     * @param string         $dependency The dependency full name.
     * @param string|closure $abstract   The specific object class name or a classure that makes the element.
     *
     * @return Codeburner\Container\Container
     */
    public function bindTo($class, $dependency, $abstract)
    {
        if (is_string($abstract)) {
            $classname = $abstract;
            $abstract = function () use ($classname) {
                return $this->offsetGet($classname);
            };
        } else {
            if (!$abstract instanceof Closure) {
                $abstract = function () use ($abstract) {
                    return $abstract;
                };
            }
        }

        $this->dependencies[$class][$dependency] = $abstract;

        return $this;
    }

    /**
     * Bind an element that will be construct only one time, and every call for the element,
     * the same instance will be given.
     *
     * @param string         $abstract The alias name that will be used to call the element.
     * @param string|closure $concrete The element class name, or an closure that makes the element.
     *
     * @return Codeburner\Container\Container
     */
    public function singleton($abstract, $concrete)
    {
        $this->bind($abstract, $concrete, true);

        return $this;
    }

    /**
     * Bind an object to the container.
     *
     * @param string $abstract The alias name that will be used to call the object.
     * @param object $instance The object that will be inserted.
     *
     * @return Codeburner\Container\Container
     */ 
    public function instance($abstract, $instance)
    {
        if (is_object($instance)) {
            $this->resolved[$abstract] = $instance;
        }

        return $this;
    }

    /**
     * Modify an element with a given function that receive the old element as argument.
     *
     * @param string  $abstract  The alias name that will be used to call the element.
     * @param closure $extension The function that receives the old element and return a new or modified one.
     *
     * @return Codeburner\Container\Container
     */
    public function extend($abstract, $extension)
    {
        if (isset($this->resolved[$abstract])) {
            $this->resolved[$abstract] = $extension($this->resolved[$abstract], $this);
        } else {
            if (isset($this->resolvable[$abstract])) {
                $oldResolvableClosure = $this->resolvable[$abstract];

                $this->resolvable[$abstract] = function () use ($oldResolvableClosure, $extension) {
                    return $extension($oldResolvableClosure($this), $this);
                };
            }
        }

        return $this;
    }

    /**
     * Makes an resolvable element an singleton.
     *
     * @param string $abstract The alias name that will be used to call the element.
     *
     * @return Codeburner\Container\Container
     */
    public function share($abstract)
    {
        if (isset($this->resolvable[$abstract])) {
            $this->resolved[$abstract] = $this->resolvable[$abstract]($this);
            unset($this->resolvable[$abstract]);
        }

        return $this;
    }

    /**
     * Makes an element or class injecting automatically all the dependencies.
     *
     * @param string $abstract   The class name or container element name to make.
     * @param array  $parameters Specific parameters definition.
     * @param bool   $force      Specify if a new element must be given and the dependencies must have be recalculated.
     *
     * @throws ReflectionException
     * @return object|null
     */
    public function make($abstract, $parameters = [], $force = false)
    {
        if ($force === false && $this->isBound($abstract)) {
            return $this->offsetGet($abstract);
        }

        if (isset($this->resolving[$abstract])) {
            return $this->resolving[$abstract]($abstract, $parameters);
        }

        $this->resolving[$abstract] = $this->construct($abstract, $force);
        return $this->resolving[$abstract]($abstract, $parameters);
    }

    /**
     * Construct a class and all the dependencies using the reflection library of PHP.
     *
     * @param string $abstract   The class name or container element name to make.
     * @param array  $parameters Specific parameters definition.
     * @param bool   $force      Specify if a new element must be given and the dependencies must have be recalculated.
     *
     * @throws ReflectionException
     * @return Closure
     */
    protected function construct($abstract, $force)
    {
        $inspector = new ReflectionClass($abstract);
        $constructor = $inspector->getConstructor();
        $dependencies = $constructor ? $constructor->getParameters() : [];

        return function ($abstract, $parameters) use ($inspector, $dependencies, $force) {
            if (empty($dependencies)) {
                return new $abstract;
            }

            return $inspector->newInstanceArgs($parameters ?: $this->resolve($abstract, $dependencies, $force));
        };
    }

    /**
     * Resolve all the given class reflected dependencies.
     *
     * @param string $abstract     The class name or container element name to resolve dependencies.
     * @param array  $dependencies The list of ReflectionParameter of a class.
     * @param bool   $force        Specify if the dependencies must be recalculated.
     *
     * @return array
     */
    protected function resolve($abstract, $dependencies, $force)
    {
        $parameters = [];

        if (!isset($this->cached[$abstract]) || $force === true) {
            $this->cached[$abstract] = $this->generate($abstract, $dependencies);
        }

        foreach ($this->cached[$abstract] as $dependency) {
            $parameters[] = $dependency($this);
        }

        return $parameters;
    }

    /**
     * Generate the dependencies callbacks to jump some conditions in every dependency creation.
     *
     * @param string $abstract     The class name or container element name to resolve dependencies.
     * @param array  $dependencies The list of ReflectionParameter of a class.
     *
     * @return array
     */
    protected function generate($abstract, $dependencies)
    {
        $parameters = [];

        foreach ($dependencies as $dependency) {
            $class = $dependency->getClass();

            if ($class !== null) {
                $classname = $class->name;
                
                if (isset($this->dependencies[$abstract]) && isset($this->dependencies[$abstract][$classname])) {
                    $parameters[] = function () use ($classname, $abstract) {
                        return $this->dependencies[$abstract][$classname]();
                    };
                } else {
                    $parameters[] = function () use ($classname) {
                        return $this->make($classname);
                    };
                }
            }
        }

        return $parameters;
    }

    /**
     * For an array access method
     *
     * @see http://php.net/manual/en/class.arrayaccess.php
     * @return object|null
     */
    public function offsetGet($offset)
    {
        if (isset($this->resolved[$offset])) {
            return $this->resolved[$offset];
        }

        if (isset($this->resolvable[$offset])) {
            return $this->resolvable[$offset]($this);
        }

        return $this->make($offset);
    }

    /**
     * For an array access method
     *
     * @see http://php.net/manual/en/class.arrayaccess.php
     * @return null
     */
    public function offsetSet($offset, $value)
    {
        if (is_object($value)) {
               $this->instance($offset, $value);
        } else $this->bind($offset, $value);
    }

    /**
     * For an array access method
     *
     * @see http://php.net/manual/en/class.arrayaccess.php
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->resolved[$offset]) || isset($this->resolvable[$offset]);
    }

    /**
     * For an array access method
     *
     * @see http://php.net/manual/en/class.arrayaccess.php
     * @return null
     */
    public function offsetUnset($offset)
    {
        unset($this->resolved[$offset]);
        unset($this->resolvable[$offset]);
    }

    /**
     * For an class attribute access method
     *
     * @see http://php.net/manual/en/language.oop5.magic.php
     * @return object|null
     */
    public function __get($offset)
    {
        return $this->offsetGet(str_replace('_', '.', $offset));
    }

    /**
     * For an class attribute access method
     *
     * @see http://php.net/manual/en/language.oop5.magic.php
     * @return object|null
     */
    public function __set($offset, $value)
    {
        $this->offsetSet(str_replace('_', '.', $offset), $value);
    }

    /**
     * For an class attribute access method
     *
     * @see http://php.net/manual/en/language.oop5.magic.php
     * @return object|null
     */
    public function __unset($offset)
    {
        $this->offsetUnset(str_replace('_', '.', $offset));
    }

    /**
     * For an class attribute access method
     *
     * @see http://php.net/manual/en/language.oop5.magic.php
     * @return object|null
     */
    public function __isset($offset)
    {
        return $this->offsetExists(str_replace('_', '.', $offset));
    }

}
