<?php

namespace App\Enum;

enum XpChartTimeUnit: string
{
    case HOUR = 'hour';
    case DAY = 'day';
    case WEEK = 'week';
    case MONTH = 'month';
    case YEAR = 'year';
    case ALL = 'all';
}
