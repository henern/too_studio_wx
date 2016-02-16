<?php
    
require_once "srvc_book_common.php";
require_once "utils_time.php";

function __impl_book_file_base_dir()
{
    return dirname(__FILE__) . "/../reservation/";
}

function __impl_book_file_dir_4_name($subdir)
{
    return __impl_book_file_base_dir() . "/" . $subdir;
}

function __impl_book_file_rticket_path($rticket)
{
    $dir = __impl_book_file_dir_4_name($rticket->visit_date);
    
    if (!is_dir($dir))
    {
        mkdir($dir);
    }
    
    $path = $dir . "/" . $rticket->visit_mins_slot . ".json";
    return $path;
}

define("KEY_FILE_JSON_RTICKET_COUNT",           "COUNT");
define("KEY_FILE_JSON_RTICKET_LIST",            "ALL");
function impl_book_do_reserve($rticket, $max_per_slot=10)
{
    $path = __impl_book_file_rticket_path($rticket);
    
    // read json from file
    if (file_exists($path))
    {
        $orig_json_str = file_get_contents($path);
    }
    else
    {
        $orig_json_str = '{ "' . KEY_FILE_JSON_RTICKET_COUNT . '" : 0, "' . KEY_FILE_JSON_RTICKET_LIST . '" : {} }';
    }
    
    $json = json_decode($orig_json_str, true);
    
    // check
    if ($json == null ||
        !array_key_exists(KEY_FILE_JSON_RTICKET_COUNT, $json) ||
        !array_key_exists(KEY_FILE_JSON_RTICKET_LIST, $json))
    {
        return BOOK_CODE_ERR_CORRUPT;
    }
    
    // check if full
    $count_rtickets = $json[KEY_FILE_JSON_RTICKET_COUNT];
    if ($count_rtickets + $rticket->num > $max_per_slot)
    {
        return BOOK_CODE_ERR_FULL;
    }
    
    // check reservation list
    $arr_rtickets = $json[KEY_FILE_JSON_RTICKET_LIST];
    if (!is_array($arr_rtickets))
    {
        return BOOK_CODE_ERR_CORRUPT;
    }
    
    // any dup?
    foreach ($arr_rtickets as $arr_rt)
    {
        $rt = new ReservationTicket(null, 0, "", "");
        if (!$rt->from_array($arr_rt))
        {
            return BOOK_CODE_ERR_CORRUPT;
        }
        
        if ($rt->guid->is_equal_to($rticket->guid))
        {
            return BOOK_CODE_ERR_DUP;
        }
    }
    
    // update json count & list
    array_push($arr_rtickets, $rticket->to_array());
    $json[KEY_FILE_JSON_RTICKET_COUNT] += $rticket->num;
    $json[KEY_FILE_JSON_RTICKET_LIST] = $arr_rtickets;
    $new_json_str = json_encode($json);
    
    // flush
    $handle_f = fopen($path, "w") or die ("ERROR to open $path!");
    fwrite($handle_f, $new_json_str);
    fclose($handle_f);
    
    return BOOK_CODE_OK;
}
    
function impl_book_query_schedule($prev_n, $next_n, &$result_arr)
{
    $begin_day = time() - $prev_n * SEC_PER_DAY;
    
    for ($k = 0; $k < $prev_n + $next_n; $k++)
    {
        $cur = $begin_day + $k * SEC_PER_DAY;
        $subdir = date("Ymd", $cur);
        $dir = __impl_book_file_dir_4_name($subdir);
        
        if (!is_dir($dir))
        {
            continue;
        }
        
        $dh = opendir($dir);
        if ($dh == false)
        {
            continue;
        }
        
        // all slots in one array
        $all_slots = array();
        
        while (($fname = readdir($dh)) != false)
        {
            $fpath = $dir . "/" . $fname;
            
            // skip folders
            if (is_dir($fpath))
            {
                continue;
            }
            
            // file-name without ext
            $minutes_slot = pathinfo($fpath, PATHINFO_FILENAME);
            $visit_clock = minutes_to_clock_str($minutes_slot);
            
            // read from file
            $json_str = file_get_contents($fpath);
            
            // decode and check
            $json = json_decode($json_str, true);
            if ($json == null || 
                !is_array($json) ||
                !array_key_exists(KEY_FILE_JSON_RTICKET_COUNT, $json) ||
                !array_key_exists(KEY_FILE_JSON_RTICKET_LIST, $json))
            {
                continue;
            }
            
            // total guest number
            $count_rtickets = $json[KEY_FILE_JSON_RTICKET_COUNT];
            if ($count_rtickets <= 0)
            {
                continue;
            }
            
            // put all reservations of this slot in one array
            $slot_inf = array();
            // e.g. COUNT ==> N
            $slot_inf["COUNT"]  = $count_rtickets;
            
            // go through each reservation
            $arr_rtickets = $json[KEY_FILE_JSON_RTICKET_LIST];
            foreach ($arr_rtickets as $arr_rt)
            {
                $rticket = new ReservationTicket(null, 0, "", "");
                if (!$rticket->from_array($arr_rt))
                {
                    continue;
                }
                
                $guid = $rticket->guid;
                $guid_str = $guid->to_string();
                $guest_num = $rticket->num;
                
                // e.g. [N] ==> #2, PHONE_138xxxxxxxx
                $slot_inf[] = "#$guest_num, $guid_str";
            }
            
            // e.g. 15:30 ==> [ xxx ]
            $all_slots[$visit_clock] = $slot_inf;
        }
        
        closedir($dh);
        
        // e.g. 20160210 ==> [ xxx ]
        $result_arr[$subdir] = $all_slots; 
    }
    
    return BOOK_CODE_OK;
}

?>