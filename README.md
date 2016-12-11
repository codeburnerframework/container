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
        - [Examples](#examples)
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
- [Exceptions](#exceptions)
- [API](#api)

## Introduction
Welcome to the [Codeburner](http://github.com/codeburnerframework) blazing fast container docs! Before starting the usage is recommended understand the main goal and mission of all parts of this package.

### Performance
[Codeburner](http://github.com/codeburnerframework) project create packages with performance in focus, and the benchmarks are comming!

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

#### Examples
Usage examples are comming soon.

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
$container->set('app.model.posts', App\Model\Post::class);

$obj1 = $container->get('app.model.posts'); // App\Model\Post#1
$obj2 = $container->get('app.model.posts'); // App\Model\Post#2
```

#### Resolved Bindings
Resolved bindings are no more than singletons, every access will return the same instance.

```php
// you can define by passing a third parameter to set
$container->set('database', App\Database::class, true);
// or using the `singleton` method
$container->singleton('database', App\Database::class);

$obj1 = $container->get('database'); // App\Database#1
$obj2 = $container->get('database'); // App\Database#1
```

### Binding Ways
#### Strings
The simplest way to define a binding, you only need to give a class name as string.

```php
class ClassNameTest {

}

$container->set('someobj', ClassNameTest::class);
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
If you need to attach an existent instance, you should use the `set` or `instance` method.

```php
$obj = new stdClass;

// you can set instances directly by the set method
$container->set('std', $obj);
// or use the `instance` method
$container->instance('std', $obj);
```

### Resolving Bindings
The great goal of the container is to automatically inject all class dependencies, if you only need to create an instance of a class without binding then into container use the `make` method.

```php
class Post {
	public function __construct(Category $category) {
		$this->category = $category;
	}
}

class Category {
	public function __construct() {
		$this->id = rand();
	}
}

$post = $container->make(Post::class);

echo $post->category->id;
```

#### Setting Dependencies Manually
Sometimes you want to define that some class will receive a specific object of another class on instantiation.

```php
class Post {
	public function __construct(Category $category) {
		$this->category = $category;
	}
}

class Category {
	public function __construct() {
		$this->id = rand();
	}
}

$category = new Category;
$category->id = 1;

$container->setTo(Post::class, Category::class, $category);
$post = $container->make(Post::class);

echo $post->category->id; // 1
```

`make($abstract, $parameters = [], $force = false)` accepts a second parameter to defined resolved dependencies, and a third to ensure that a new object will be created.

```php
$post = $container->make(Post::class, [Category::class => new Category], true);
```

#### Executing Closures
If you have a closure with dependencies you can use the `call($closure, $parameters = [])` method to resolve then.

```php
$container->call(function (User $user, Posts $posts) {
    // ...
});
```
And as well as `make`, you can pass an array of resolved dependencies.

```php
$container->call(function (User $user, Posts $posts) {}, [User::class => new User]);
```

### Extending Bindings
Some times you need to modify a binding, to do that use the `extend` method. They receive the old binding object and a container reference.

```php
$container->set('app.services.mail', App\Services\MailService::class);

$container->extend('app.services.mail', function ($instance, $container) {
	$instance->environment('development');

    $instance->setHtmlWrapper($container->get('app.wrappers.html'));

	return $instance;
});
```

## Exceptions
The [Codeburner](http://github.com/codeburnerframework) Container implements [PSR-11](https://github.com/php-fig/fig-standards/blob/master/proposed/container.md) providing two types of exceptions, the `Psr\Container\Exception\NotFoundException` and `Psr\Container\Exception\ContainerException`.

- [ContainerException](https://github.com/codeburnerframework/container/blob/master/src/exceptions/ContainerException.php)
    - [NotFoundException](https://github.com/codeburnerframework/container/blob/master/src/exceptions/NotFoundException.php)

## API

- [Container](https://github.com/codeburnerframework/container/blob/master/src/container.php)
    - `call(closure $closure, array $parameters = []) : mixed` Execute a closure resolving its dependencies
    - `make(string $abstract, array $parameters = [], bool $force = false) : mixed` Resolve something in the container
    - `flush() : Container` Renew the container
    - `get(string $abstract) : mixed` Get something stored in the container
    - `has(string $abstract) : bool` Verify if something is stored in the container
    - `set(string $abstract, $concrete, bool $shared = false) : Container` Store something in the container
    - `setIf(string $abstract, $concrete, bool $shared = false) : Container` Store something in the container if it does not already exists
    - `setTo(string $abstract, string $dependencyName, $dependency) : Container` Define a resolved dependency to something in the container
    - `singleton(string $abstract, $concrete) : Container` Store a new singleton object
    - `instance(string $abstract, $concrete) : Container` Store a new instantiated class
    - `isSingleton(string $abstract) : bool` Verify if something in the container is a singleton
    - `isInstance(string $abstract) : bool` Verify if something in the container is a instance
    - `extend(string $abstract, closure $extender) : Container` Wrap something instantiation
    - `share(string $abstract) : Container` Convert something to a singleton
