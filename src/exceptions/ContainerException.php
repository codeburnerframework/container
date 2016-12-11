<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @copyright 2015 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Container\Exceptions;

use Exception;
use Psr\Container\Exception\ContainerException as ContainerExceptionInterface;

/**
 * Exception thrown when a generic error occurs while creating something.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @since 1.0.0
 */
class ContainerException extends Exception implements ContainerExceptionInterface
{

}
