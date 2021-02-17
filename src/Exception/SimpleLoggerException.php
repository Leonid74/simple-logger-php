<?php

/**
 * Main exception class used for error handling
 * Основной класс, используемый для обработки ошибок
 *
 * This file is part of the project.
 *
 * @author    Leonid Sheikman (leonid74)
 * @copyright 2019-2021 Leonid Sheikman
 * @see       https://github.com/Leonid74/simple-logger-php
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Leonid74\SimpleLogger\Exception;

class SimpleLoggerException extends \Exception
{
    public function __construct(string $message = '', $code = 0, \Exception $previous = null)
    {
        $strEOL = (php_sapi_name() == 'cli' ? PHP_EOL : '<br>');
        parent::__construct('SimpleLogger caught the error!' . $strEOL . $strEOL .
                            'Error code: ' . ($code ? $code : 'unknown') . $strEOL .
                            'Error text: ' . $message . $strEOL . $strEOL .
                            'Exitting.', $code, $previous);
    }
}
