<?php declare( strict_types=1 );

/**
 * Simple logger class that creates logs when an exception is thrown and
 * sends debugging information to the screen and via email (optional)
 * Простой логгер, который записывает информацию в лог файл, когда срабатывает
 * исключение, показывает эту информацию на экране и отправляет на почту (опционально)
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

namespace Leonid74\SimpleLogger;

use Leonid74\SimpleLogger\Exception\SimpleLoggerException;

class SimpleLogger
{

    /**
     * Is logger enabled
     * Включен ли логгер
     */
    public bool $isEnable = false;

    /**
     * Directory for the log file
     * Каталог для лог файла
     */
    public string $strLogFileDir = 'logs';

    /**
     * String for the default timezone like 'Europe/Moscow' or 'UTC' etc.
     * Строка для установки таймзоны по-умолчанию
     */
    public string $strTimezone = 'Europe/Moscow';

    /**
     * Log filename
     * Имя логфайла
     */
    protected string $strLogFileName;

    /**
     * Full path to the logfile
     * Полный путь с именем для логфайла
     */
    protected string $strLogFilePath;

    /**
     * Time of the last save operation
     * Время последнего сохранения информации
     */
    protected float $timeLastSave;

    /**
     * Unique identifier for the log file
     * Уникальный идентификатор для логфайла
     */
    protected string $strUniqId;

    /**
     * Tag of carriage returns based on the type of run (PHP_EOL или '<br>')
     * Тег перевода строки, исходя из типа запуска (PHP_EOL или '<br>')
     */
    protected string $strEol;

    /**
     * Open tag of pre-formatted text based on the type of run (cli = '' or web = '<pre>')
     * Открывающий тег предварительно форматированного текста, исходя из типа запуска (cli = '' или web = '<pre>')
     */
    protected string $strPreOpen;

    /**
     * Close tag of pre-formatted text based on the type of run (cli = '' or web = '</pre>')
     * Закрывающий тег предварительно форматированного текста, исходя из типа запуска (cli = '' или web = '</pre>')
     */
    protected string $strPreClose;

    /**
     * Instances array for the each Log filename
     * Массив экземпляров для уже существующих лог файлов
     */
    private static array $_arrInstances = [];

    /**
     * Get instance object of \SimpleLogger
     * Получаем объект экземпляра \SimpleLogger
     *
     * @param string $strLogFileName Log filename
     * @return \SimpleLogger\RequestLogger|false
     */
    public static function getInstance( string $strLogFileName = 'debug.log' ): self
    {
        if ( !isset( self::$isEnable ) || !self::$isEnable ) {
            return false;
        }

        if ( !isset( self::$_arrInstances[$strLogFileName] ) ) {
            self::$_arrInstances[$strLogFileName] = new self( $strLogFileName );
        }

        return self::$_arrInstances[$strLogFileName];
    }

    /**
     * Sets the default timezone
     * Устанавливаем временную зону по-умолчанию.
     *
     * @param string $strTimezone String like 'Europe/Moscow' or 'UTC' etc.
     *
     * @return bool
     */
    public function setDefaultTimezone( string $strTimezone ): bool
    {
        if ( !$this->isEnable ) {
            return false;
        }

        if ( \strtolower( \trim( $strTimezone ) ) === \strtolower( $this->strTimezone ) ) {
            return true;
        }

        if ( !\date_default_timezone_set( $strTimezone ) ) {
            throw new \Exception( 'Cannot set Default Timezone' );
        }

        $this->strTimezone = $strTimezone;
        return true;
    }

    /**
     * Write to the logfile and print on the screen.
     * Запись в журнал и вывод на экран.
     *
     * @param string|array|object $logData
     * @param null|string         $strLogTitle
     * @param null|bool           $isPrintOnScreen
     *
     * @return bool
     */
    public function toLog( $logData, ?string $strLogTitle = 'DEBUG', ?bool $isPrintOnScreen = null ): bool
    {
        try {
            if ( !$this->isEnable ) {
                return false;
            }

            $timeStart = \microtime( true );
            $timeElapsed = isset( $this->timeLastSave ) ? \sprintf( ', +%.5f sec', $timeStart - $this->timeLastSave ) : ', new session!';
            //$strLogTitle = $strLogTitle ?? 'DEBUG';
            $isPrintOnScreen = $isPrintOnScreen ?? false;
            $strDataTmp = \is_string( $logData ) ? $logData : \var_export( $logData, true );
            $memoryUsage = $this->_memoryUsage();
            $this->timeLastSave = $timeStart;

            $strData2Log = sprintf(
                '[ %s ] [ %s ] [ %s ]',
                \date( 'Y-m-d H:i:s P' ) . $timeElapsed,
                'session: ' . $this->strUniqId,
                'memory: ' . $memoryUsage
            );
            $strData2Log .= PHP_EOL . 'TITLE: ' . $strLogTitle . PHP_EOL . $strDataTmp . PHP_EOL . PHP_EOL;

            if ( $isPrintOnScreen ) {
                echo ( PHP_SAPI === 'cli' ? $strData2Log : '<pre>' . \htmlspecialchars( $strData2Log ) . '</pre>' );
            }

            if ( !isset( $this->strLogFilePath ) ) {
                $this->strLogFilePath = $this->_getLogFullFileNameWithPath();
            }

            $resultAppend = $this->_append( $this->strLogFilePath, $strData2Log );

            //mail(_EMAIL4ERROR, _SITE_ERROR_ID . ': ' . $strLogTitle, 'Данные: ' . $this->strEol . $strData2Log);

            return $resultAppend;
        } catch ( \Exception $e ) {
            throw new SimpleLoggerException( $e->getMessage() );
        }
    }

    /**
     * Constructor
     * Конструктор
     *
     * @param string $strLogFileName Log filename
     */
    private function __construct( string $strLogFileName )
    {
        $this->setDefaultTimezone( $this->strTimezone );
        $this->strLogFileName = $strLogFileName;
        $this->strUniqId = $this->_uniqIdReal();
    }

    /**
     * Appends a log message to a file.
     * Добавляем сообщение в лог файл.
     *
     * @param string $filename The filename to append
     * @param string $message The message to append
     *
     * @return bool
     */
    private function _append( string $filename, string $message ): bool
    {
        if ( @\file_put_contents( $filename, $message, FILE_APPEND | LOCK_EX ) === false ) {
            if ( !\is_writable( $filename ) ) {
                throw new \Exception( 'Logfile is not writable: ' . $filename );
            }
            throw new \Exception( 'Can`t write to the log file: [' . $filename . ']' );

            //mail(_EMAIL4ERROR, _SITE_ERROR_ID . ': Ошибка при записи в лог', 'Данные: ' . $this->strEol . $message);
        }

        return true;
    }

    /**
     * Gets a prefixed real unique identifier based on the cryptographically secure function
     * Получаем действительно уникальный идентификатор (с префиксом), основанный на криптографически безопасных функциях
     *
     * @param int    $length length of the unique identifier
     * @param string $prefix prefix of the unique identifier
     *
     * @return string
     */
    private function _uniqIdReal( int $length = 5, string $prefix = '' ): string
    {
        try {
            if ( \function_exists( 'random_bytes' ) ) {
                $bytes = \random_bytes( (int) \ceil( $length / 2 ) );
                return $prefix . \substr( \bin2hex( $bytes ), 0, $length );
            } elseif ( \function_exists( 'openssl_random_pseudo_bytes' ) ) {
                $bytes = \openssl_random_pseudo_bytes( (int) \ceil( $length / 2 ) );
                return $prefix . \substr( \bin2hex( $bytes ), 0, $length );
            }
            throw new \Exception( 'Found no available cryptographically secure random function' );
        } catch ( \Exception $e ) {
            throw new SimpleLoggerException( $e->getMessage() );
        }
    }

    /**
     * Gets memory usage
     * Получаем данные об использовании памяти скриптом
     *
     * @return string
     */
    private function _memoryUsage(): string
    {
        try {
            // Currently memory actually used by the script
            $memUsageUsed = \memory_get_usage();
            // Currently memory actually allocated for the script
            $memUsageAllocated = \memory_get_usage( true );
            // Peak memory memory actually used by the script
            $memPeakUsed = \memory_get_peak_usage();
            // Peak memory memory actually allocated for the script
            $memPeakAllocated = \memory_get_peak_usage( true );

            // Memory used/allocated: %d/%d KB (Peak used/allocated: %d/%d KB)
            return \sprintf(
                '%d/%d KB (%d/%d KB)',
                \round( $memUsageUsed / 1024 ),
                \round( $memUsageAllocated / 1024 ),
                \round( $memPeakUsed / 1024 ),
                \round( $memPeakAllocated / 1024 )
            );
        } catch ( \Exception $e ) {
            throw new SimpleLoggerException( $e->getMessage() );
        }
    }

    /**
     * Returns full path and name of the logfilename (recursive make directories if needed).
     * Получаем полный путь с именем логфайла (рекурсивно создаем каталоги при необходимости).
     *
     * @return string
     */
    private function _getLogFullFileNameWithPath(): string
    {
        try {
            $this->strLogFileDir = $this->_getClearedFileName( $this->strLogFileDir );

            $strLogFilePath = __DIR__ . \str_repeat( DIRECTORY_SEPARATOR . '..', 4 ) . DIRECTORY_SEPARATOR . $this->strLogFileDir;

            if ( \is_dir( $strLogFilePath ) === false ) {
                if ( \mkdir( $strLogFilePath, 0755, true ) === false ) {
                    throw new \Exception( 'Can`t create the directory: [' . $strLogFilePath . ']' );
                    //mail(_EMAIL4ERROR, _SITE_ERROR_ID . ': Ошибка при создании каталога для логов', 'Данные: ' . $this->strEol . $strLogFilePath);
                }
            }

            if ( !\is_writable( $strLogFilePath ) ) {
                throw new \Exception( 'Directory is not writable: [' . $strLogFilePath . ']' );
            }

            $this->strLogFileName = $this->_getClearedFileName( $this->strLogFileName );

            return $strLogFilePath . DIRECTORY_SEPARATOR . \date( 'Y-m-d' ) . '_' . $this->strLogFileName;
        } catch ( \Exception $e ) {
            throw new SimpleLoggerException( $e->getMessage() );
        }
    }

    /**
     * Returns the file name cleared of characters that are not recommended for use in the file name.
     * Получаем имя файла, очищенное от символов, не рекомендуемых к использоанию в имени файла.
     *
     * @return string
     */
    private function _getClearedFileName( string $strRawFileName ): string
    {
        $strRawFileName = \filter_var( $strRawFileName, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW );
        $strRawFileName = \str_replace( ['<', '>', ':', '"', '/', '\\', '|', '?', '*'], '', $strRawFileName );

        return $strRawFileName;
    }
}
