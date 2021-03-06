<?php
    
require_once "wx_dev.php";
utils_init();

# trace the visitor
log_visitor_info();

require_once "srvc_book_abstract.php";

$guest_num_max = 20;
$guest_num_default = 2;
$BOARD_M_DEFAULT = $guest_num_default;
    
// user can make a reservation from tommorrow
$FIRST_OPEN_AFTER_TODAY = 0;
$TIME_OF_FIRST_OPEN_DAY = time() + $FIRST_OPEN_AFTER_TODAY * SEC_PER_DAY;
$right_now_day = full_date($TIME_OF_FIRST_OPEN_DAY);

// try to get wx-openid
$wx_oid = "";
$wx_code = array_string4key($_GET, "code");
$wx_state = array_string4key($_GET, "state");
if ($wx_code != null && $wx_state == TOO_WX_STATE_DEFAULT)
{
	$wx_access_token = "";
    wx_openid_from_code($wx_code, $wx_access_token, $wx_oid);
	
	if ($wx_oid != null)
	{
		setcookie(KEY_COOKIE_TOO_WX_OID, $wx_oid, time() + 3600);
	}
	
    $redirect_url = TOO_HOME_URL . "/wx/book.php#wechat_redirect";
    header("Location:" . $redirect_url);
	
	exit();
}

$wx_oid = array_string4key($_COOKIE, KEY_COOKIE_TOO_WX_OID);
if ($wx_oid == null)	$wx_oid = "";

?>

<?php
$available_days = [];
$available_days_display = [];
$cur_day = $TIME_OF_FIRST_OPEN_DAY;
for ($k = 0; $k < OPEN_HOUR_DAY; $k++)
{
    $ts = $cur_day + $k * SEC_PER_DAY;
    $str_date = full_date($ts);
    $val_date = date("Ymd", $ts);
    
    // skip the day if blocked
    if (srvc_book_is_blocked($val_date))    continue;
    
    $len_avaible_hours = 0;
    $avaible_hours = [];
    for ($cur_hour = OPEN_HOUR_BEGIN; 
         $cur_hour <= OPEN_HOUR_END; 
         $cur_hour += OPEN_HOUR_SLOT)
    {
        if (srvc_book_is_timeslot_blocked($val_date, $cur_hour))
        {
            continue;
        }
        
        $avaible_hours[] = $cur_hour;
        $len_avaible_hours++;
    }
    
    if ($len_avaible_hours > 0)
    {
        $available_days_display[$val_date] = $str_date;
        $available_days[$val_date] = $avaible_hours;
    }
}
?>
<?php
$timeslot_map = [];
for ($cur_hour = OPEN_HOUR_BEGIN; 
     $cur_hour <= OPEN_HOUR_END; 
     $cur_hour += OPEN_HOUR_SLOT)
{
    $timeslot_map[$cur_hour] = minutes_to_clock_str($cur_hour);
}
?>
<html>
	<head>
		<title>Too塗画室在线预定</title>
		
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
		<meta name="apple-mobile-web-app-status-bar-style" content="black" />
		<meta name="format-detection" content="telephone=no" />
		<meta name="format-detection" content="email=no" />
		<meta name="apple-mobile-web-app-capable" content="yes" />
        <script src="./js/book-toolkit.js"></script>
        <link rel="stylesheet" href="./css/base.css" type="text/css"/>
        <link rel="stylesheet" href="./css/book-default.css" type="text/css"/>

        <script>
            var _hip = [['_setPageId', 210001]];
            var executionTime = new Date().getTime();
            window.onload=function() {
                var readyTime = new Date().getTime();
                var bodyTag;
                if((readyTime - executionTime) < 3000){
                    if(document.documentElement.scrollHeight <= document.documentElement.clientHeight) {
                        bodyTag = document.getElementsByTagName('body')[0];
                        bodyTag.style.height = document.documentElement.clientWidth / screen.width * screen.height + 'px';
                    }
                    if(screen.width > 980 || screen.height > 980) return;
                    if(window.navigator.standalone === true) return;
                    if(window.innerWidth !== document.documentElement.clientWidth) {
                        if((window.innerWidth - 1) !== document.documentElement.clientWidth) return;
                    }
                    setTimeout(function() {
                        if(window.pageYOffset !== 0) return;
                        window.scrollTo(0, 1);
                        if(bodyTag !== undefined) bodyTag.style.height = window.innerHeight + 'px';
                        window.scrollTo(0, 0);
                    }, 300);
                }
            };
            
            var json_tsmap = JSON.parse('<?php echo json_encode($timeslot_map); ?>');
            var json_days = JSON.parse('<?php echo json_encode($available_days); ?>');
            function on_date_changed(date_select_id,time_select_id)
            {
                var date_select = document.getElementById(date_select_id);
                var date_indx_selected = date_select.selectedIndex;
                var date_val = date_select.options[date_indx_selected].value;
                var time_select = document.getElementById(time_select_id);
                time_select.options.length = 0;
                for (var timeslot in json_days[date_val])
                {
                    var timeslot_val = json_days[date_val][timeslot];
                    var opt = document.createElement('option');
                    opt.text = json_tsmap[timeslot_val];
                    opt.value = timeslot_val;
                    time_select.add(opt,null);
                }
                time_select.selectedIndex = 0;
                on_select_changed('J-time-select', 'J-input-time');
            }
            
            function on_select_changed(select_id, binding2_id)
            {
                var element_select = document.getElementById(select_id);
                var indx_selected = element_select.selectedIndex;
                var element_bind2 = document.getElementById(binding2_id);
                
                element_bind2.innerHTML = element_select.options[indx_selected].innerHTML;
            }
            
            function confirm_to_pay(callback, timeout)
            {
                setTimeout(function(){
                    if (confirm("预订成功啦！在线支付更享95折喔，到店支付也行，试试在线支付？"))
                    {
                        callback();
                    }
                }, timeout);
            }
            
            function on_click_to_reserve()
            {
                var g_phone = document.getElementById("J-input-phone").value;
                var g_num   = document.getElementById("J-person-select").value;
                var v_date  = document.getElementById("J-date-select").value;
                var v_slot  = document.getElementById("J-time-select").value;
                
                var v_board_large = document.getElementById("J-board-large").value;
                var v_board_small = document.getElementById("J-board-small").value;
                var v_board_medium = document.getElementById("J-board-medium").value;
                
                if (verify_mobile(g_phone) == false)
                {
                    alert("亲，请留下手机号方便联系喔，到店报手机号即可");
                    return;
                }
                
                var str_date  = document.getElementById("J-input-date").innerText;
                var str_slot  = document.getElementById("J-input-time").innerText;
                
                var str_reseration = str_date + " " + str_slot + "\n" +
                                     "画板: "+v_board_large+"大"+v_board_medium+"中"+v_board_small+"小（"+g_num+"人）\n"+
                                     "亲，确认预订吗？";
                if (!confirm(str_reseration))   return;
                
                var btn_reserve = document.getElementById("J_submit");
                btn_reserve.innerHTML = "正在努力预定...";
                
                book_do_reserve(g_phone, 
                                g_num, 
                                <?php echo "\"" . "$wx_oid" . "\"" ?>,
                                v_date, 
                                v_slot, 
                                v_board_small, 
                                v_board_medium, 
                                v_board_large, 
                                function(result_code, result_ttoken, result_desc)
                {
                    
                    if (result_code >= 0)
                    {
                        btn_reserve.innerHTML = "预定成功，恭候大驾";
						btn_reserve.href="javascript:void";
                        confirm_to_pay(function(){
                            window.location.assign("./srvc_pay_auth.php?count=" + g_num + 
                                                   "&visit_day=" + v_date + 
                                                   "&time_slot=" + v_slot + 
                                                   "&phone=" + g_phone + 
                                                   "&small_b=" + v_board_small + 
                                                   "&medium_b=" + v_board_medium + 
                                                   "&large_b=" + v_board_large + 
                                                   "&ttoken=" + result_ttoken);
                        }, 200);
                    }
                    else
                    {
                        btn_reserve.innerHTML = "预定失败（" + result_desc + "）";
                    }
                });
            }
        </script>
		
	</head>
	<body id="top">
        <!--icon for wechat-->
        <div style='display:none;'>
            <img src='./img/too-icon.jpeg'/>
        </div>
        <header>
            <div class="placeholder"></div>
            <div class="title">我要预定</div>
        </header>
        <section class="info">
            <div class="people-sel J-person-trigger">
                <label>人数</label>
                <span class="value" id="J-input-person"><?php echo $guest_num_default ?></span>
                <i class="caret"></i>
                <select class="select-overlay" id="J-person-select" onchange="javascript:on_select_changed('J-person-select', 'J-input-person')">
                    <?php
                        for ($j = 1; $j <= $guest_num_max; $j++)
                        {
                            if ($j == $guest_num_default)
                            {
                                echo "<option value='$j' selected>$j</option>";
                            }
                            else
                            {
                                echo "<option value='$j'>$j</option>";
                            }
                        }
                    ?>
                </select>
            </div>
            <div class="datetime-sel">
                <div class="date-sel J-date-trigger">
                    <span class="value" id="J-input-date"><?php echo $right_now_day; ?></span>
                    <i class="caret"></i>
                    <select class="select-overlay" id="J-date-select" onchange="javascript:on_select_changed('J-date-select', 'J-input-date');on_date_changed('J-date-select','J-time-select');">
                        <?php
                        foreach ($available_days_display as $date_val => $str)
                        {
                            echo "<option value='$date_val'>$str</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="time-sel J-time-trigger">
                    <span class="value" id="J-input-time"><?php echo minutes_to_clock_str(OPEN_HOUR_BEGIN); ?></span>
                    <i class="caret"></i>
                    <select class="select-overlay" id="J-time-select" onchange="javascript:on_select_changed('J-time-select', 'J-input-time')">
                    </select>
                </div>
            </div>
            <div class="msg msg-full hide">该时间段已订满，请换个时间</div>
        </section>
                        
        <section class="contact">
            <div class="row-group">
                <div class="row">
                    <div class="input">
                        <input id="J-input-phone" type="tel" placeholder="请输入手机号" />
                    </div>
                </div>
            </div>
            <div class="msg msg-contact-err hide"></div>
        </section>
        		
        <section class="contact">
        <div class="row-group">
        <div class="row">
		<table id="board-group" style="border:none" cellspacing="0" width="100%">
		<!--小画板-->
		<tr>
			<td class="board_img"><img class="board_img" src='./img/too-board-small.png'/></td>
			<td align="center">
                <span class="value board_sel" id="J-input-board-small">0</span><label>张</label>
                <i class="caret"></i>
                <select class="select-overlay" id="J-board-small" onchange="javascript:on_select_changed('J-board-small', 'J-input-board-small')">
                    <option value='0' selected>0</option>
                    <option value='1'>1</option>
                    <option value='2'>2</option>
                    <option value='3'>3</option>
                    <option value='4'>4</option>
                    <option value='5'>5</option>
                    <option value='6'>6</option>
                </select>
			</td>
		</tr>	
        </table>
        </div>
        
        <div class="row">
        <table id="board-group" style="border:none" cellspacing="0" width="100%">
		<!--中画板-->
		<tr>
			<td class="board_img"><img class="board_img" src='./img/too-board-medium.png'/></td>
			<td align="center">
                <span class="value board_sel" id="J-input-board-medium"><?php echo $BOARD_M_DEFAULT ?></span><label>张</label>
                <i class="caret"></i>
                <select class="select-overlay" id="J-board-medium" onchange="javascript:on_select_changed('J-board-medium', 'J-input-board-medium')">
                    <?php
                        
                    for ($cur = 0; $cur <= 6; $cur++)
                    {
                        if ($cur == $BOARD_M_DEFAULT)
                        {
                            echo "<option value='$cur' selected>$cur</option>";
                        }
                        else
                        {
                            echo "<option value='$cur'>$cur</option>";
                        }
                    }
                    
                    ?>
                    
                </select>
			</td>
		</tr>
        </table>
        </div>
        
        <div class="row">
        <table id="board-group" style="border:none" cellspacing="0" width="100%">
		<!--大画板-->
		<tr>
			<td class="board_img"><img class="board_img" src='./img/too-board-large.png'/></td>
			<td align="center">
                <span class="value board_sel" id="J-input-board-large">0</span><label>张</label>
                <i class="caret"></i>
                <select class="select-overlay" id="J-board-large" onchange="javascript:on_select_changed('J-board-large', 'J-input-board-large')">
                    <option value='0' selected>0</option>
                    <option value='1'>1</option>
                    <option value='2'>2</option>
                    <option value='3'>3</option>
                    <option value='4'>4</option>
                    <option value='5'>5</option>
                    <option value='6'>6</option>
                </select>
			</td>
		</tr>
		</table>
        </div>
        </div>
        </section>
    
        <a id="J_submit" class="btn-huge" href="javascript:on_click_to_reserve();">马上预订</a>
		<!--内容 end-->
<footer class="footer">
    <p class="copyright">Copyright ©2016 Too塗Studio</p>
	<a href="<?php echo TOO_WX_MAP_URL ?>"><?php echo TOO_WX_ADDRESS ?></a>
</footer>     
        <script type="text/javascript">
		//得到焦点触发事件
		function OnfocusFun(element,elementvalue)
		{
		    if(element.value==elementvalue)
		    {
		        element.value="";
		    }
		}
		//离开输入框触发事件
		function OnBlurFun(element,elementvalue)
		{
		    if(element.value==""||element.value.replace(/\s/g,"")=="")
		    {
		        element.value=elementvalue;
		    }
		}
        on_date_changed('J-date-select','J-time-select');
        on_select_changed('J-date-select', 'J-input-date');     // refresh the J-date-select
		</script>
		
	</body>
</html>