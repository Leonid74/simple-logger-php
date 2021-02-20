<?php declare( strict_types=1 );

/**
 * Main exception class used for error handling
 * Основной класс, используемый для обработки ошибок
 *
 * This file is part of the project.
 *
 * @author Leonid Sheikman (leonid74)
 * @copyright 2019-2021 Leonid Sheikman
 * @see https://github.com/Leonid74/simple-logger-php
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Leonid74\SimpleLogger\Exception;

use Exception;

class SimpleLoggerException extends Exception
{
   /*  public function __construct( string $message, int $code = 0, ?Exception $previous = null )
    {
        $strEOL = ( PHP_SAPI === 'cli' ? PHP_EOL : '<br>' );
        parent::__construct(
            'SimpleLogger caught the error!' . $strEOL . $strEOL .
            'Error code: ' . $code . $strEOL .
            'Error text: ' . $message . $strEOL . $strEOL .
            'Exitting.',
            $code,
            $previous
        );
    } */

    public function __construct( $ex )
    {
        $errorNumber = $ex->getCode();
        if ( $errorNumber !== 404 ) {
            $errorNumber = 500;
        }
        // Sending HTTP Status Code Back to user Browser
        http_response_code( $errorNumber );

        echo "<h2>" . $this->getErrorType( $errorNumber ) . "</h2>";
        echo "<p>Uncaught Exception: '" . get_class( $ex ) . "'</p>";
        echo "<p>Message: '" . $ex->getMessage() . "'</p>";
        echo "<p>Stack Trace:<pre>" . $ex->getTraceAsString() . "</pre></p>";
        echo "<p>Thrown in '" . $ex->getFile() . "' on line " . $ex->getLine() . "</p>";

        /* if ($errorNumber === 404) {
            echo "<h1>Not Found</h1>";
        } else {
            echo "<h1>System Error Occurred</h1>";
        } */
    }

    private function getErrorType( int $errorNumber ): string
    {
        switch ( $errorNumber ) {
            case E_NOTICE:
            case E_USER_NOTICE:
                $type = 'Notice';
                break;
            case E_WARNING:
            case E_USER_WARNING:
                $type = 'Warning';
                break;
            case E_ERROR:
            case E_USER_ERROR:
                $type = 'Fatal Error';
                break;
            default:
                $type = 'Unknown Error';
                break;
        }
        return $type;
    }
}
