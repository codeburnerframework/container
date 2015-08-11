# Codeburner IoC Container System

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)
[![Build Status](https://img.shields.io/travis/codeburnerframework/container/master.svg)](https://travis-ci.org/codeburnerframework/container)
[![Code Coverage](https://scrutinizer-ci.com/g/codeburnerframework/container/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/codeburnerframework/container/?branch=master)
[![Quality Score](https://img.shields.io/scrutinizer/g/codeburnerframework/container.svg)](https://scrutinizer-ci.com/g/codeburnerframework/container)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/9af2c429-cc7f-4c71-8eac-e3c3ddd4c1d2/big.png)](https://insight.sensiolabs.com/projects/9af2c429-cc7f-4c71-8eac-e3c3ddd4c1d2)

The faster IoC container package for you build blazing fast applications for the web.

##Instalation

###Manual
[Download the zip](https://github.com/codeburnerframework/container/archive/master.zip) and extract into your directory, the include the `container.php` file, and that's it!

###Composer
add `codeburner/container` to your `composer.json` file.

```json
{
	"require": {
		"codeburner/container": "dev-master"		
	}
}
```
Don't forget to install or update the composer and include the `vendor/autoload.php` file.

##Usage

- [Basic Usage](#basic-usage)
- [Access Ways](#access-ways)
	- [As Array](#as-array)
	- [As Attribute](#as-attribute)
- [Binding Types](#binding-types)
	- [Resolvable Bindings](#resolvable-bindings)
	- [Resolved Bindings](#resolved-bindings)
- [Binding Ways](#binding-ways)
	- [Strings](#strings)
	- [Closures](#closures)
	- [Instances](#instances)
- [Extending Bindings](#extending-bindings)
- [Resolving Bindings](#resolving-bindings)
	- [Setting Dependencies Manually](#setting-dependencies-manually)

###Basic Usage
After you have the classes ready to be instantiate, you only need to register the bindings and call then.

```php
use Codeburner\Container\Container;

$container = new Container;

// Regiser a "stdClass" class to a "std" key.
$container->bind('std', 'stdClass');

// Accessing new "stdClass" objects.
$container->std;
```

###Access Ways
####As Array
```php
$container['binding.name'];
```
####As Attribute
```php
// Note that dots(.) will become underscores(_)
$container->binding_name;
```
###Binding Types
####Resolvable Bindings
Resolvable bindings will return a new instance in every access.
```php
$container->bind('newinstance', 'someobj');
$obj1 = $container->newinstance; // someobj#1
$obj2 = $container->newinstance; // someobj#2
```
####Resolved Bindings
Resolved bindings are no more than singletons, every access will return the same instance.
```php
$container->bind('newinstance', 'someobj', true);
// or
$container->singleton('newinstance', 'someobj');
$obj1 = $container->newinstance; // someobj#1
$obj2 = $container->newinstance; // someobj#1
```
###Binding Ways
####Strings
The simplest way to define a binding, you only need to give a class name as string.
```php
class ClassNameTest
{

}

$container->bind('someobj', 'ClassNameTest');
```
####Closures
Some times you need to set some attributes or make some initial logic on objects, you can do it with a closure binding.
```php
$container->bind('someobj', function ($container) {
	$obj = new stdClass;
	$obj->attribute = 1;

	return $obj;
});
```
####Instances
If you need to attach an existent instance, you should use the `instance` method.
```php
$obj = new stdClass;
$container->instance('std', $obj);
```
###Extending Bindings
Some times you need to modify a binding, to do that use the `extend` method. They receive the old binding object and a container reference.
```php
$container->bind('someobj', 'stdClass');
$container->extend('someobj', function ($oldSomeObjInstance, $container) {
	// do some logic.
	return $newSomeObjInstance; // or a modified old instance.
});
```
###Resolving Bindings
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
####Setting Dependencies Manually
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

$container->bindTo('A', 'B', $b);
$a = $container->make('A');
echo $a->b->number; // 1
```
