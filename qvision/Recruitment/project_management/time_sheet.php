<?php
require '../../../connect.php';
require '../../../user.php';
$userrole=$_SESSION['userrole'];
$candidateid=$_SESSION['candidateid'];

$one=''; $two=''; $three=''; $four=''; $five=''; $six='';
$seven=''; $eight=''; $nine=''; $ten =''; $over_time='';

$a_one=''; $a_two=''; $a_three=''; $a_four=''; $a_five=''; $a_six='';
$a_seven=''; $a_eight=''; $a_nine=''; $a_ten =''; $a_over_time='';

$p_two=''; $p_three=''; $p_four=''; $p_five=''; $p_six='';
$p_seven=''; $p_eight=''; $p_nine=''; $p_ten=''; $p_over_time='';

$user_id=$_SESSION['userid'];
date_default_timezone_set('Asia/Kolkata');
$date = isset($_REQUEST['date']) ? $_REQUEST['date'] : date('Y-m-d');
$stmt = $con->query("select * from time_sheet where staff_id='$candidateid' and date='$date'");

$row = $stmt->fetch();
$time_sheet_project_id = '';
if($row){
    $one=$row['one']; $two=$row['two']; $three=$row['three'];
    $four=$row['four']; $five=$row['five']; $six=$row['six'];
    $seven=$row['seven']; $eight=$row['eight']; $nine=$row['nine'];
    $ten=$row['ten']; $over_time=$row['over_time'];

    $a_one=$row['a_one']; $a_two=$row['a_two']; $a_three=$row['a_three'];
    $a_four=$row['a_four']; $a_five=$row['a_five']; $a_six=$row['a_six'];
    $a_seven=$row['a_seven']; $a_eight=$row['a_eight']; $a_nine=$row['a_nine'];
    $a_ten=$row['a_ten']; $a_over_time=$row['a_over_time'];
    
    $p_two=$row['p_two'] ?? ''; $p_three=$row['p_three'] ?? ''; $p_four=$row['p_four'] ?? '';
    $p_five=$row['p_five'] ?? ''; $p_six=$row['p_six'] ?? ''; $p_seven=$row['p_seven'] ?? '';
    $p_eight=$row['p_eight'] ?? ''; $p_nine=$row['p_nine'] ?? ''; $p_ten=$row['p_ten'] ?? '';
    $p_over_time=$row['p_over_time'] ?? '';
    
    $time_sheet_project_id = isset($row['project_id']) ? $row['project_id'] : '';
}

$assigned_projects = $con->query("SELECT pd.project_id, pd.project_name, pd.project_code FROM y_project_employees pe JOIN y_project_details pd ON pe.project_id = pd.project_id WHERE pe.employee_id = '$candidateid'");
$project_list = $assigned_projects->fetchAll(PDO::FETCH_ASSOC);
$has_projects = count($project_list) > 0;
?>  

<form role="form" id="time_sheet_form" action="qvision/Recruitment/project_management/time_sheet_submit.php" method="post" enctype="multipart/type">
<div class="card card-primary shadow">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0"><font size="5">Hourly Time Sheet For <input type="date" class="form-control" style="display:inline-block; width:auto; font-size: 1rem; margin-left:10px;" value="<?php echo $date; ?>" min="<?php echo date('Y-m-d'); ?>" onchange="reload_timesheet(this.value)"></font></h3>
        <input type="hidden" name="sheet_date" value="<?php echo $date; ?>" />
        
        <div class="d-flex align-items-center ml-auto">
            <strong class="mr-2" style="font-size: 1.1rem; color: #fff;">In Time:</strong>
            <input type="text" id="one1" name="one1" class="form-control" style="width: 140px; text-align: center; font-weight: bold;" placeholder="e.g. 09:30 AM" value="<?php echo $one?>">
        </div>
    </div>
    <div class="card-body">
    <table class="table table-bordered">
        <tr>
            <td colspan="3" class="text-center py-3">
                <img src="qvision/images/logo123.jpg" alt="logo" style="width:80px; height:auto; margin-bottom:10px;"><br>
                <h5 class="mb-0 font-weight-bold">Aeronero Solutions Private Limited</h5>
            </td>
        </tr>
        
        <?php 
        function get_project_dropdown($name, $selected_val, $project_list, $has_projects) {
            if (!$has_projects) {
                return ''; // Return nothing if no projects assigned
            }
            $html = '<br><select name="'.$name.'" class="form-control form-control-sm mt-1" style="font-size: 11px; width:100%;" required>';
            $html .= '<option value="">Select Project</option>';
            foreach($project_list as $p) {
                $sel = ($selected_val == $p['project_id']) ? 'selected' : '';
                $pcode = !empty($p['project_code']) ? " (" . $p['project_code'] . ")" : "";
                $html .= '<option value="'.$p['project_id'].'" '.$sel.'>'.htmlspecialchars($p['project_name']).$pcode.'</option>';
            }
            $sel_other = ($selected_val == 'Others') ? 'selected' : '';
            $html .= '<option value="Others" '.$sel_other.'>Others</option>';
            $html .= '</select>';
            return $html;
        }
        ?>
        <tr style="background-color: #f4f6f9;">
            <th width="<?php echo $has_projects ? '20%' : '15%'; ?>" class="text-center align-middle"><?php echo $has_projects ? 'Time & Project' : 'Time'; ?></th>
            <th width="<?php echo $has_projects ? '40%' : '42.5%'; ?>" class="text-center align-middle">To-do List</th>
            <th width="<?php echo $has_projects ? '40%' : '42.5%'; ?>" class="text-center align-middle">Task Completed</th>
        </tr>
        <tr>
            <td class="align-middle text-center"><strong>9.30-10.30</strong><?php echo get_project_dropdown('p_two', $p_two, $project_list, $has_projects); ?></td>
            <td><textarea id="two2" name="two2" class="form-control" style="height:50px"><?php echo $two; ?></textarea></td>
            <td><textarea id="a_two2" name="a_two2" class="form-control" style="height:50px"><?php echo $a_two; ?></textarea></td>
        </tr>
        <tr>
            <td class="align-middle text-center"><strong>10.30-11.30</strong><?php echo get_project_dropdown('p_three', $p_three, $project_list, $has_projects); ?></td>
            <td><textarea id="three3" name="three3" class="form-control" style="height:50px"><?php echo $three; ?></textarea></td>
            <td><textarea id="a_three3" name="a_three3" class="form-control" style="height:50px"><?php echo $a_three; ?></textarea></td>
        </tr>
        <tr>
            <td class="align-middle text-center"><strong>11.30-12.30</strong><?php echo get_project_dropdown('p_four', $p_four, $project_list, $has_projects); ?></td>
            <td><textarea id="four4" name="four4" class="form-control" style="height:50px"><?php echo $four; ?></textarea></td>
            <td><textarea id="a_four4" name="a_four4" class="form-control" style="height:50px"><?php echo $a_four; ?></textarea></td>
        </tr>
        <tr>
            <td class="align-middle text-center"><strong>12.30-01.30</strong><?php echo get_project_dropdown('p_five', $p_five, $project_list, $has_projects); ?></td>
            <td><textarea id="five5" name="five5" class="form-control" style="height:50px"><?php echo $five; ?></textarea></td>
            <td><textarea id="a_five5" name="a_five5" class="form-control" style="height:50px"><?php echo $a_five; ?></textarea></td>
        </tr>
        <tr>
            <td class="align-middle text-center"><strong>01.30-02.30</strong><?php echo get_project_dropdown('p_six', $p_six, $project_list, $has_projects); ?></td>
            <td><textarea id="six6" name="six6" class="form-control" style="height:50px"><?php echo $six; ?></textarea></td>
            <td><textarea id="a_six6" name="a_six6" class="form-control" style="height:50px"><?php echo $a_six; ?></textarea></td>
        </tr>
        <tr>
            <td class="align-middle text-center"><strong>02.30-03.30</strong><?php echo get_project_dropdown('p_seven', $p_seven, $project_list, $has_projects); ?></td>
            <td><textarea id="seven7" name="seven7" class="form-control" style="height:50px"><?php echo $seven; ?></textarea></td>
            <td><textarea id="a_seven7" name="a_seven7" class="form-control" style="height:50px"><?php echo $a_seven; ?></textarea></td>
        </tr>
        <tr>
            <td class="align-middle text-center"><strong>03.30-04.30</strong><?php echo get_project_dropdown('p_eight', $p_eight, $project_list, $has_projects); ?></td>
            <td><textarea id="eight8" name="eight8" class="form-control" style="height:50px"><?php echo $eight; ?></textarea></td>
            <td><textarea id="a_eight8" name="a_eight8" class="form-control" style="height:50px"><?php echo $a_eight; ?></textarea></td>
        </tr>
        <tr>
            <td class="align-middle text-center"><strong>04.30-05.30</strong><?php echo get_project_dropdown('p_nine', $p_nine, $project_list, $has_projects); ?></td>
            <td><textarea id="nine9" name="nine9" class="form-control" style="height:50px"><?php echo $nine; ?></textarea></td>
            <td><textarea id="a_nine9" name="a_nine9" class="form-control" style="height:50px"><?php echo $a_nine; ?></textarea></td>
        </tr>
        <tr>
            <td class="align-middle text-center"><strong>05.30-06.30</strong><?php echo get_project_dropdown('p_ten', $p_ten, $project_list, $has_projects); ?></td>
            <td><textarea id="ten10" name="ten10" class="form-control" style="height:50px"><?php echo $ten; ?></textarea></td>
            <td><textarea id="a_ten10" name="a_ten10" class="form-control" style="height:50px"><?php echo $a_ten; ?></textarea></td>
        </tr>
        <tr>
            <td class="align-middle text-center"><strong>Over Time</strong><?php echo get_project_dropdown('p_over_time', $p_over_time, $project_list, $has_projects); ?></td>
            <td colspan="2"><textarea id="over_time10" name="over_time10" class="form-control" style="height:50px" placeholder="Enter Actual Over Time Task..."><?php echo $over_time; ?></textarea></td>
        </tr>
    </table>
    <input type="hidden" id="pro_id" name="pro_id" value="<?php echo $candidateid; ?>" />
    <input type="submit" name="submit" class="btn btn-primary btn-md" style="float:right;">
    </div>
</div>
</form>

<script>
	$(document).ready(function(){
  $("form").submit(function(){
    alert("Time Sheet Sumbitted Successfully");
  });
});

  /* $(function () {
//Add text editor
/* $('#one1').summernote()
}) 
$(function () {
//Add text editor
$('#two2').summernote()
})
$(function () {
//Add text editor
$('#three3').summernote()
})
$(function () {
//Add text editor
$('#four4').summernote()
})
$(function () {
//Add text editor
$('#five5').summernote()
})
$(function () {
//Add text editor
$('#six6').summernote()
})
$(function () {
//Add text editor
$('#seven7').summernote()
})
$(function () {
//Add text editor
$('#eight8').summernote()
})
$(function () {
//Add text editor
$('#nine9').summernote()
})
$(function () {
//Add text editor
$('#over_time10').summernote()
}) */ 
</script>

<script>
$(document).ready(function(){

    function makeReadonly(id) {
        var el = document.getElementById(id);
        if (el && el.value.trim() !== "") {
            $('#' + id).attr('readonly', 'readonly');
        } else if(el) {
            $('#' + id).removeAttr('readonly');
        }
    }

    var fields = [
        'one1', 'two2', 'three3', 'four4', 'five5', 'six6', 'seven7', 'eight8', 'nine9', 'ten10', 'over_time10',
        'a_one1', 'a_two2', 'a_three3', 'a_four4', 'a_five5', 'a_six6', 'a_seven7', 'a_eight8', 'a_nine9', 'a_ten10'
    ];

    for (var i = 0; i < fields.length; i++) {
        makeReadonly(fields[i]);
    }

}); 

function reload_timesheet(d) {
    $.ajax({
        type: "POST",
        url: "qvision/Recruitment/project_management/time_sheet.php",
        data: { date: d },
        success: function(data) {
            $("#main_content").html(data);
        }
    });
}
</script>
