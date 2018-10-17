<?php

require_once("../../config.php");

require_once('connect.php');

require_once('un_students_form.php');


global $CFG, $DB;



$course = $DB->get_record('course',array('id'=>$_GET['id']));
$PAGE->set_url('/blocks/vsucourse/un_students.php', array('id' => $_GET['id']));

require_login($course, true);

$courseid = $course->id;

$PAGE->set_pagelayout('standard');

$PAGE->set_context(context_course::instance($course->id));

$PAGE->navbar->add('Отписывание студентов от курса');



$PAGE->set_title('Отписывание студентов от курса');

//$PAGE->set_context(get_system_context());
$PAGE->set_heading(get_string('pluginname', 'block_vsucourse'));


$PAGE->requires->js('/blocks/vsucourse/js/jquery-2.1.0.min.js');
$PAGE->requires->js('/blocks/vsucourse/js/vsucourse_js.js');


echo $OUTPUT->header();

$form = new un_students_form();

if (isset($_GET['id']))
{
		$output = '';
		$o = '';
        ob_start();
		
		
        $form->display();
        $o = ob_get_contents();
        ob_end_clean();
		// end of buffer output. Now we send buffer data to our oupbur variable

		$output .= $o;
}else
{
	echo "GET no found";
}

echo $output;


echo $OUTPUT->footer();
echo '<script>
$( document ).ready(function() {
	$("fieldset").each(function (i) {
		this.className = "clearfix collapsible collapsed";
	});

});
</script>';
?>
<style>
#un_user {
border: 1px solid #999;
}
#un_user .r0 {
padding: 3px;
background-color: #F3F3F3;
}
#un_user .r1 {
padding: 3px;
background-color: #F9F9F9;
}
</style>
<script>
function show(id){

	$("#count_group a").each(function (i) {
		$(this).css("color", "blue");
	});
	
	$("#count_group #group_"+id +"").css("color", "black");
	
	$("div #group_list").each(function (i) { 
		if (id == 0){
			$("#"+$(this).attr("mainid")).show();
		}else{
			if ($(this).attr("listid")!= "") {
				if ($(this).attr("listid").indexOf(id) == -1){
					$("#"+$(this).attr("mainid")).hide();
				}else{
					$("#"+$(this).attr("mainid")).show();
				}
			}else{
				$("#"+$(this).attr("mainid")).hide();
			}
		}
	});
	$("#fast_"+UId).html("");
}
function CheckCompleted(type){
    $(":checkbox[prefix=handmade_completed]").each(function (i) {
        id = $(this).attr("id");
        if ($("#fast_"+id).css("display") == "none"){
        }else{
            if (type == 0)
            {
                this.checked = 'checked';
            }
            if (type == 1)
            {
                this.checked = '';
            }
        }
    });
}
function CheckGroupH(type){
	$(":checkbox[prefix=handmade_notcompleted]").each(function (i) {
		id = $(this).attr("id");
		if ($("#fast_"+id).css("display") == "none"){
		
		}else{
			if (type == 0)
			{
				this.checked = 'checked';
			}
			if (type == 1)
			{
				this.checked = '';
			}
		}
	});
    $(":checkbox[prefix=handmade_completed]").each(function (i) {
		id = $(this).attr("id");
		if ($("#fast_"+id).css("display") == "none"){
		
		}else{
			if (type == 0)
			{
				this.checked = 'checked';
			}
			if (type == 1)
			{
				this.checked = '';
			}
		}
	});
}
function CheckGroup(group, type){
	$(":checkbox[prefix=pr_" + group + "]").each(function (i) {
		if (type == 0)
		{
			this.checked = 'checked';
		}
		if (type == 1)
		{
			this.checked = '';
		}
	});
}

function del_user_new(){
	$(":checkbox:checked[prefix=handmade]").each(function (i) {
		UId = this.id;
		var request = $.ajax({
				type: "POST",
				url: "../../enrol/unenroluser.php",
				//
				data: { ue:UId , ifilter:0, confirm: 1, sesskey: $("#key").html()}
		});
		
		request.done(function( msg ) {
			
			//$("#buffer").html(msg);
		});
		$("#fast_"+UId).html("Выполнено!");
	});
}


function del_users()
{
	$(":checkbox:checked[prefix=handmade]").each(function (i) {
		UId = this.id;
		$.ajax({
				type: "GET",
				url: "un_students_jq.php",
				data: { type: "del_user", id:UId}
		}).done(function( msg ) {
			var re = /\s*,\s*/;
			var tagList = msg.split(re);

			if (tagList[0] == "ok"){
				$("#fast_"+tagList[1]).html("");
			}
		});
	});
}

function del_chort(id)
{
	var parts = window.location.search.substr(1).split("&");
	var $_GET = {};
	for (var i = 0; i < parts.length; i++) {
		var temp = parts[i].split("=");
		$_GET[decodeURIComponent(temp[0])] = decodeURIComponent(temp[1]);
	}
	str = "";
	$(":checkbox:not(:checked)[prefix=pr_"+ id +"]").each(function (i) {
		UId = this.id;
		str += UId + ",";
	});
	
	str += ".";
	str = str.replace(",.", "");
	str = str.replace(".", "");
	//alert(str);
	
	$.ajax({
		type: "GET",
		url: "un_students_jq.php",
		data: { type: "del_chort", id:id, list:str, cid: $_GET['id']}
	}).done(function( msg ) {
		if (msg == "ok"){
			$(":checkbox:checked[prefix=pr_"+ id +"]").each(function (i) {
				UId = this.id;
				$("#fast_"+UId).html("");
				$("#fast_"+UId).css("padding" , 0);
			});
		}
	});
}

</script>
