<?php

/**
 * Simple logger class that creates logs when an exception is thrown and
 * sends debugging information to the screen and via email (optional)
 * Простой логгер, который записывает информацию в лог файл, когда срабатывает
 * исключение, показывает эту информацию на экране и отправляет на почту (опционально)
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

// phpcs:disable Generic.Files.LineLength.TooLong, PSR2.Classes.PropertyDeclaration.Underscore

declare(strict_types=1);

namespace Leonid74\SimpleLogger;

use Leonid74\SimpleLogger\Exception\SimpleLoggerException;

class SimpleLogger
{
    /**
     * Is logger enabled
     * Включен ли логгер
     *
     * @var bool
     */
    public $isEnable = false;

    /**
     * Directory for the log file
     * Каталог для лог файла
     *
     * @var string
     */
    public $strLogFileDir = '../../../../logs/';

    /**
     * Log filename
     * Имя логфайла
     *
     * @var string
     */
    protected $strLogFileName;

    /**
     * Full path to the logfile
     * Полный путь с именем для логфайла
     *
     * @var string
     */
    protected $strLogFilePath;

    /**
     * Time of the last save operation
     * Время последнего сохранения информации
     *
     * @var float
     */
    protected $timeLastSave;

    /**
     * Unique identifier for the log file
     * Уникальный идентификатор для логфайла
     *
     * @var string
     */
    protected $strUniqId;

    /**
     * String for the default timezone like 'Europe/Moscow' or 'UTC' etc.
     * Строка для установки таймзоны по-умолчанию
     *
     * @var string
     */
    protected $strTimezone = 'Europe/Moscow';

    /**
     * Tag of carriage returns based on the type of run (PHP_EOL или '<br>')
     * Тег перевода строки, исходя из типа запуска (PHP_EOL или '<br>')
     *
     * @var string
     */
    protected $strEol;

    /**
     * Open tag of pre-formatted text based on the type of run (cli = '' or web = '<pre>')
     * Открывающий тег предварительно форматированного текста, исходя из типа запуска (cli = '' или web = '<pre>')
     *
     * @var string
     */
    protected $strPreOpen;

    /**
     * Close tag of pre-formatted text based on the type of run (cli = '' or web = '</pre>')
     * Закрывающий тег предварительно форматированного текста, исходя из типа запуска (cli = '' или web = '</pre>')
     *
     * @var string
     */
    protected $strPreClose;

    /**
     * Instances array for the each Log filename
     * Массив экземпляров для уже существующих лог файлов
     *
     * @var array
     */
    private static $__arrInstances = [];

    /**
     * Get instance object of \SimpleLogger
     * Получаем объект экземпляра \SimpleLogger
     *
     * @param string $strLogFileName Log filename
     *
     * @return \SimpleLogger\RequestLogger|false
     */
    public static function getInstance(string $strLogFileName = 'debug.log'): self
    {
        // Сделать оценку активности при вызове, с учетом порядка вызова функций
        //if (!self::$isEnable) return false;

        if (!isset(self::$__arrInstances[$strLogFileName])) {
            self::$__arrInstances[$strLogFileName] = new self($strLogFileName);
        }
        return self::$__arrInstances[$strLogFileName];
    }

    /**
     * Sets the default timezone
     * Устанавливаем временную зону по-умолчанию.
     *
     * @param string $strTimezone String like 'Europe/Moscow' or 'UTC' etc.
     *
     * @return bool
     */
    public function setDefaultTimezone(string $strTimezone)
    {
        if (!$this->isEnable) {
            return false;
        }

        if (strtolower(trim($strTimezone)) === strtolower($this->strTimezone)) {
            return true;
        }

        if (!date_default_timezone_set($strTimezone)) {
            throw new SimpleLoggerException('Cannot set Default Timezone');
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
    public function toLog($logData, $strLogTitle = null, $isPrintOnScreen = null)
    {
        try {
            if (!$this->isEnable) {
                return false;
            }

            $timeStart          = microtime(true);
            $timeElapsed        = isset($this->timeLastSave) ? sprintf(', +%.5f sec', $timeStart - $this->timeLastSave) : ', new session!';
            $strLogDate         = date('Y-m-d');
            $strLogDateTime     = date('Y-m-d H:i:s P');
            $strLogTitle        = $strLogTitle ?? 'DEBUG';
            $isPrintOnScreen    = $isPrintOnScreen ?? false;
            $strDataTmp         = is_string($logData) ? $logData : var_export($logData, true);
            $memoryUsage        = $this->memoryUsage();
            $this->timeLastSave = $timeStart;

            $strData2Log = sprintf('[ %s ] [ %s ] [ %s ]', $strLogDateTime . $timeElapsed, 'session: ' . $this->strUniqId, 'memory: ' . $memoryUsage) . $this->strEol . $this->strPreOpen . 'TITLE: ' . $strLogTitle . $this->strEol . $strDataTmp . $this->strPreClose . $this->strEol . $this->strEol;

            if ($isPrintOnScreen) {
                echo $strData2Log;
            }

            if (!isset($this->strLogFilePath)) {
                $this->strLogFilePath = $this->__getLogFullFileNameWithPath();
                $this->strLogFilePath = dirname($this->strLogFilePath) . DIRECTORY_SEPARATOR . $strLogDate . '_' . basename($this->strLogFilePath);
            }

            if (@file_put_contents($this->strLogFilePath, $strData2Log, FILE_APPEND | LOCK_EX) === false) {
                if (!is_writable($this->strLogFilePath)) {
                    throw new SimpleLoggerException('Logfile is not writable: ' . $this->strLogFilePath);
                }
                throw new SimpleLoggerException('Can`t write to the log file: [' . $this->strLogFilePath . ']');
                //mail(_EMAIL4ERROR, _SITE_ERROR_ID . ': Ошибка при записи в лог', 'Данные: ' . $this->strEol . $strData2Log);
            }

            //mail(_EMAIL4ERROR, _SITE_ERROR_ID . ': ' . $strLogTitle, 'Данные: ' . $this->strEol . $strData2Log);

            return true;
        } catch (SimpleLoggerException $e) {
            printf('%s' . $this->strEol, $e->getMessage());
            return false;
        }
    }

    /**
     * Gets a prefixed real unique identifier based on the cryptographically secure function
     * Получаем действительно уникальный идентификатор (с префиксом), основанный на криптографически безопасных функциях
     *
     * @param int    $length length of the unique identifier
     * @param string $prefix prefix of the unique identifier
     *
     * @return string|false
     */
    protected function uniqIdReal(int $length = 5, string $prefix = ''): string
    {
        try {
            if (function_exists('random_bytes')) {
                $bytes = random_bytes((int) ceil($length / 2));
                return $prefix . substr(bin2hex($bytes), 0, $length);
            } elseif (function_exists('openssl_random_pseudo_bytes')) {
                $bytes = openssl_random_pseudo_bytes((int) ceil($length / 2));
                return $prefix . substr(bin2hex($bytes), 0, $length);
            }
            throw new SimpleLoggerException('Found no available cryptographically secure random function');
        } catch (SimpleLoggerException $e) {
            printf('%s' . $this->strEol, $e->getMessage());
            return false;
        }
    }

    /**
     * Gets memory usage
     * Получаем данные об использовании памяти скриптом
     *
     * @return string|false
     */
    protected function memoryUsage(): string
    {
        try {
            // Currently memory actually used by the script
            $memUsageUsed = memory_get_usage();
            // Currently memory actually allocated for the script
            $memUsageAllocated = memory_get_usage(true);
            // Peak memory memory actually used by the script
            $memPeakUsed = memory_get_peak_usage();
            // Peak memory memory actually allocated for the script
            $memPeakAllocated = memory_get_peak_usage(true);

            // Memory used/allocated: %d/%d KB (Peak used/allocated: %d/%d KB)
            return sprintf(
                '%d/%d KB (%d/%d KB)',
                round($memUsageUsed / 1024),
                round($memUsageAllocated / 1024),
                round($memPeakUsed / 1024),
                round($memPeakAllocated / 1024)
            );
        } catch (SimpleLoggerException $e) {
            printf('%s' . $this->strEol, $e->getMessage());
            return false;
        }
    }

    /**
     * Returns full path and name of the logfilename (recursive make directories if needed).
     * Получаем полный путь с именем логфайла (рекурсивно создаем каталоги при необходимости).
     *
     * @return string|false
     */
    private function __getLogFullFileNameWithPath()
    {
        try {
            $this->strLogFileName = str_replace([':', '*', '?', '"', '<', '>', '|'], '', $this->strLogFileName);
            $this->strLogFileDir  = str_replace([':', '*', '?', '"', '<', '>', '|'], '', rtrim($this->strLogFileDir, '/\\'));
            $strLogFilePath       = __DIR__ . DIRECTORY_SEPARATOR . '../../../../' . $this->strLogFileDir;

            if (is_dir($strLogFilePath) === false) {
                if (mkdir($strLogFilePath, 0755, true) === false) {
                    throw new SimpleLoggerException('Can`t create the directory: [' . $strLogFilePath . ']');
                    //phpcs:ignore
                    //mail(_EMAIL4ERROR, _SITE_ERROR_ID . ': Ошибка при создании каталога для логов', 'Данные: ' . $this->strEol . $strLogFilePath);
                }
            }

            if (!is_writable($strLogFilePath)) {
                throw new SimpleLoggerException('Directory is not writable: [' . $strLogFilePath . ']');
            }

            return $strLogFilePath . DIRECTORY_SEPARATOR . $this->strLogFileName;
        } catch (SimpleLoggerException $e) {
            printf('%s' . $this->strEol, $e->getMessage());
            return false;
        }
    }

    /**
     * Constructor
     * Конструктор
     *
     * @param string $strLogFileName Log filename
     */
    private function __construct(string $strLogFileName)
    {
        $this->setDefaultTimezone($this->strTimezone);
        $this->strEol         = (php_sapi_name() == 'cli' ? PHP_EOL : '<br>');
        $this->strPreOpen     = ($this->strEol == '<br>' ? '<pre>' : '');
        $this->strPreClose    = ($this->strEol == '<br>' ? '</pre>' : '');
        $this->strLogFileName = $strLogFileName;
        $this->strUniqId      = $this->uniqIdReal();
    }
}
