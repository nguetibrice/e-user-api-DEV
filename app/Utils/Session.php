<?php

namespace App\Utils;

use DateTime;

abstract class Session
{
    protected static DateTime $startTime;
    protected static string $requestId;

    /**
     * @return DateTime
     */
    public static function getStartTime(): DateTime
    {
        return self::$startTime;
    }

    /**
     * @param DateTime $startTime
     */
    public static function setStartTime(DateTime $startTime): void
    {
        self::$startTime = $startTime;
    }

    /**
     * @return string
     */
    public static function getRequestId(): string
    {
        return self::$requestId;
    }

    /**
     * @param string $requestId
     */
    public static function setRequestId(string $requestId)
    {
        self::$requestId = $requestId;
    }
}