<?php 

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2015 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Container;

use ArrayAccess;
use ReflectionClass;
use ReflectionFunction;
use Closure;

/**
 * The container class is reponsable to construct all objects
 * of the project automatically, with total abstraction of dependencies.
 * 
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @since 1.0.0
 */

class Container implements ArrayAccess
{

    /**
     * Implement all basic manipulation methods, including the ArrayAccess interface methods
     * and all the magic acessor methods as __get or __set.
     */

    use ContainerCollectionMethods;

    /**
     * Implement all abstraction method, such bind, bindIf, bindTo, share, extends,
     * isBound, isSingleton, flush.
     */

    use ContainerAbstractionMethods;

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

    public function call($function, $parameters = [])
    {
        $inspector = new ReflectionFunction($function);
        $dependencies = $inspector->getParameters();
        $resolvedClosureDependencies = [];

        foreach ($dependencies as $dependency) {
            if (isset($parameters[$dependency->name])) {
                $resolvedClosureDependencies[] = $parameters[$dependency->name];
            } else {
                if (($class = $dependency->getClass()) === null) {
                       $resolvedClosureDependencies[] = $dependency->isOptional() ? $dependency->getDefaultValue() : null;
                } else $resolvedClosureDependencies[] = $this->make($class->name);
            }
        }

        return call_user_func_array($function, $resolvedClosureDependencies);
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
        if ($force === false && isset($this->collection[$abstract])) {
            return $this->offsetGet($abstract);
        }

        if (isset($this->resolving[$abstract])) {
            return $this->resolving[$abstract]($abstract, $parameters);
        }

        $callable = $this->resolving[$abstract] = $this->construct($abstract, $force);
        return $callable($abstract, $parameters);
    }

    /**
     * Construct a class and all the dependencies using the reflection library of PHP.
     *
     * @param string $abstract The class name or container element name to make.
     * @param bool   $force    Specify if a new element must be given and the dependencies must have be recalculated.
     *
     * @throws ReflectionException
     * @return Closure
     */

    protected function construct($abstract, $force)
    {
        $inspector = new ReflectionClass($abstract);

        if ($constructor  = $inspector->getConstructor()) {
            $dependencies = $constructor->getParameters();

            return function ($abstract, $parameters) use ($inspector, $dependencies, $force) {
                $resolvedClassParameters = [];

                foreach ($dependencies as $dependency) {
                    if (isset($parameters[$dependency->name])) {
                           $resolvedClassParameters[] = $parameters[$dependency->name];
                    } else $resolvedClassParameters[] = $this->resolve($abstract, $dependency, $force);
                }

                return $inspector->newInstanceArgs($resolvedClassParameters);
            };
        }
     
        return function ($abstract) {
            return new $abstract;
        };
    }

    /**
     * Resolve all the given class reflected dependencies.
     *
     * @param string               $abstract   The class name or container element name to resolve dependencies.
     * @param ReflectionParameter  $dependency The class dependency to be resolved.
     * @param bool                 $force      Specify if the dependencies must be recalculated.
     *
     * @return Object
     */

    protected function resolve($abstract, $dependency, $force)
    {
        $key = $abstract.$dependency->name;

        if (!isset($this->resolved[$key]) || $force === true) {
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
     * @return Closure
     */

    protected function generate($abstract, $dependency)
    {
        if ($class = $dependency->getClass()) {
            $classname = $class->name;
            $key = $abstract.$classname;

            if (isset($this->dependencies[$key])) {
                   return $this->dependencies[$key];
            } else return function () use ($classname) {
                   return $this->make($classname);
            };
        }

        return $class->getDefaultValue();
    }

}

trait ContainerCollectionMethods
{

    abstract public function make($abstract, $parameters = [], $force = false);
    abstract public function instance($abstract, $instance);
    abstract public function bind($abstract, $concrete, $shared = false);

    public function offsetGet($abstract)
    {
        if (isset($this->collection[$abstract])) {
            $concrete = $this->collection[$abstract];

            if ($concrete instanceof Closure) {
                   return $concrete($this);
            } else return $concrete;
        }

        return $this->make($abstract);
    }

    public function offsetSet($abstract, $value)
    {
        if (is_object($value)) {
               $this->instance($abstract, $value);
        } else $this->bind($abstract, $value);
    }

    public function offsetExists($abstract)
    {
        return isset($this->collection[$abstract]);
    }

    public function offsetUnset($abstract)
    {
        unset($this->collection[$abstract]);
    }

    public function __get($offset)
    {
        return $this->offsetGet(str_replace('_', '.', $offset));
    }

    public function __set($offset, $value)
    {
        $this->offsetSet(str_replace('_', '.', $offset), $value);
    }

    public function __unset($offset)
    {
        $this->offsetUnset(str_replace('_', '.', $offset));
    }

    public function __isset($offset)
    {
        return $this->offsetExists(str_replace('_', '.', $offset));
    }

}

trait ContainerAbstractionMethods
{

    /**
     * Verify if an element exists in container.
     *
     * @return bool
     */

    public function isBound($abstract)
    {
        return isset($this->collection[$abstract]);
    }

    /**
     * Verify if an element has a singleton instance.
     *
     * @return bool
     */

    public function isSingleton($abstract)
    {
        return isset($this->collection[$abstract]);
    }

    /**
     * Reset the container, removing all the elements, cache and options.
     *
     * @return void
     */

    public function flush()
    {
        $this->collection = [];
        $this->dependencies = [];
        $this->resolvable = [];
        $this->resolved = [];
    }

    /**
     * Bind a new element to the container.
     *
     * @param string         $abstract The alias name that will be used to call the element.
     * @param string|closure $concrete The element class name, or an closure that makes the element.
     * @param bool           $shared   Define if the element will be a singleton instance.
     *
     * @return \Codeburner\Container\Container
     */

    public function bind($abstract, $concrete, $shared = false)
    {
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
     * @return \Codeburner\Container\Container
     */

    public function bindIf($abstract, $concrete, $shared = false)
    {
        if (!isset($this->collection[$abstract])) {
            $this->bind($abstract, $concrete, $shared);
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
     * @return \Codeburner\Container\Container
     */

    public function bindTo($class, $dependencyName, $dependency)
    {
        if ($dependency instanceof Closure === false) {
            if (is_object($dependency)) {
                $dependency = function () use ($dependency) {
                    return $dependency;
                };
            } else { 
                $dependency = function () use ($dependency) {
                    return $this->offsetGet($dependency);
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
     * @return \Codeburner\Container\Container
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
     * @return \Codeburner\Container\Container
     */ 

    public function instance($abstract, $instance)
    {
        if (is_object($instance)) {
            $this->collection[$abstract] = $instance;
        }

        return $this;
    }

    /**
     * Modify an element with a given function that receive the old element as argument.
     *
     * @param string  $abstract  The alias name that will be used to call the element.
     * @param closure $extension The function that receives the old element and return a new or modified one.
     *
     * @return \Codeburner\Container\Container
     */

    public function extend($abstract, $extension)
    {
        if (isset($this->collection[$abstract])) {
            $object = $this->collection[$abstract];

            if ($object instanceof Closure === false) {
                   $this->collection[$abstract] = $extension($object, $this);
            } else $this->collection[$abstract] = function () use ($object, $extension) {
                return $extension($object($this), $this);
            };
        }

        return $this;
    }

    /**
     * Makes an resolvable element an singleton.
     *
     * @param string $abstract The alias name that will be used to call the element.
     *
     * @return \Codeburner\Container\Container
     */

    public function share($abstract)
    {
        if (isset($this->collection[$abstract]) && $this->collection[$abstract] instanceof Closure) {
            $this->collection[$abstract] = $this->collection[$abstract]($this);
        }

        return $this;
    }

}


