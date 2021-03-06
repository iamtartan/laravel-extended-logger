<?php

namespace Tartan\Log;

use Illuminate\Support\Facades\Auth;
use Exception;

class Logger
{
    /**
     * @var array
     */
    private static $LOG_LEVELS = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];

    /**
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (!in_array($name, self::$LOG_LEVELS)) {
            $name = 'debug';
        }

        return self::__callStatic($name, $arguments);
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        if (!in_array($name, self::$LOG_LEVELS)) {
            $name = 'debug';
        }

        if (!is_array($arguments)) {
            $arguments = [$arguments];
        }

        // fix the wrong usage of xlog when second parameter is not an array
        if (!isset($arguments[1])) {
            $arguments[1] = [];
        }

        if(!is_array($arguments[1])){
            $arguments[1] = [$arguments[1]];
        }

        if (session_status() == PHP_SESSION_NONE) {
            $arguments[1]['sid'] = session_id();
        } else {
            $arguments[1]['sid'] = '';
        }

        $arguments[1]['uip'] = @clientIp();

        // add user id to all logs
        if (env('XLOG_ADD_USERID', true)) {
            if (!Auth::guest()) {
                $arguments[1]['uid'] = 'us' . Auth::user()->id . 'er'; // user id as a tag
            }
        }
        $trackIdKey = env('XLOG_TRACK_ID_KEY', 'xTrackId');

        // get request track ID from service container

        if (!isset($arguments[1][$trackIdKey])) {
            $arguments[1][$trackIdKey] = self::getTrackId($trackIdKey);
        }

        return call_user_func_array(['Illuminate\Support\Facades\Log', $name], $arguments);
    }

    /**
     * @param Exception $e
     * @param string $level
     *
     * @return mixed
     */
    public static function exception(Exception $e, $name = 'error')
    {
        $arguments     = [];
        $arguments [0] = 'exception-> ' . $e->getMessage();
        $arguments [1] = [
            'code'                => $e->getCode(),
            'file'                => basename($e->getFile()),
            'line'                => $e->getLine(),
            self::getTrackIdKey() => self::getTrackId(),
        ];

        return self::__callStatic($name, $arguments);
    }

    /**
     * @return string
     */
    public static function getTrackIdKey()
    {
        return env('XLOG_TRACK_ID_KEY', 'xTrackId');
    }

    /**
     * @param $trackIdKey
     *
     * @return string
     */
    protected static function getTrackId()
    {
        $trackIdKey = self::getTrackIdKey();

        try {
            $trackId = resolve($trackIdKey);
        } catch (Exception $e) {
            $trackId = '-';
        }

        return $trackId;
    }

}
