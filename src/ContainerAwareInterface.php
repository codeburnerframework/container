<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @copyright 2015 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Container;

/**
 * Codeburner Container Component.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @see https://github.com/codeburnerframework/container
 */
interface ContainerAwareInterface
{

    /**
     * Inject the container instance into the class.
     *
     * @param \Codeburner\Container\Container $container
     */
    public function setContainer(Container $container);

    /**
     * get the container instance.
     *
     * @return \Codeburner\Container\Container $container
     */
    public function getContainer();

}
