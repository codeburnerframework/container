<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2015 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Container;

use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * Container Interface that must be implemented by every container
 * and container decorator instance.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @version 1.0.0
 */

interface ContainerInterface extends PsrContainerInterface {

    /**
     * Call a user function injecting the dependencies.
     *
     * @param string|Closure $function   The function or the user function name.
     * @param array          $parameters The predefined dependencies.
     *
     * @return mixed
     */

    public function call($function, array $parameters = []);

    /**
     * Makes an element or class injecting automatically all the dependencies.
     *
     * @param string $abstract   The class name or container element name to make.
     * @param array  $parameters Specific parameters definition.
     *
     * @throws ContainerException
     * @return object|null
     */

    public function make(string $abstract, array $parameters = []);

    /**
     * Bind a new element to the container.
     *
     * @param string                $abstract The alias name that will be used to call the element.
     * @param string|closure|object $concrete The element class name, or an closure that makes the element, or the object itself.
     * @param bool                  $shared   Define if the element will be a singleton instance.
     *
     * @return self
     */

    public function set(string $abstract, $concrete, bool $shared = false) : self;

    /**
     * Bind a new element to the container IF the element name not exists in the container.
     *
     * @param string         $abstract The alias name that will be used to call the element.
     * @param string|closure $concrete The element class name, or an closure that makes the element.
     * @param bool           $shared   Define if the element will be a singleton instance.
     *
     * @return self
     */

    public function setIf(string $abstract, $concrete, bool $shared = false) : self;

    /**
     * Bind an element that will be construct only one time, and every call for the element,
     * the same instance will be given.
     *
     * @param string         $abstract The alias name that will be used to call the element.
     * @param string|closure $concrete The element class name, or an closure that makes the element.
     *
     * @return self
     */

    public function singleton(string $abstract, $concrete) : self;

    /**
     * Bind an object to the container.
     *
     * @param string $abstract The alias name that will be used to call the object.
     * @param object $instance The object that will be inserted.
     *
     * @throws ContainerException When $instance is not an object.
     * @return self
     */

    public function instance(string $abstract, $instance) : self;

}
