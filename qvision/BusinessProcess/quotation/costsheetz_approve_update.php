<?php
ob_start();                                   // the old debug echoes below are collected here and never shown
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', 0);
require '../../../connect.php'; 
require '../../../user.php'; 
require '../../../PHPMailer/PHPMailerAutoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../../../PHPMailer/src/Exception.php';
require '../../../PHPMailer/src/PHPMailer.php';
require '../../../PHPMailer/src/SMTP.php';
$user_id =$_SESSION['userid'];
$user_role =$_SESSION['userrole'];
$candidateid=$_SESSION['candidateid'];
$cost_sheet_no = $_REQUEST['id']; 
$enquiry_id    = $_REQUEST['enquiry_id'];
$old_quote_no  = $_REQUEST['old_quote_no'];
$business_id   = $_REQUEST['business_id'];
$costsheet_id  = $_REQUEST['costsheet_id'];

// ---------------------------------------------------------------------------------------------
// RESPONSIBLE PERSON  (the person who must be told that the cost sheet was approved)
//   1) the enquiry owner (enquiry.created_by -> z_user_master)
//   2) if that is missing: the person who created the cost sheet
//   3) if that is missing too: the person who is approving now
// The old query used e.client, a column that does not exist in the enquiry table any more, and
// INNER JOINs, so one missing link returned nothing at all (blank name / user name / mail).
// ---------------------------------------------------------------------------------------------
$client = ''; $full_name = ''; $user_name = ''; $mailerID = ''; $company_name = ''; $resp_email = '';
$enq = false; $resp_candidate = '';
try {
	$st = $con->prepare("SELECT it_name, Company_name, mail, it_mail1, created_by FROM enquiry WHERE id = ?");
	$st->execute([$enquiry_id]);
	$enq = $st->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $enq = false; }

if ($enq) {
	$client       = $enq['it_name'];
	$company_name = $enq['Company_name'];
	$mailerID     = !empty($enq['it_mail1']) ? $enq['it_mail1'] : $enq['mail'];
	$resp_candidate = $enq['created_by'];
}

$find_user = function ($candidate) use ($con) {
	if ($candidate === '' || $candidate === null) { return false; }
	try {
		$q = $con->prepare("SELECT full_name, user_name, email_id FROM z_user_master WHERE candidate_id = ? LIMIT 1");
		$q->execute([$candidate]);
		return $q->fetch(PDO::FETCH_ASSOC);
	} catch (Throwable $e) { return false; }
};

$person = $find_user($resp_candidate);
if (!$person) {                                   // fallback 2: who created the cost sheet
	try {
		$q = $con->prepare("SELECT created_by FROM cost_sheet_entry WHERE cost_sheet_no = ? ORDER BY id DESC LIMIT 1");
		$q->execute([$cost_sheet_no]);
		$cs = $q->fetch(PDO::FETCH_ASSOC);
		if ($cs) { $person = $find_user($cs['created_by']); }
	} catch (Throwable $e) { }
}
if (!$person) { $person = $find_user($candidateid); }   // fallback 3: the approver

if ($person) {
	$full_name = $person['full_name'];
	$user_name = $person['user_name'];
	// the e-mail address to notify: e-mail id if present, otherwise the user name when it is an e-mail
	$resp_email = filter_var($person['email_id'], FILTER_VALIDATE_EMAIL) ? $person['email_id']
				: (filter_var($person['user_name'], FILTER_VALIDATE_EMAIL) ? $person['user_name'] : '');
}
if ($client === '' || $client === null) { $client = $full_name; }

// ---------------------------------------------------------------------------------------------
// QUOTE NUMBER  (unchanged)
// ---------------------------------------------------------------------------------------------
 $row_query = "SELECT * FROM quote_generate";

 $queryz = $con->query($row_query);
 $queryz->execute();
 $count   = $queryz->rowCount();

if($count == 0)
	
{   
   echo"1";

        $char = 'QOT';
	$month =01;
	$current_month = date('m');
	if ($current_month >= '01' && $current_month < '04'){

	if ($month >= '01' && $month < '04'){
	   $nextyear = substr(date('Y'),-2);
	} 

	if ($month >= '04'){
	   $nextyear = substr(date('Y')-1,-2);
	}
	} 

	if ($current_month >= '04'){
	if ($month >= '04'){
	   $nextyear = substr(date('Y'),-2);
	}

	if ($month < '04'){
	   $nextyear = substr(date('Y')+1,-2);
	}
	}

	 $nextyear; 
	//current year
	 $curyear = substr(date('Y'),-2); echo "<br/>";
	 $finyear = $curyear.'-'.$nextyear; echo "<br/>";
	$char_str = '1';
    $seq = 00001;
    $quoteno = sprintf("%05d", $seq);
     $QUOTE_NO = $char.''.$current_month.'/'.$finyear.'/'.$quoteno.'/'.$char_str;
	
    }else{	
       
  if($old_quote_no!=''){
	 
	    $row_query = "SELECT * FROM quote_generate where quote_no ='$old_quote_no' ";
	   $query2 = $con->query($row_query);
		 $query2->execute();
		 $count = $query2->rowCount();
		 $row = $query2->fetch();
		 $row['quote_no'];
			 if (!empty($row['quote_no'])) {
				
				$splite_val = explode("/",$row['quote_no']); 	
				  $no   =  $splite_val [0];
				  $no2   =  $splite_val [1];
				  $no3 =  $splite_val [2];
				  $char =  $splite_val [3];
				  $newchar = ++$char;
			 echo  $QUOTE_NO =  $no.''.'/'.$no2.'/'.$no3.'/'.$newchar;
			 } 
  }else{
	  	 
       echo"2";echo '**@@**';
		$row_query = "SELECT quote_no FROM quote_generate ORDER BY id DESC ";
     
		 $query2 = $con->query($row_query);
		 $query2->execute();
		 $count = $query2->rowCount();
		 $row = $query2->fetch();
		 echo $row['quote_no'];
		 
		
		 if (!empty($row['quote_no'])) {
				
				 $splite_val = explode("/",$row['quote_no']); 	
				 $no     =  $splite_val [0];
				 $no2   =  $splite_val [1];
				 $char = '1';
				
				
			 $char = 'QOT';
				$month =01;
				$current_month = date('m');
				if ($current_month >= '01' && $current_month < '04'){

				if ($month >= '01' && $month < '04'){
				   $nextyear = substr(date('Y'),-2);
				} 

				if ($month >= '04'){
				   $nextyear = substr(date('Y')-1,-2);
				}
				} 

				if ($current_month >= '04'){
				if ($month >= '04'){
				   $nextyear = substr(date('Y'),-2);
				}

				if ($month < '04'){
				   $nextyear = substr(date('Y')+1,-2);
				}
				}

				 $nextyear; 
				//current year
				 $curyear = substr(date('Y'),-2); echo "<br/>";
				 $finyear = $curyear.'-'.$nextyear; echo "<br/>";
				$char_str = '1';	
				
				
			    $find_f = substr($row['quote_no'], 0, 7);echo "<br/>";
			    $find_fs = substr($row['quote_no'], 12, 5);echo "<br/>";
			  
			    $final_quote_no = str_pad($find_fs +1, 5, 0, STR_PAD_LEFT);echo "<br/>";
			  echo  $QUOTE_NO =  $char.''.$current_month.'/'.$finyear.'/'.$final_quote_no.'/'.$char_str;
			 } 
			 } else {
    // no quote exists in the DB yet: generate the first quote number
    $char = 'QOT';
    $current_month = date('m');
    
    // Financial year logic
    $month = 01;
    if ($current_month >= '01' && $current_month < '04'){
        $nextyear = ($month >= '01' && $month < '04') ? substr(date('Y'),-2) : substr(date('Y')-1,-2);
    } else {
        $nextyear = ($month >= '04') ? substr(date('Y'),-2) : substr(date('Y')+1,-2);
    }
    
    $curyear = substr(date('Y'),-2); 
    $finyear = $curyear.'-'.$nextyear; 
    
    // First sequence number
    $seq = 1;
    $final_quote_no = sprintf("%05d", $seq);
    $char_str = '1';
    
    $QUOTE_NO =  $char.''.$current_month.'/'.$finyear.'/'.$final_quote_no.'/'.$char_str;
}
}

 $date = date('Y-m-d');

// Directly updating the cost_sheet_no
$update_query = $con->query("update cost_sheet_entry set approved_by ='$candidateid', status ='2',modified_by ='$candidateid',modified_on =NOW() WHERE cost_sheet_no= '$cost_sheet_no'");

$insert_query2= $con->query("Update enquiry set status='5',approved_by ='$candidateid' where id='$enquiry_id'");

 $row_query = "SELECT * FROM quote_generate where  cost_sheet_id ='$costsheet_id' ";

 $query2 = $con->query($row_query);
 $query2->execute();
 $quote_count = $query2->rowCount();
 $row = $query2->fetch();
 
 
 if($quote_count!=="")
 
 {
  $insert_query=$con->query("insert into quote_generate(quote_no,cost_sheet_no,quote_date,status,created_by,created_on) 
  values('$QUOTE_NO','$cost_sheet_no','$date','1','$candidateid',NOW())");     
   $update_query =$con->query("update quote_generate set status ='3' where quote_no ='$old_quote_no'");
 }
 else{
	   $insert_query=$con->query("insert into quote_generate(quote_no,cost_sheet_no,quote_date,status,created_by,created_on) 
  values('$QUOTE_NO','$cost_sheet_no','$date','2','$candidateid',NOW())"); 
	
 } 

// ---------------------------------------------------------------------------------------------
// NOTIFY THE RESPONSIBLE PERSON - best effort: a mail problem must never break the approval
// ---------------------------------------------------------------------------------------------
if ($resp_email !== '') {
	try {
		$mail = new PHPMailer(true);
		$mail->SMTPDebug = 0; 
		$mail->Mailer = "smtp";
		$mail->IsSMTP(true); 
		$mail->Port = 587;
		$mail->Host = 'webmail.quadsel.in';        
		$mail->SMTPAuth = true;                              // Enable SMTP authentication
		$mail->Username = 'quote@quadsel.in';
		$mail->Password = 'Q@2023';                           // SMTP password
		$mail->SMTPSecure = 'tls';                            // Enable encryption, 'ssl' also accepted
		$mail->Timeout = 8;                                   // seconds (default 300 can freeze the page)
		$mail->SMTPOptions = [
			'ssl' => [
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_singed' => true,
			]
		];
		$mail->From = 'quote@quadsel.in';
		$mail->FromName = 'Quote Generate Alert....';
		$mail->AddAddress($resp_email, $full_name);
		$mail->WordWrap = 50;
		$mail->isHTML(true);
		$mail->Subject = "Cost Sheet approved by Head..";
		$mail->Body = 'Dear&nbsp;&nbsp;'.$full_name.',<br> 
			&nbsp;&nbsp;	This Mail regarding your Cost sheet approved ' .
			' <h4>Thanks & Regards,</h4><br>
			'.$full_name.'
			<p>Quadsel Systems Pvt. Ltd.</p>';
		$mail->send();
	} catch (Throwable $e) {
		error_log('Cost sheet approval mail not sent: ' . $e->getMessage());
	}
}

ob_end_clean();
echo "1";
?>