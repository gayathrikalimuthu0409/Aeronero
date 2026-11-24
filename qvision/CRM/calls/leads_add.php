<?php
require '../../../connect.php';
include("../../../user.php");
$userrole = $_SESSION['userrole'] ?? '';

$currentDate = date("Y/m/d");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead Form</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
function record_fetch(a){
	var arr = a.split('-');
	var id = arr[0];
	$.ajax(
		{
			type:'GET',
			data:'id='+id,
			url:"qvision/CRM/calls/existing_data.php",
			dataType: 'json',
			success:function(value)
			{
			  if(value != null)
				{ 
					$.each(value, function(index, element)
					{
						$('#client_name').val(element.it_name);
						
						var mob = element.it_mob1 || '';
						if(mob.startsWith('+')) {
							var parts = mob.split(/[\s\-]/, 2);
							if(parts.length > 1) {
								$('input[name="country_code"]').val(parts[0].replace('+', ''));
								$('#contact').val(mob.substring(parts[0].length + 1));
							} else {
								$('#contact').val(mob);
							}
						} else {
							$('#contact').val(mob);
						}	
						$('#email').val(element.it_mail1);
						$('#mail').val(element.it_mail2);
						$('#address').val(element.address);
						$('#website').val(element.website);
						
					});
				}  
			}			
		})	
}

function productstatus(value)
{
    var serv = document.getElementById('services');
    if (serv) {
        if (value == '3' || value == '4') {
            serv.style.visibility = "hidden";
        } else {
            serv.style.visibility = "visible";
        }
    }
}

$(document).ready(function() {
    $("form[name='fupForm']").off("submit").on("submit", function(ev) {
        ev.preventDefault();
        var formData = new FormData(this);

        // Combine country code and contact number if it doesn't already have one
        var countryCode = formData.get('country_code');
        var contactNo = formData.get('contact');
        if (countryCode && contactNo && !contactNo.startsWith('+')) {
            var cc = countryCode.replace('+', '').trim();
            formData.set('contact', '+' + cc + '-' + contactNo.trim());
        }

        $.ajax({   
            url: "qvision/CRM/calls/leads_submit.php",
            method: "POST",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function(res) {
                if (res.status == 1) {
                    alert(res.message);
                    loadLeadsList();
                } else {
                    alert(res.message || "Calls Not Added");
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error, xhr.responseText);
                var errMsg = "Calls Not Added";
                try {
                    var json = JSON.parse(xhr.responseText);
                    if (json && json.message) {
                        errMsg = json.message;
                    }
                } catch(e) {
                    if (xhr.status === 404) {
                        errMsg = "Server error 404: Endpoint not found.";
                    } else if (xhr.status === 500) {
                        errMsg = "Server error 500: Internal server error.";
                    } else if (xhr.responseText) {
                        errMsg = "Error: " + xhr.responseText.substring(0, 150);
                    }
                }
                alert(errMsg);
            }
        });
    });
});
		
function back()
{
    loadLeadsList();
}

$(document).ready(function() {
    $('#Product').on('change', function() {
        var Product = this.value;
        if (!document.getElementById("services")) {
            return;
        }
        $.ajax({
            url: "qvision/CRM/find_services.php",
            type: "POST",
            data: { Product: Product },
            cache: false,
            success: function(result){
                $("#services").html(result);
            }
        });
    });
});  

function cusstatus(value)
{
    var cli1 = document.getElementById("cli1");
    var cli2 = document.getElementById("cli2");

    if (value == '4') {
        if (cli2) cli2.style.display = "revert";
    } else {
        if (cli2) cli2.style.display = "none";
    }

    if (value == '3') {
        if (cli1) cli1.style.display = "revert"; 
    } else {
        if (cli1) cli1.style.display = "none";
    } 
}

function callstatus(value)
{
    var cor2 = document.getElementById("cor2");
    var cor3 = document.getElementById("cor3");
    var cli2 = document.getElementById("cli2");
    var ind1 = document.getElementById("ind1");
    var ind2 = document.getElementById("ind2");
    var comp1 = document.getElementById("comp1");
    var comp2 = document.getElementById("comp2");

    if (value == '1') {
        if (cor2) cor2.style.display = "revert"; 
        if (cor3) cor3.style.display = "revert";
        if (cli2) cli2.style.display = "none";
    } else {
        if (cor2) cor2.style.display = "none";
        if (cor3) cor3.style.display = "none";
    }

    if (value == '2') {
        if (ind1) ind1.style.display = "revert";
        if (comp1) comp1.style.display = "none";
        if (comp2) comp2.style.display = "none";
        if (cor3) cor3.style.display = "revert";
    } else {
        if (ind1) ind1.style.display = "none";
        if (ind2) ind2.style.display = "none";
    }
} 

function clientstatus(value)
{
    var comp1 = document.getElementById("comp1");
    var comp2 = document.getElementById("comp2");

    if (value == '1') {
        if (comp2) comp2.style.display = "revert";
    } else {
        if (comp2) comp2.style.display = "none";
    }

    if (value == '2') {
        if (comp1) comp1.style.display = "revert";
    } else {
        if (comp1) comp1.style.display = "none";
    } 
}

function check() // education
{
    var len = $('#new_tab tr').length;
    len = len + 1;
    $('#new_tab').append('<tr class="row_' + len + '"><td><input type="checkbox" class="chk" name="chk[]" id="chk_' + len + '" value="' + len + '"</td><td><input type="text" class="form-control" id="feedback' + len + '" name="feedback1[]"></td><td><input type="date" class="form-control" id="feedback_date' + len + '" name="feedback_date1[]"></td><td><input type="date" class="form-control" id="fed_date' + len + '" name="fed_date1[]"></td></tr>');
}

$(document).on('click', '#enquiry_row_remove', function () {
    $('input:checkbox:checked.chk').each(function () {
        var id = $(this).val();
        var le = $('#new_tab tr').length;

        if (le <= 3) {
            alert("You Can't Delete All the Rows");
        } else {
            $('.row_' + id).remove();
        }
    });
});
	
// Mobile Number Validation      
$(document).on("change", ".mob", function () {      
    var inputvalues = $(this).val();      
    var regex = /^[0-9\-\+\s]{4,20}$/;    
    if (!regex.test(inputvalues)) {      	
        $(this).val("");    
        alert("Please Enter Valid Contact Number");    
        return false;    
    }    
});

$(document).on("change", ".wmob", function () {      
    var inputvalues = $(this).val();      
    var regex = /^[0-9\-\+\s]{4,20}$/;    
    if (inputvalues !== "" && !regex.test(inputvalues)) {      	
        $(this).val("");    
        alert("Please Enter Valid Mobile Number");    
        return false;    
    }    
});
    
// Mail validations           
$(document).on("change", ".finmail", function () {      
    var inputvalues = $(this).val();      
    var regex = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    if (inputvalues !== "" && !regex.test(inputvalues)) {      	
        $(this).val("");    
        alert("Please Enter Valid Mail ID");    
        return false;    
    }    
});  

$(document).on("change", ".mail", function () {      
    var inputvalues = $(this).val();      
    var regex = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    if (inputvalues !== "" && !regex.test(inputvalues)) {      	
        $(this).val("");    
        alert("Please Enter Valid Mail ID");    
        return false;    
    }    
}); 

function loadLeadsList() {
    $.ajax({
        type: "POST",
        url: "qvision/CRM/calls/leads_list.php",
        success: function (html) {
            $("#main_content").html(html);
        },
        error: function () {
            alert("Unable to load Leads List.");
        }
    });
}
</script>
</head>
<body>

<div class="card">
<div class="card-header">
<a onclick="return back()" style="float: right;" data-toggle="modal" class="btn btn-primary">Back</a>
</div>
<div class="card-body">
<div class="tab-content">

    <div class="active tab-pane" id="for_employment">

    <form method="POST" name="fupForm" id="fupForm" enctype="multipart/form-data">
  
    <table class="table table-bordered">
      
        <tr>
        <td colspan="6"><center><b>New Leads</b></center></td>
        </tr>
		<tr>
                <td>Call Type</td>
                <td colspan="5">
                    <select class="form-control" id="cust_type" name="cust_type" onchange="callstatus(this.value)">
                        <option value="">Choose Type</option>
						<option value="1">Corporate</option>
                        <option value="2">Individual</option>
                    </select></td>
            </tr>
		<tr>
                <td>Call Source</td>
                <td colspan="5">
                    
                    <select class="form-control" id="Call_type" name="Call_type" required>
                        <option value="">Choose Source</option>
                        <?php
                        $stmt = $con->query("
                            SELECT id, name
                            FROM lead_sources
                            WHERE status = 1
                            ORDER BY name ASC
                        ");

                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        ?>
                            <option value="<?= (int)$row['id'] ?>">
                                <?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
		 <tr id="cor2" style="display:none;">
                <td>Client Type</td>
                <td colspan="5">
                    <select class="form-control" id="client_type" name="client_type" onchange="clientstatus(this.value)">
                        <option value="">Choose Client Type</option>
						<option value="2">New</option>
                        <option value="1">Existing</option>
                    </select></td>
            </tr>
			 <tr id="ind1" style="display:none;">
                <td>Individual Customer Type</td>
                <td colspan="5">
                    <select class="form-control" id="client_type1" name="client_type1" onchange="cusstatus(this.value)">
                        <option value="">Choose Customer Type</option>
						<option value="3">New</option>
                        <option value="4">Existing</option>
                    </select></td>
            </tr>
        <tr id="comp1" style="display:none;">
        <td>Company Name*</td>
        <td colspan="5"><input type="text" class="form-control" placeholder="Enter Company Name" id="client_org1" name="client_org1" ></td>
        </tr>
		<tr id="comp2" style="display:none;">
					<td>Company Name*</td>
					<td colspan="5">
						<select class="form-control" name="client_orgg" onChange="record_fetch(this.value)" id="client_orgg">
						<option value="">Choose Company Name</option>
								<?php 
								$query = $con->query("SELECT distinct org_name,id as org_id FROM new_client_master");
								while ($row_fetch = $query->fetch()) {?>	
								
								<option value="<?php echo $row_fetch['org_id']; ?>-<?php echo $row_fetch['org_name']; ?>"> <?php echo $row_fetch['org_name']; ?> </option>
								<?php } ?>
						</select>
					</td>
				</tr>
		 <tr id="cli1">
        <td>Client Name*</td>
        <td colspan="5"><input type="text" class="form-control" id="client_name" placeholder="Enter Name" name="client_name" onKeyPress="return ValidateAlpha(event);" ></td>
        </tr>
		<script>
		 function ValidateAlpha(evt)
    {
        var keyCode = (evt.which) ? evt.which : evt.keyCode
        if ((keyCode < 65 || keyCode > 90) && (keyCode < 97 || keyCode > 123) && keyCode != 32)
         
        return false;
            return true;
    }
		</script>
		<tr id="cli2" style="display:none;">
					<td>Client Name*</td>
					<td colspan="5">
					<select class="form-control" name="client_org" id="client_org"  >
					<option value="">Choose Client Name</option>
							<?php 
							$query = $con->query("SELECT distinct client_name,id FROM individual_form");
							while ($row_fetch = $query->fetch()) {?>	
							
							<option value="<?php echo $row_fetch['client_name']; ?>"> <?php echo $row_fetch['client_name']; ?> </option>
							<?php } ?>
                     </select>
					</td>
				</tr>
      <tr>
        <td>Contact Number*</td>
        <td colspan="5">
            <div style="display: flex; align-items: center;">
                <span style="padding: 0 10px; border: 1px solid #ccc; background: #eee; height: 38px; display: flex; align-items: center; border-right: 0;">+</span>
                <input type="text" class="form-control" name="country_code" style="width: 70px; margin-right: 5px; border-left: 0; border-top-left-radius: 0; border-bottom-left-radius: 0;" placeholder="Code" required>
                <input type="text" class="form-control mob" maxlength="15" id="contact" placeholder="Enter Contact No" name="contact" required style="width: 100%;">
            </div>
        </td>
        </tr>
		<tr>
        <td>Whatsapp Number</td>
        <td colspan="5"><input type="text" maxlength="10" class="form-control wmob" id="whatsapp" placeholder="Enter Whatsapp No" name="whatsapp"></td>
        </tr>
		  <tr>
        <td>Email Id</td>
		
        <td colspan="5"><input type="email" class="form-control finmail" id="email" placeholder="Enter Email" name="email"  autocomplete="off"></td>
        </tr>
		
		 <tr>
                <td>Alternative Mail_id</td>
                <td colspan="5">
                    <input type="email" id="mail" name="mail" class="form-control mail" placeholder="Enter Mail" >
                </td>
            </tr>
			
			<tr>
        <td>Address</td>
        <td colspan="5"><input type="text" class="form-control" id="address" placeholder="Enter Address" name="address" ></td>
        </tr>
		
			  <tr id="cor3">
        <td>Website</td>
        <td colspan="5"><input type="text" class="form-control" id="website" placeholder="Enter Website" name="website" ></td>
        </tr>
   
	  <tr>
					<td>Product/Service*</td>
					<td colspan="5">
						<select name="Product" class="form-control" id="Product" onchange="productstatus(this.value)" required>
							<option value="">Select</option>
							<option value="1">Product</option>
							<option value="2">Services</option>
						</select>
					</td>
				</tr>
				 <tr>
		<td>Attach File</td>
		<td colspan="5">
		<input type="file" class="form-control" id="attachfile" name="attachfile[]" ></td>
     </tr>
    <tr>
        <td>Remarks</td>
        <td colspan="5"><input type="text" class="form-control" id="remarks" placeholder="Enter Remarks" name="remarks"></td>
        </tr>
		 </table>
		
		  <table class="table table-bordered" id="new_tab">
                    <tr>
                    <td colspan="5"><h3><center>Feedback Entry </center></h3></td>
                    </tr>
                    <tr>
                        <th>#</th>
                        <th>Feedback</th>
                        <th>Feedback Date</th>
                        <th>Followup Date</th>
                        <th>Action</th>
                    </tr>

                    <tr>
                        <td><input type="checkbox" class="chk" name="chk[]" id="chk_1" value="1" style="width:15px;height:20px;"/></td>
                        <td><input type="text" class="form-control" id="feedback" name="feedback1[]" required></td>
                        <td><input type="text" class="form-control" id="feedback_date" name="feedback_date1[]" value="<?php echo $currentDate; ?>" readonly></td>
						<td><input type="date" class="form-control" id="fed_date" name="fed_date1[]"></td>
                        <td>
                            <input type="button" class="btn btn-success" id="new_row" name="new_row" onclick="check()" value="Add">
                            <input type="button" class="btn btn-danger" id="enquiry_row_remove" value="Remove">
                        </td>
                    </tr>

                </table>
		 <div style="margin-top: 15px; margin-bottom: 25px;">
		     <input type="submit" class="btn btn-success" name="save" value="Save">
         </div>
	
    </form>
    </div>
    </div>
    </div>
    </div>
</body>
</html>
