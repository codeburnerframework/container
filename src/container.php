<?php

namespace Codeburner\Container;

use InvalidArgumentException;
use ArrayAccess;
use ReflectionClass;
use ReflectionParameter;
use Closure;

class Container implements ArrayAccess
{

	protected $resolved = [];
	protected $resolvable = [];
	protected $dependencies = [];
	protected $cached = [];

	public function bound($abstract)
	{
		if (isset($this->resolved[$abstract]) or isset($this->resolvable[$abstract])) {
			return true;
		}

		return false;
	}

	public function resolved($abstract)
	{
		if (isset($this->resolved[$abstract])) {
			return true;
		}

		return false;
	}

	public function flush()
	{
		$this->resolved = [];
		$this->resolvable = [];
		$this->dependencies = [];
		$this->cached = [];
	}

	public function bind($abstract, $concrete, $shared = false)
	{
		if (!$concrete instanceof Closure) {
			$concrete = function ($app) use ($concrete) {
				return $app->make($concrete);
			};
		}

		if ($shared == true) {
			   $this->resolved[$abstract]   = $concrete($this);
		} else $this->resolvable[$abstract] = $concrete;

		return $this;
	}

	public function bindIf($abstract, $concrete, $shared = false)
	{
		if (!isset($this->resolved[$abstract]) or !isset($this->resolvable[$abstract])) {
			$this->bind($abstract, $concrete, $shared);
		}

		return $this;
	}

	public function bindTo($class, $dependency, $abstract)
	{
		$this->dependencies[$class][$dependency] = $abstract;

		return $this;
	}

	public function singleton($abstract, $concrete)
	{
		$this->bind($abstract, $concrete, false);

		return $this;
	}

	public function instance($abstract, $instance)
	{
		if (is_object($instance)) {
			$this->resolved[$abstract] = $instance;
		}

		return $this;
	}

	public function extend($abstract, $extension)
	{
		if (isset($this->resolvable[$abstract])) {
			$this->resolvable[$abstract] = $extension($this->resolvable[$abstract], $this);
		}

		return $this;
	}

	public function share($abstract)
	{
		if (isset($this->resolvable[$abstract])) {
			$this->resolved[$abstract] = $this->resolvable[$abstract]($this);
			unset($this->resolvable[$abstract]);
		}

		return $this;
	}

	public function make($abstract, $parameters = [], $force = false)
	{
		if ($force == false and $this->bound($abstract)) {
			return $this->offsetGet($abstract);
		}

		if (isset($this->cached[$abstract])) {
			return $this->cached[$abstract]($abstract, $parameters);
		}

		$inspector = new ReflectionClass($abstract);
		$constructor = $inspector->getConstructor();
		$dependencies = $constructor ? $constructor->getParameters() : [];

		$this->cached[$abstract] = function ($abstract, $parameters) use ($inspector, $constructor, $dependencies) {
			if (empty($dependencies)) {
				return new $abstract;
			}

			if (empty($parameters)) {
				foreach ($dependencies as $dependency) {
					$class = $dependency->getClass();

					if ($class != null) {
						if (isset($this->dependencies[$abstract]) and isset($this->dependencies[$abstract][$class->name])) {
							   $parameters[] = $this->dependencies[$abstract][$class->name];
						} else $parameters[] = $this->make($class->name);
					}
				}
			}

			return $inspector->newInstanceArgs($parameters);
		};

		return $this->cached[$abstract]($abstract, $parameters);
	}

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

	public function offsetSet($offset, $value)
	{
		if (is_object($value)) {
			   $this->instance($offset, $value);
		} else $this->bind($offset, $value);
	}

	public function offsetExists($offset)
	{
		return isset($this->resolved[$offset]) or isset($this->resolvable[$offset]);
	}

	public function offsetUnset($offset)
	{
		if (isset($this->resolved[$offset])) {
			unset($this->resolved[$offset]);
		} else {
			if (isset($this->resolvable[$offset])) {
				unset($this->resolvable[$offset]);
			}
		}
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

}
