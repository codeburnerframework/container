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

trait ContainerAwareTrait
{

    /**
     * The instance of container.
     *
     * @var ContainerInterface
     */

    protected $container;

    /**
     * Set a container.
     *
     * @param ContainerInterface $container
     * @return mixed
     */

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Get the container.
     *
     * @return ContainerInterface
     */

    public function getContainer()
    {
        return $this->container;
    }

}
