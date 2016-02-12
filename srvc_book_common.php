<?php
    
define("BOOK_CODE_OK",              0);
define("BOOK_CODE_ERR_UNKNOWN",     -10);
define("BOOK_CODE_ERR_FULL",        -11);
define("BOOK_CODE_ERR_INVALID",     -12);
define("BOOK_CODE_ERR_CORRUPT",     -13);

function IS_BOOK_OK($code)
{
    return $code >= BOOK_CODE_OK;
}
    
define("KEY_GUID_TYPE",     "GUID_TYPE");
define("KEY_GUID_VALUE",    "GUID_VAL");

define("TYPE_GUID_PHONE",   1000);
define("TYPE_GUID_WX_ID",   1001);

class GuestUID
{
    var type;       // PHONE, WXID, etc.
    var val;
    
    function to_array()
    {
        return array(KEY_GUID_TYPE  => $this->type, 
                     KEY_GUID_VALUE => $this->val);
    }
    
    function GuestUID($arr)
    {
        if (array_key_exists(KEY_GUID_TYPE, $arr))
        {
            $this->type = $this->valid_type($arr[KEY_GUID_TYPE]);
        }
        
        if (array_key_exists(KEY_GUID_VALUE, $arr))
        {
            $this->val = $arr[KEY_GUID_VALUE];
        }
    }
    
    function GuestUID($val, $type=TYPE_GUID_PHONE)
    {
        $this->type = $this->valid_type($type);
        $this->val = $val;
    }
    
    function valid_type($type)
    {
        if ($type != TYPE_GUID_PHONE && 
            $type != TYPE_GUID_WX_ID)
        {
            return TYPE_GUID_PHONE;
        }
        
        return $type;
    }
    
    function is_valid()
    {
        return (TYPE_GUID_PHONE <= $this->type && $this->type <= TYPE_GUID_WX_ID &&
                is_string($this->val));
    }
}
    
define("KEY_RTICKET_GUID",          "RTICKET_GUID");
define("KEY_RTICKET_NUM",           "RTICKET_NUM");
define("KEY_RTICKET_V_DATE",        "RTICKET_V_DATE");
define("KEY_RTICKET_V_MINS_SLOT",   "RTICKET_V_MINS_SLOT");
class ReservationTicket
{
    var $guid;
    var $num;
    var $visit_date;
    var $visit_mins_slot;
    
    function to_array()
    {
        return array(KEY_RTICKET_GUID           => $this->guid->to_array(),
                     KEY_RTICKET_NUM            => $this->num,
                     KEY_RTICKET_V_DATE         => $this->visit_date,
                     KEY_RTICKET_V_MINS_SLOT    => $this->visit_mins_slot);
    }
    
    function ReservationTicket($guid, $num, $v_date, $v_mins_slot)
    {
        $this->guid = $guid;
        $this->num = $num + 0;
        $this->visit_date = $v_date;
        $this->visit_mins_slot = $v_mins_slot;
    }
    
    function is_valid()
    {
        return is_subclass_of($this->guid, "GuestUID") &&
               is_int($this->num) &&
               is_numeric($this->visit_date) &&
               is_numeric($this->visit_mins_slot);
    }
}

?>