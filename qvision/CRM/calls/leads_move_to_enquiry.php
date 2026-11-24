
<?php

require '../../../connect.php';
include("../../../user.php");

header('Content-Type: application/json');

function respond($status, $message, $extra = []) {
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message
    ], $extra));
    exit;
}

// Accept single ID or multiple IDs
$ids = $_POST['ids'] ?? [];

if (!is_array($ids) || empty($ids)) {
    $singleId = $_POST['id'] ?? $_GET['id'] ?? '';
    $ids = ($singleId !== '') ? [$singleId] : [];
}

// Validate IDs
$leadIds = [];

foreach ($ids as $id) {

    $validatedId = filter_var($id, FILTER_VALIDATE_INT);

    if ($validatedId && $validatedId > 0) {
        $leadIds[] = (int)$validatedId;
    }
}

$leadIds = array_values(array_unique($leadIds));

if (empty($leadIds)) {
    respond(0, 'Please select valid lead IDs.');
}

$userId = $_SESSION['userid'] ?? null;
$role_id = 'R018';
$flag = 1;

try {

    $con->beginTransaction();

    // Fetch and lock selected leads
    $placeholders = [];

    foreach ($leadIds as $index => $id) {
        $placeholders[] = ':id' . $index;
    }

    $sql = "
        SELECT *
        FROM crm_leads
        WHERE id IN (" . implode(',', $placeholders) . ")
        FOR UPDATE
    ";

    $stmt = $con->prepare($sql);

    foreach ($leadIds as $index => $id) {
        $stmt->bindValue(':id' . $index, $id, PDO::PARAM_INT);
    }

    $stmt->execute();

    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($leads) !== count($leadIds)) {
        throw new RuntimeException(
            'One or more selected leads were not found.'
        );
    }

    // Check already moved leads
    foreach ($leads as $lead) {

        if ((int)$lead['moved_to_enquiry'] === 1) {
            throw new RuntimeException(
                'Lead ID ' . $lead['id'] .
                ' has already been moved. No leads were moved.'
            );
        }
    }

    $movedCount = 0;
    $movedIds = [];

    // Process every selected lead
    foreach ($leads as $lead) {

        $leadId = (int)$lead['id'];

        // Map source
        $sourceStmt = $con->prepare("
            SELECT cm.id
            FROM lead_sources ls
            INNER JOIN calls_master cm
                ON LOWER(TRIM(cm.name)) = LOWER(TRIM(ls.name))
            WHERE ls.id = :source_id
            LIMIT 1
        ");

        $sourceStmt->execute([
            'source_id' => $lead['call_source']
        ]);

        $sourceId = $sourceStmt->fetchColumn();

        if (!$sourceId) {
            $sourceId = $lead['call_source'];
        }

        // Latest feedback
        $feedbackStmt = $con->prepare("
            SELECT feedback, feedback_date, followup_date
            FROM crm_leads_feedback
            WHERE lead_id = :lead_id
            ORDER BY id DESC
            LIMIT 1
        ");

        $feedbackStmt->execute([
            'lead_id' => $leadId
        ]);

        $feedbackRow = $feedbackStmt->fetch(PDO::FETCH_ASSOC);

        // Client details
        $custType = $lead['call_type'] ?? '';
        $clientType = $lead['client_type'] ?? '';

        $companyName = trim(
            (string)($lead['client_org'] ?? '')
        );

        $clientName = trim(
            (string)($lead['client_name'] ?? '')
        );

        if ($custType == '2' && $companyName === '') {
            $companyName = 'Individual Customer';
        } elseif ($companyName === '') {
            $companyName = $clientName;
        }

        // Insert into crm_calls
        $insertCall = $con->prepare("
            INSERT INTO crm_calls (
                cust_type, call_type, client_type, client_org,
                client_id, ind_client_id, client_name, role_id,
                contact, whatsapp, email, alternative_mail,
                address, Product, services, image, remarks,
                created_by, created_on, status, flag
            ) VALUES (
                :cust_type, :call_type, :client_type, :client_org,
                :client_id, :ind_client_id, :client_name, :role_id,
                :contact, :whatsapp, :email, :alternative_mail,
                :address, :product, :services, :image, :remarks,
                :created_by, NOW(), 1, :flag
            )
        ");

        $insertCall->execute([
            'cust_type' => $custType,
            'call_type' => $sourceId,
            'client_type' => $clientType,
            'client_org' => $companyName,
            'client_id' => $lead['client_id'] ?? 0,
            'ind_client_id' => $lead['ind_client_id'] ?? 0,
            'client_name' => $clientName,
            'role_id' => $role_id,
            'contact' => $lead['contact'] ?? '',
            'whatsapp' => $lead['whatsapp'] ?? '',
            'email' => $lead['email'] ?? '',
            'alternative_mail' => $lead['alternative_email'] ?? '',
            'address' => $lead['address'] ?? '',
            'product' => $lead['product_service'] ?? '',
            'services' => $lead['services'] ?? 12,
            'image' => $lead['image'] ?? '',
            'remarks' => trim((string)($lead['remarks'] ?? '')),
            'created_by' => $userId ?? $lead['created_by'],
            'flag' => $flag
        ]);

        $callId = (int)$con->lastInsertId();

        // Insert latest feedback
        if ($feedbackRow && !empty($feedbackRow['feedback'])) {

            $insertFeedback = $con->prepare("
                INSERT INTO crm_calls_feedback (
                    calls_id, feedback, feedback_date,
                    date, created_by, created_on
                ) VALUES (
                    :calls_id, :feedback, :feedback_date,
                    :followup_date, :created_by, NOW()
                )
            ");

            $insertFeedback->execute([
                'calls_id' => $callId,
                'feedback' => $feedbackRow['feedback'],
                'feedback_date' => $feedbackRow['feedback_date'] ?? date('Y-m-d'),
                'followup_date' => $feedbackRow['followup_date'] ?? null,
                'created_by' => $userId ?? $lead['created_by']
            ]);
        }

        // Update original lead
        $update = $con->prepare("
            UPDATE crm_leads
            SET moved_to_enquiry = 1,
                enquiry_id = :call_id,
                moved_at = NOW(),
                status = 0
            WHERE id = :id
              AND moved_to_enquiry = 0
        ");

        $update->execute([
            'call_id' => $callId,
            'id' => $leadId
        ]);

        if ($update->rowCount() !== 1) {
            throw new RuntimeException(
                'Unable to update lead ID ' . $leadId
            );
        }

        $movedCount++;

        $movedIds[] = [
            'lead_id' => $leadId,
            'call_id' => $callId
        ];
    }

    // Commit all leads together
    $con->commit();

    $message = $movedCount === 1
        ? 'Lead successfully moved to Calls List.'
        : $movedCount . ' leads successfully moved to Calls List.';

    respond(1, $message, [
        'moved_count' => $movedCount,
        'moved_leads' => $movedIds
    ]);

} catch (Throwable $e) {

    if ($con->inTransaction()) {
        $con->rollBack();
    }

    respond(0, $e->getMessage());
}

?>
