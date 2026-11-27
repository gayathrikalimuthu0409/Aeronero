<?php
ob_start();                                   // old debug echoes are collected here and never shown
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
//$id=$_REQUEST['get_id'];
  $cost_sheet_no = $_REQUEST['id']; echo $cost_sheet_no;echo '***e';
  $enquiry_id    = $_REQUEST['enquiry_id'];echo $enquiry_id;  echo '****'; 
  $old_quote_no = $_REQUEST['old_quote_no'];

  echo "old";
  echo $old_quote_no;
  echo "old";
  $business_id = $_REQUEST['business_id'];
  $costsheet_id = $_REQUEST['costsheet_id'];
  echo $costsheet_id;
//  $full_name = $_REQUEST['full_name'];
echo $user_id;echo '****';
echo '****';
echo $candidateid; echo '****'; 
   //$row_query = "SELECT * FROM quote_generate";
  $stmt = $con->prepare("SELECT * from quote_generate");
  $stmt->execute();
  $count = $stmt->rowCount();

// ---------------------------------------------------------------------------------------------
// RESPONSIBLE PERSON  (the person who must be told that the cost sheet was approved)
//   1) the enquiry owner  2) else the person who created the cost sheet  3) else the approver
// The old query used e.client, a column that no longer exists in the enquiry table, so this script
// crashed here and the approve page stayed on the loading spinner.
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
if (!$person) {
	try {
		$q = $con->prepare("SELECT created_by FROM cost_sheet_entry WHERE cost_sheet_no = ? ORDER BY id DESC LIMIT 1");
		$q->execute([$cost_sheet_no]);
		$cs = $q->fetch(PDO::FETCH_ASSOC);
		if ($cs) { $person = $find_user($cs['created_by']); }
	} catch (Throwable $e) { }
}
if (!$person) { $person = $find_user($candidateid); }
if ($person) {
	$full_name = $person['full_name'];
	$user_name = $person['user_name'];
	$mailerID  = $person['user_name'];
	$resp_email = filter_var($person['email_id'], FILTER_VALIDATE_EMAIL) ? $person['email_id']
				: (filter_var($person['user_name'], FILTER_VALIDATE_EMAIL) ? $person['user_name'] : '');
}
if ($client === '' || $client === null) { $client = $full_name; }
$candidateid=$_SESSION['candidateid'];

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
	//for($n=0; $n<26; $n++) {
       //echo +$char_str;
    //}
    $seq = 00001;
    $quoteno = sprintf("%05d", $seq);
      //$QUOTE_NO = $char.''.$finyear.''.$quoteno.'/'.$char_str;
     $QUOTE_NO = $char.''.$current_month.'/'.$finyear.'/'.$quoteno.'/'.$char_str;
	
    }else{	
       echo"old quote";
       echo$old_quote_no;
	   echo"old quote";
  if($old_quote_no!=''){
	 
	    $row_query = "SELECT * FROM quote_generate where quote_no ='$old_quote_no' ";
		//echo "SELECT * FROM quote_generate where quote_no ='$old_quote_no' ";
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
     echo "SELECT quote_no FROM quote_generate ORDER BY id DESC ";
		 $query2 = $con->query($row_query);
		 $query2->execute();
		 $count = $query2->rowCount();
		 $row = $query2->fetch();
		 echo"text";
		 echo $row['quote_no'];
		 
		
		 if (!empty($row['quote_no'])) {
				
				 $splite_val = explode("/",$row['quote_no']); 	
				 $no     =  $splite_val [0];
				
				 echo $no2   =  $splite_val [1];
				  
				 echo $chars =  $splite_val [3];
				 echo"IAL";
			
				
				
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
				 echo"IAL";
				$char_str = ++$chars;	
				
				
			  // $result = preg_split('/[-_]/', $number);
			    $find_f = substr($row['quote_no'], 0, 7);echo "<br/>";
				echo"d";
			    echo$find_fs = substr($row['quote_no'], 12, 5);echo "<br/>";
			  
			 // echo  $a = sprintf("%05d", $find_fs);echo "<br/>";
			    $final_quote_no = str_pad($find_fs +1, 5, 0, STR_PAD_LEFT);echo "<br/>";
			  //echo $last_quote_no = $find_fs;echo "<br/>";
			 //echo  $final_quote_no = ++$final_no;echo "<br/>";
			 //echo $QUOTE_NO = $find_f .$final_quote_no.'/'.$char;
			 //echo  $QUOTE_NO = $no2.'/'.$final_quote_no.'/'.$char;
			 echo"opopo";
			   $QUOTE_NO =  $char.''.$current_month.'/'.$finyear.'/'.$find_fs.'/'.$char_str;
			 
			 } 
}
}



 $date = date('Y-m-d');
 // (the old code used count() on a text value - a fatal error in PHP 8)
 $update_query = $con->query("update cost_sheet_entry set approved_by ='$candidateid', status ='2',modified_by ='$candidateid',modified_on =NOW() WHERE cost_sheet_no= '$cost_sheet_no'");

$insert_query2= $con->query("Update enquiry set status='5',approved_by ='$candidateid' where id='$enquiry_id'");
/* $row_query = "SELECT qg.cost_sheet_no,e.company_name,e.client,e.mail,zum.full_name,zum.user_name,zum.candidate_id FROM `quote_generate` qg
JOIN enquiry e on qg.created_by = e.created_by
JOIN z_user_master zum WHERE zum.candidate_id = e.created_by and qg.cost_sheet_no ='$cost_sheet_no'"; */



 $row_query = "SELECT * FROM quote_generate where  cost_sheet_id ='$costsheet_id' ";



 $query2 = $con->query($row_query);
 $query2->execute();
 $quote_count = $query2->rowCount();
 $row = $query2->fetch();
 echo "abcdefgh";
 echo $quote_count;
 
 if($quote_count!==0)
 
 {
  $insert_query=$con->query("insert into quote_generate(quote_no,cost_sheet_no,cost_sheet_id,quote_date,status,created_by,created_on) 
  values('$QUOTE_NO','$cost_sheet_no','$costsheet_id','$date','1','$candidateid',NOW())");     
   /* echo "insert into quote_generate(quote_no,cost_sheet_no,cost_sheet_id,quote_date,status,created_by,created_on) 
  values('$QUOTE_NO','$cost_sheet_no','$costsheet_id','$date','1','$candidateid',NOW())"; */
   
  
 }


 else{
	   $insert_query=$con->query("insert into quote_generate(quote_no,cost_sheet_no,cost_sheet_id,quote_date,status,created_by,created_on) 
  values('$QUOTE_NO','$cost_sheet_no','$costsheet_id','$date','1','$candidateid',NOW())"); 
  
  /* echo "insert into quote_generate(quote_no,cost_sheet_no,quote_date,status,created_by,created_on) 
  values('$QUOTE_NO','$costsheet_id','$cost_sheet_no','$date','1','$candidateid',NOW())"; */
	
 } 
 

 
// NOTIFY THE RESPONSIBLE PERSON - best effort: a mail problem must never break the approval
if ($resp_email !== '') {
	try {
		$mail = new PHPMailer(true);
		$mail->SMTPDebug = 0; 
		$mail->Mailer = "smtp";
		$mail->IsSMTP(true); 
		$mail->Port = 587;
		$mail->Host = 'webmail.quadsel.in';        
		$mail->SMTPAuth = true;
		$mail->Username = 'quote@quadsel.in';
		$mail->Password = 'Q@2023';
		$mail->SMTPSecure = 'tls';
		$mail->Timeout = 8;                                   // default 300 s can freeze the page
		$mail->SMTPOptions = [
			'ssl' => [
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_singed' => true,
			]
		];
		$mail->From = 'quote@quadsel.in';
		$mail->FromName = 'Marketing Approved Alert - Cost sheet....';
		$mail->AddAddress($resp_email, $full_name);
		$mail->WordWrap = 50;
		$mail->isHTML(true);
		$mail->Subject = "Cost Sheet approved by Head..";
		$mail->Body = 'Dear&nbsp;&nbsp;'.$full_name.',<br> 
			&nbsp;&nbsp;	This Mail regarding your Cost sheet approved by Marketing Head.' .
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