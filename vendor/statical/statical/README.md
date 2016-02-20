# Statical

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/johnstevenson/statical/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/johnstevenson/statical/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/johnstevenson/statical/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/johnstevenson/statical/?branch=master)
[![Build Status](https://secure.travis-ci.org/johnstevenson/statical.png)](http://travis-ci.org/johnstevenson/statical)

PHP static proxy library.
## Contents
* [About](#About)
* [Usage](#Usage)
* [License](#License)

<a name="About"></a>
## About

**Statical** is a tiny PHP library that enables you to call class methods from a static accessor, so
the static call to `Foo::doSomething()` actually invokes the `doSomething()` method of a specific class
instance. To show a more concrete example:

```php
# Normal access
$app->get('view')->render('mytemplate', $data);

# Using a static proxy
View::render('mytemplate', $data);
```

Both examples call the render method of the instantiated view class, with the static proxy version
using terse and cleaner code. This may or may not be a good thing and depends entirely on your
requirements, usage and point of view.

### How it Works
Everything runs through the `Statical\Manager`. It needs three pieces of data to create
each static proxy:

* an alias *(which calls)*
* a proxy class *(which invokes the method in)*
* the target class

An **alias** is the short name you use for method calling: `Foo`, `View` or whatever.

You create a static **proxy class** like this. Note that its name is irrelevant and it is normally empty:

```
class FooProxy extends \Statical\BaseProxy {}
```

A **target class** is the class whose methods you wish to call. It can be either:

* an actual class instance
* a closure invoking a class instance
* a reference to something in a container or service-locator that resolves to a class instance.

This data is then registered using either the `addProxyInstance()` or the `addProxyService()` methods.
See the [Usage](#Usage) section for some examples.

### Namespaces
By default, each static proxy is registered in the global namespace. This means that any calls to
`Foo` will not work in a namespace unless they are prefixed with a backslash `\Foo`. Alternatively
you can include a *use* statement in each file: `use \Foo as Foo;`.

**Statical** includes a powerful namespacing feature which allows you to add namespace patterns for
an alias. For example `addNamespaceGroup('path', Foo', 'App\\Library')` allows you to call `Foo` in any
*App\\Library* or descendant namespace.

### Features
A few features in no particular order. Please see the [documentation][wiki] for more information.

- **Statical** creates a static proxy to itself, aliased as *Statical* and available in any namespace,
allowing you to call the Manager with `Statical::addProxyService()` or whatever. This feature can be disabled or modified as required.

- You can use any type of container/service-locator. If it implements `ArrayAccess` or has a `get` method then **Statical**
will discover this automatically, otherwise you need to pass a callable as the target container.

- You can use multiple containers when adding proxy services to the Manager.

- If you pass a closure as a proxy instance, it will be invoked once to resolve the target
instance. You can get a reference to this instance, or in fact any target class, by calling the
*getInstance()* method on your alias, for example `Foo::getInstance()`.

- **Statical** is test-friendly. If you register a container then it is used to resolve the
target instance for every proxied call, allowing you to swap in different objects. You can also
replace a proxy by registering a different instance/container with the same alias.


<a name="Usage"></a>
## Usage
Install via [composer][composer]

```
composer require statical/statical
```

Below are some examples. Firstly, using a class instance:

```php
<?php
$alias = 'Foo';
$proxy = 'Name\\Space\\FooInstance';
$instance = new FooClass();

# Create our Manager
$manager = new Statical\Manager();

# Add proxy instance
$manager->addProxyInstance($alias, $proxy, $instance);

# Now we can call FooClass methods via the static alias Foo
Foo::doSomething();
```

For a container or service-locator you would do the following:

```php
<?php
$alias = 'Foo';
$proxy = 'Name\\Space\\FooService';

# FooService id in container
$id = 'bar';

# Add it to the container
$container->set($id, function ($c) {
  return new FooService($c);
});

# Create our Manager
$manager = new Statical\Manager();

# Add proxy service
$manager->addProxyService($alias, $proxy, $container, $id);

# FooService is resolved from the container each time Foo is called
Foo::doSomething();

```

If the container id is a lower-cased version of the alias, then you can omit the `$id` param.
Using the above example:

```php
<?php
$alias = 'Foo';
...
# FooService id in container
$id = 'foo';
...

# Add proxy service - note we don't need to pass the id
$manager->addProxyService($alias, $proxy, $container);
...

```

In the above examples, the service is resolved out of the container automatically. If the container
doesn't implement `ArrayAccess` or doesn't have a `get` method, you need to pass a callable:

```php
<?php
$alias = 'Foo';
$proxy = 'Name\\Space\\FooService';

# Our container uses magic calls
$di->foo = new FooService();

# so we need to pass a callable as the container param
$container = array($di, '__get');
...
# Add proxy service
$manager->addProxyService($alias, $proxy, $container);
...

```
Full usage [documentation][wiki] can be found in the Wiki.

<a name="License"></a>
## License

Statical is licensed under the MIT License - see the `LICENSE` file for details


  [composer]: http://getcomposer.org
  [wiki]:https://github.com/johnstevenson/statical/wiki/Home

