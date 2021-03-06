<?php
    
require_once "srvc_book_common.php";
require_once "srvc_book_file.php";

function srvc_book_max_per_slot()
{
    return 10;
}
    
function srvc_book_rticket_to_string($rticket)
{
    $str = " " . $rticket->guid->to_string();
    $str .= "-" . "$rticket->num" . "人";
    $str .= "-" .
            "$rticket->large_board"     . "大" . 
            "$rticket->medium_board"    . "中" . 
            "$rticket->small_board"     . "小";
    $str .= "-" . $rticket->visit_date;
    $str .= "-" . minutes_to_clock_str($rticket->visit_mins_slot);
    
    return $str;
}

// lock one day
function srvc_book_block($vdate)
{
    $err = impl_book_lock_date($vdate);
    return $err;
}

function srvc_book_unblock($vdate)
{
    $err = impl_book_unlock_date($vdate);
    return $err;
}

function srvc_book_is_blocked($vdate)
{
    $err = impl_book_date_is_locked($vdate);
    return $err;
}

// lock one slot
function srvc_book_block_timeslot($vdate, $timeslot)
{
    $err = impl_book_lock_timeslot($vdate, $timeslot);
    return $err;
}

function srvc_book_unblock_timeslot($vdate, $timeslot)
{
    $err = impl_book_unlock_timeslot($vdate, $timeslot);
    return $err;
}

define("HOUR_BUFFER_4_BOOK_WEEKDAY",    2);
define("HOUR_BUFFER_4_BOOK_WEEKEND",    0);
function srvc_book_is_timeslot_blocked($vdate, $timeslot)
{
    $cur_date = date("Ymd", time());
    if ($cur_date - $vdate > 0)
    {
        // before today
        return true;
    }
    else if ($cur_date == $vdate)
    {
        $dayOfWeek = date("N", time());
        $hour_buffer = ($dayOfWeek == 6 || $dayOfWeek == 7) ? HOUR_BUFFER_4_BOOK_WEEKEND : HOUR_BUFFER_4_BOOK_WEEKDAY;
        
        // only the hours after HOUR_BUFFER_4_BOOK is available
        $bar_time = time() + SEC_PER_HOUR * $hour_buffer;
        $bar_timeslot = date("H", $bar_time) * MIN_PER_HOUR + date("i", $bar_time);
        if ($bar_timeslot - $timeslot > 0)
        {
            return true;
        }
    }
    
    $err = impl_book_timeslot_is_locked($vdate, $timeslot);
    return $err;
}

// query
function srvc_book_query_block($next_n_days, &$result_arr)
{
    if ($next_n_days >= 0)
    {
        return impl_book_query_lock(1, $next_n_days, $result_arr);
    }
    
    return BOOK_CODE_ERR_INVALID;
}

function srvc_book_reserve($guid, 
                           $guest_num, 
                           $visit_date, 
                           $visit_slot_in_mins, 
                           $small_board, 
                           $medium_board, 
                           $large_board, 
                           &$trade_token)
{
    $rticket = new ReservationTicket($guid, 
                                     $guest_num, 
                                     $visit_date, 
                                     $visit_slot_in_mins, 
                                     $small_board, 
                                     $medium_board, 
                                     $large_board);
    $trade_token = $rticket->trade_token;
    
    if (!$rticket->is_valid())
    {
        return BOOK_CODE_ERR_INVALID;
    }
    
    $err = impl_book_do_reserve($rticket, srvc_book_max_per_slot());
    
    if (IS_BOOK_OK($err))
    {
        $subject = "[Too塗预约单]";
        $subject .= srvc_book_rticket_to_string($rticket);
        
        email_send_to_many(array(TOO_WX_MAIL_ADMIN_Y, TOO_WX_MAIL_ADMIN_W),
                           $subject,
                           json_encode($rticket->to_array()),
                           notify_email(TOO_HOST_URL));
    }
    
    return $err;
}

function srvc_book_query_schedule($next_n_days, &$result_arr)
{
    if ($next_n_days >= 0)
    {
        return impl_book_query_schedule(1, $next_n_days, $result_arr);
    }
    
    return BOOK_CODE_ERR_INVALID;
}
    
?>