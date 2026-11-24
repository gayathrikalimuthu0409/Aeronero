<?php
require '../../../connect.php';
include("../../../user.php");

$id = $_REQUEST['id'] ?? '';
$Call_type = $_REQUEST['Call_type'] ?? '';
$client_org = $_REQUEST['client_org1'] ?? '';
$client_name = $_REQUEST['client_name'] ?? $_REQUEST['client_name1'] ?? '';
$contact = $_REQUEST['contact'] ?? $_REQUEST['contact1'] ?? '';
$countryCode = trim($_REQUEST['country_code'] ?? $_REQUEST['country_code1'] ?? '');
if ($countryCode !== '' && $contact !== '' && strpos($contact, '+') !== 0) {
    $cleanCc = ltrim($countryCode, '+');
    $contact = '+' . $cleanCc . '-' . $contact;
}
$whatsapp = $_REQUEST['whatsapp'] ?? $_REQUEST['whatsapp1'] ?? '';
$email = $_REQUEST['email'] ?? $_REQUEST['email1'] ?? '';
$mail = $_REQUEST['mail'] ?? $_REQUEST['mail1'] ?? '';
$website = $_REQUEST['website'] ?? $_REQUEST['website1'] ?? '';
$address = $_REQUEST['address'] ?? $_REQUEST['address1'] ?? '';
$Product = $_REQUEST['Product'] ?? '';
$remarks = $_REQUEST['remarks'] ?? '';

if (!empty($id)) {
    $stmt = $con->prepare("
        UPDATE crm_leads
        SET
            client_org = :client_org,
            client_name = :client_name,
            contact = :contact,
            whatsapp = :whatsapp,
            email = :email,
            alternative_email = :alternative_email,
            website = :website,
            address = :address,
            call_source = :call_source,
            product_service = :product_service,
            remarks = :remarks
        WHERE id = :id
    ");

    $stmt->execute([
        'client_org' => $client_org,
        'client_name' => $client_name,
        'contact' => $contact,
        'whatsapp' => $whatsapp,
        'email' => $email,
        'alternative_email' => $mail,
        'website' => $website,
        'address' => $address,
        'call_source' => $Call_type,
        'product_service' => $Product,
        'remarks' => $remarks,
        'id' => $id
    ]);

    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'status' => 1,
        'message' => 'Lead updated successfully.'
    ]);
} else {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'status' => 0,
        'message' => 'Invalid Lead ID.'
    ]);
}
?>
