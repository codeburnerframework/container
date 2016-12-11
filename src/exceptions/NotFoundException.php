<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @copyright 2015 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Container\Exceptions;

use Psr\Container\Exception\NotFoundException as NotFoundExceptionInterface;

/**
 * Exception thrown when no entry was found in container while
 * calling the get method.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @since 2.0.0
 */
class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{

}
