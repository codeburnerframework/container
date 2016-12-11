# Codeburner Container

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)
[![Build Status](https://img.shields.io/travis/codeburnerframework/container/master.svg)](https://travis-ci.org/codeburnerframework/container)
[![Code Coverage](https://scrutinizer-ci.com/g/codeburnerframework/container/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/codeburnerframework/container/?branch=master)
[![Quality Score](https://img.shields.io/scrutinizer/g/codeburnerframework/container.svg)](https://scrutinizer-ci.com/g/codeburnerframework/container)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/9af2c429-cc7f-4c71-8eac-e3c3ddd4c1d2/big.png)](https://insight.sensiolabs.com/projects/9af2c429-cc7f-4c71-8eac-e3c3ddd4c1d2)

The faster IoC container package for you build blazing fast applications for the web.

Thanks to [Tom Butler](https://r.je/dice.html) for motivate me with [this announce of dice](https://r.je/dice.html), a fast dependency injection container.

##Instalation

Add `codeburner/container` to your `composer.json` file, and update or install composer dependencies.

```json
{
    "require": {
        "codeburner/container": "^2.0"
    }
}
```

or via CLI:

```bash
$ composer require codeburner/container --save
```

##Usage

- [Introduction](#introduction)
	- [Performance](#performance)
	- [Concepts](#concepts)
	- [Usage](#usage)
- [Bindings](#bindings)
	- [Binding Types](#binding-types)
		- [Resolvable Bindings](#resolvable-bindings)
		- [Resolved Bindings](#resolved-bindings)
	- [Binding Ways](#binding-ways)
		- [Strings](#strings)
		- [Closures](#closures)
		- [Instances](#instances)
	- [Resolving Bindings](#resolving-bindings)
		- [Setting Dependencies Manually](#setting-dependencies-manually)
	- [Extending Bindings](#extending-bindings)

## Introduction
Welcome to the codeburner blazing fast container docs! Before starting the usage is recommended understand the main goal and mission of all parts of this package.

### Performance
Codeburner project create packages with performance in focus, and the benchmarks are comming!

### Concepts
The container is responsable to automatilly instantiate new objects, resolving all class dependencies and storing these objects over aliases. This enable a greater flexibility removing hard-coded class dependencies, and instead, making the dependencies be dinacally injected at run-time.

### Usage
After you have the classes ready to be instantiate, you only need to register the bindings and call then.

```php
use Codeburner\Container\Container;

$container = new Container;

// Regiser a "stdClass" class to a "std" key.
$container->set('std', 'stdClass');

// Accessing new "stdClass" objects.
$container->get('std');
```

## Bindings
Bindings are the objects stored in the container. The container implements the [PSR-11](https://github.com/php-fig/fig-standards/blob/master/proposed/container.md) providing the `get($id)` and `has($id)` methods to access the bindings, and define the `set($id, $concrete)` to store objects.

```php
class ClassA {
	public function __construct(stdClass $dependency) {

	}
}

$container->set('my-a', 'ClassA');

if ($container->has('my-a')) {
	$container->get('my-a');
}
```

### Binding Types

#### Resolvable Bindings
Resolvable bindings will return a new instance in every access.

```php
$container->set('newinstance', 'someobj');
$obj1 = $container->get('newinstance'); // someobj#1
$obj2 = $container->get('newinstance'); // someobj#2
```

#### Resolved Bindings
Resolved bindings are no more than singletons, every access will return the same instance.

```php
$container->set('newinstance', 'someobj', true);
// or
$container->singleton('newinstance', 'someobj');

$obj1 = $container->get('newinstance'); // someobj#1
$obj2 = $container->get('newinstance'); // someobj#1
```

### Binding Ways
#### Strings
The simplest way to define a binding, you only need to give a class name as string.

```php
class ClassNameTest {

}

$container->set('someobj', 'ClassNameTest');
```

#### Closures
Some times you need to set some attributes or make some initial logic on objects, you can do it with a closure binding.

```php
$container->set('someobj', function ($container) {
	$obj = new stdClass;
	$obj->attribute = 1;

	return $obj;
});
```

#### Instances
If you need to attach an existent instance, you should use the `instance` method.

```php
$obj = new stdClass;
$container->instance('std', $obj);
```

### Resolving Bindings
The great goal of the container is to automatically inject all class dependencies, if you only need to create an instance of a class without binding then into container use the `make` method.

```php
class A {
	public function __construct(B $b) {
		$this->b = $b;
	}
}

class B {
	public function __construct() {
		$this->number = rand();
	}
}

$a = $container->make('A');
echo $a->b->number;
```

#### Setting Dependencies Manually
Sometimes you want to define that some class will receive a specific object of another class on instantiation.

```php
class A {
	public function __construct(B $b) {
		$this->b = $b;
	}
}

class B {
	public function __construct() {
		$this->number = rand();
	}
}

$b = new B;
$b->number = 1;

$container->setTo('A', 'B', $b);
$a = $container->make('A');
echo $a->b->number; // 1
```

### Extending Bindings
Some times you need to modify a binding, to do that use the `extend` method. They receive the old binding object and a container reference.

```php
$container->set('someobj', 'stdClass');
$container->extend('someobj', function ($oldSomeObjInstance, $container) {
	// do some logic.
	return $newSomeObjInstance; // or a modified old instance.
});
```
