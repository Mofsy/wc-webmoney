<?php
/*
  +----------------------------------------------------------+
  | Woocommerce - Webmoney Payment Gateway                   |
  +----------------------------------------------------------+
  | Author: Oleg Budrin (Mofsy) <support@mofsy.ru>           |
  | Author website: https://mofsy.ru                         |
  +----------------------------------------------------------+
*/

class WC_Webmoney_Logger
{
    /**
     * @var array
     */
    public $buffer;

    /**
     * Path
     */
    public $path;

    /**
     * Datetime
     */
    public $dt;

    /**
     * Logging levels (RFC 5424)
     *
     * @var array
     */
    public $levels = array
    (
        100 => 'DEBUG',
        200 => 'INFO',
        250 => 'NOTICE',
        300 => 'WARNING',
        400 => 'ERROR',
        500 => 'CRITICAL',
        550 => 'ALERT',
        600 => 'EMERGENCY',
    );

    /**
     * Logger constructor.
     *
     * @param $path
     */
    public function __construct($path)
    {
        $this->path = $path;
        $this->dt = new DateTime('now', new DateTimeZone( 'UTC' ));
    }

    /**
     * @param $message
     */
    public function addWarn($message)
    {
        $this->add(300, $message);
    }

    /**
     * @param $message
     * @param null $object
     */
    public function addError($message, $object = null)
    {
        $this->add(400, $message, $object);
    }

    /**
     * @param $message
     * @param null $object
     */
    public function addDebug($message, $object = null)
    {
        $this->add(100, $message, $object);
    }

    /**
     * @param $message
     */
    public function addInfo($message)
    {
        $this->add(200, $message);
    }

    /**
     * @param $message
     */
    public function addNotice($message)
    {
        $this->add(250, $message);
    }

    /**
     * @param $message
     * @param null $object
     */
    public function addCritical($message, $object = null)
    {
        $this->add(500, $message, $object);
    }

    /**
     * @param $message
     * @param null $object
     */
    public function addAlert($message, $object = null)
    {
        $this->add(550, $message, $object);
    }

    /**
     * @param $message
     * @param null $object
     */
    public function addEmergency($message, $object = null)
    {
        $this->add(600, $message, $object);
    }

    /**
     * @param $level
     * @param $message
     * @param null $object
     */
    public function add($level, $message, $object = null)
    {
        $content = implode
        (' -|- ',
            [
                $level,
                $this->levels[$level],
                $this->dt->format(DATE_ATOM),
                $message,
                print_r($object, true) . PHP_EOL
            ]
        );

        file_put_contents
        (
            $this->path,
            $content,
            FILE_APPEND | LOCK_EX
        );
    }
}