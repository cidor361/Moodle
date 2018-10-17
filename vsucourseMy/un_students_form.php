<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/completionlib.php');

class un_students_form extends moodleform {
	public $id;
	function definition() {
		global $CFG, $DB, $conn;
	
		$mform =& $this->_form;
		if (isset($_GET['id']))
		{
			$this->id = $_GET['id'];
		}else
		{
			$this->id = 0;
		}
		$i = 1;
		$sql = "SELECT * FROM  `mdl_enrol` WHERE  `courseid` = '".$this->id."'";	
		//echo $sql."<br>";
		$rows = $DB->get_records_sql($sql);
		foreach ($rows as $row) {
			
			
			$sql2 = "SELECT * FROM  `mdl_user_enrolments` WHERE  `enrolid` = '".$row->id."'";
			
			$rows2 = $DB->get_records_sql($sql2);
			$type = 0;
			if (count($rows2) != 0){
				if ($row->enrol == "manual")
				{
					$mform->addElement('header','general', "Подписанные вручную<br>");
					
					$mform = $this->print_users($rows2, $mform);
				}else
				if ($row->enrol == "cohort")
				{
					$mform->addElement('header','general', "Глобальная группа ".$this->global_group($row->id)."<br>");
					
					$mform->addElement('html',  "<a style='cursor: pointer;' onclick='CheckGroup($row->id, 0)'>Отметить</a> <a style='cursor: pointer;' onclick='CheckGroup($row->id, 1)'>Снять</a> всех.<br>");
					
					$mform = $this->print_chort($rows2, $mform, $row->id);
				}else{
					continue;
				}
			}
				
			
			
		}
	}
	function global_group($id){
		global $DB;
		$sql = "SELECT * FROM  `mdl_enrol` WHERE `id` = '".$id."'";	
		$row = $DB->get_record_sql($sql);
		
		$sql2 = "SELECT * FROM  `mdl_cohort` WHERE `id` = '".$row->customint1."'";	
		$row2 = $DB->get_record_sql($sql2);
		return "(".$row2->name.")";
	}
	function user_inf($id){
		global $DB;
		$sql = "SELECT * FROM  `mdl_user` WHERE  `id` = '".$id."'";	
		$row = $DB->get_record_sql($sql);
		return $row;
	}
	function print_users($rows, $mformold){
		$mform =& $this->_form;
		$mform = $mformold;
		
		global $DB;
		
		$completion = new completion_info($course);

		
		echo "<div id='key' style='visibility: hidden;position: absolute;'>".$_SESSION["USER"]->sesskey."</div>";
		
		$sql2 = "SELECT * FROM  `mdl_groups` WHERE  `courseid` = '".$this->id."'";
		$rows2 = $DB->get_records_sql($sql2);
		
		$html = "";
		
		$rowsb = $rows;
		$userlist = "('0'";
		foreach ($rowsb as $row) {
			$userlist = $userlist.", '".$row->userid."'";
		}
		$userlist .= ")";
		
		foreach ($rows2 as $row2) {
			$sql3 = "SELECT * FROM  `mdl_groups_members` WHERE  `groupid` = '$row2->id' and `userid` in ".$userlist;
			//echo $sql3;
			$rows3 = $DB->get_records_sql($sql3);
			if (count($rows3) != 0)
			$html = $html."{myteg} <a onclick='show($row2->id)' style='cursor: pointer;' id='group_$row2->id'>".$row2->name." (".count($rows3).")</a>";
		}
		
		
		if ($html != ""){
			$html = str_replace("{myteg}", "," , $html);
			$html[0] = '';//удаляем первую запятую	
			$html[0] = '';//удаляем первую запятую
			$mform->addElement('html', "<div id='count_group'>".$html."<br><a onclick='show(0)' style='cursor: pointer;' id='group_0'>Отобразить всех пользователей</a></div>");
		}
		
		$mform->addElement('html',  "<a style='cursor: pointer;' onclick='CheckGroupH(0)'>Отметить</a> <a style='cursor: pointer;' onclick='CheckGroupH(1)'>Снять</a> всех.<br>");
		
        $mform->addElement('html',  "<a style='cursor: pointer;' onclick='CheckCompleted(0)'>Отметить завершивших курс</a>");                                                                     //Grebennikov
		
		$i = 1;
		$mform->addElement('html', "<div id='un_user'>");
		//$mform->addElement('html', $sql2."<br>");
		$course = $DB->get_record('course',array('id'=>$_GET['id']));                                         //Grebennikov
        $cinfo = new completion_info($course);                                                                //Grebennikov
		foreach ($rows as $row) {
			$user = $this->user_inf($row->userid);

            $iscomplete = $cinfo->is_course_complete($user->id);                                              //Grebennikov

			if ($i % 2 == 0){
				$style = 'userinforow r1';
			}else{
				$style = 'userinforow r0';
			}
				
			if ($user->lastname != "" and $user->deleted == 0 and $this->check_teacher($this->id,$user->id)){
				
				if ($user->lastaccess == 0){
					$unixtime_to_date = "Никогда";
				}else{
					$unixtime_to_date = date('j/n/Y H:i:s', $user->lastaccess);
				}
				$this_email = "";
				if ($user->email != ""){
					$this_email = "<br><i>".$user->email."</i>";
				}
				if ($iscomplete == true) {
                    $mform->addElement('html', "<div class='".$style."' id='fast_$row->id'><input type='checkbox' id='$row->id' prefix='handmade_completed'>".$user->lastname." ".$user->firstname." (<a href='https://edu.vsu.ru/user/profile.php?id=$user->id' target='_blank'>$user->username</a>)".$this_email." <br>Последний вход: $unixtime_to_date <div id='group_list' mainid='fast_$row->id' listid='".$this->user_groups_id($user->id)."'>".$this->user_groups($user->id)."<br>Курс завершён"."</div></div>");         //handmade
				}else{
                    $mform->addElement('html', "<div class='".$style."' id='fast_$row->id'><input type='checkbox' id='$row->id' prefix='handmade_notcompleted'>".$user->lastname." ".$user->firstname." (<a href='https://edu.vsu.ru/user/profile.php?id=$user->id' target='_blank'>$user->username</a>)".$this_email." <br>Последний вход: $unixtime_to_date <div id='group_list' mainid='fast_$row->id' listid='".$this->user_groups_id($user->id)."'>".$this->user_groups($user->id)."<br>Курс не завершён"."</div></div>");
                    }
				$i++;
			}
		}
		$mform->addElement('html', "<input type='button' value='Отписать' onclick='del_user_new();' /></div><div id='buffer'></div>");
		return $mform;
	}
	
	function check_teacher($cid, $uid){
		global $DB;
		$sql = "SELECT * FROM  `mdl_context` WHERE `contextlevel` = 50 and `instanceid` = ".$cid;
		$rows = $DB->get_records_sql($sql);
		foreach($rows as $row)
		{
			$sql_ch = "SELECT * FROM `mdl_role_assignments` WHERE `contextid` = '".$row->id."' and `roleid` = '3'";
			$rows_role = $DB->get_records_sql($sql_ch);
				
			foreach($rows_role as $row2){
				$sql = "SELECT * FROM mdl_user WHERE id = ".$row2->userid." and deleted = 0";
				$results = $DB->get_records_sql($sql);
				foreach($results as $result)
				{
					if (count($result) > 0)
					{
						if ($result->id == $uid)
						{
							return false;
						}
					}
				}
			}
		}
		return true;
	}
	function user_groups_id($Uid){
		global $DB;
		$html = "";
		$sql = "SELECT * FROM  `mdl_groups` WHERE  `courseid` = '".$this->id."'";
		$rows = $DB->get_records_sql($sql);
		foreach ($rows as $row) {
			$sql2 = "SELECT * FROM  `mdl_groups_members` WHERE  `groupid` = '$row->id' and `userid` = '$Uid'";
			$rows2 = $DB->get_records_sql($sql2);
			if (count($rows2)){
				$html = $html."{myteg}".$row->id;
				// соединяем в строку с разделителем
			}else{
				
			}
		}
		if ($html != ""){
			$html = str_replace("{myteg}", "," , $html);
			return $html;
		}
		else{
			return "";
		}
	}
	function user_groups($Uid){
		global $DB;
		$html = "";
		$sql = "SELECT * FROM  `mdl_groups` WHERE  `courseid` = '".$this->id."'";
		$rows = $DB->get_records_sql($sql);
		foreach ($rows as $row) {
			$sql2 = "SELECT * FROM  `mdl_groups_members` WHERE  `groupid` = '$row->id' and `userid` = '$Uid'";
			$rows2 = $DB->get_records_sql($sql2);
			if (count($rows2)){
				$html = $html."{myteg}".$row->name;
				// соединяем в строку с разделителем
			}else{
				
			}
		}
		if ($html != ""){
			$html = str_replace("{myteg}", "," , $html);
			$html[0] = '';//удаляем первую запятую
			return $html;
		}
		else{
			return "";
		}
	}
	function print_chort($rows, $mformold, $chortid){
		$mform =& $this->_form;
		$mform = $mformold;
		$i = 1;
		$mform->addElement('html', "<div id='un_user'>");
		//$mform->addElement('html', $sql2."<br>");
		$users = array();
		
		
		
		foreach ($rows as $row) {
				
			$user = $this->user_inf($row->userid);
			if ($i % 2 == 0){
				$style = 'userinforow r1';
			}else{
				$style = 'userinforow r0';
			}
				
			if ($user->lastname != "" and $user->deleted == 0){
			
				$unixtime_to_date = date('j/n/Y H:i:s', $user->lastaccess);
				$users[$i] = $user->lastname." ".$user->firstname."|||
				<div class='".$style."' id='fast_$user->id'>
				<input type='checkbox' id='$user->id' checked='checked' prefix='pr_$chortid' />
				{i}. ".$user->lastname." ".$user->firstname." 
				(<a href='https://edu.vsu.ru/user/profile.php?id=$row->userid' target='_blank'>$user->username</a>)";
				
				if ($user->email){
					$users[$i] .=	"<br><i>".$user->email."</i>";
				}
				$users[$i] .=	"<br>Последний вход: $unixtime_to_date</div>";
				//$mform->addElement('html', "<div class='".$style."' id='fast_$user->id'><input type='checkbox' id='$user->id' checked='checked' prefix='pr_$chortid' />".$i.". ".$user->lastname." ".$user->firstname." (<a href='https://edu.vsu.ru/user/profile.php?id=$row->userid' target='_blank'>$user->username</a>)<br><i>".$user->email."</i> </div>");
				
				$i++;
			}
		}
		sort($users);
		for($j = 0; $j <= $i; $j++){
		
			$str = strpos($users[$j], "|||"); 
			$users[$j] = substr($users[$j], $str + 3, strlen($users[$j]) - $str - 3); 
			$users[$j] = str_replace("{i}", $j+1 ,$users[$j]);
			
			$mform->addElement('html', $users[$j]);
		}
		
		$mform->addElement('html', "<input type='button' value='Отписать' onclick='del_chort(\"$chortid\");' /></div>");
		return $mform;
	}
	
}

function print_completed_users($rows2, $mform){

		
		$mform->addElement('html', "<input type='button' value='Отписать' onclick='del_user_new();' /></div>");
		return $mform;
	}

?>
