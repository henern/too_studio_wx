<?php
    
$g_sec_per_day = 24 * 60 * 60;
$g_days_of_week = array("星期一", "星期二", "星期三", "星期四", "星期五", "星期六", "星期天");

function day_of_week($timestamp=time())
{
    $indx = date("N", $timestamp);
    
    if ($indx > 0 && $indx < 8)
    {
        return $g_days_of_week[$indx];
    }
    
    return "";
}
    
function full_date($ts=time())
{
    return date("Y-m-d ", $ts) . day_of_week($ts);
}

function minutes_to_clock_str($mins)
{
    $h = floor($mins / 60);
    $m = $mins % 60;
    
    return str_pad($h, 2, "0", STR_PAD_LEFT) . ":" . str_pad($m, 2, "0", STR_PAD_LEFT);
}

?>