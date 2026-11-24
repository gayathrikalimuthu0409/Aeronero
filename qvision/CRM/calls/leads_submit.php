<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../../../connect.php';

header('Content-Type: application/json; charset=UTF-8');

function outputResult($status, $message, $extra = []) {
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        outputResult(0, 'Invalid request method.');
    }

    if (!isset($con) || !$con) {
        outputResult(0, 'Database connection is not available.');
    }

    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $userId = $_SESSION['userid'] ?? null;
    $candidateId = $_SESSION['candidateid'] ?? null;

    if (!$userId && !empty($_SESSION['username'])) {
        $uStmt = $con->prepare("SELECT user_id FROM z_user_master WHERE user_name = ? LIMIT 1");
        $uStmt->execute([$_SESSION['username']]);
        $uRow = $uStmt->fetch();
        if ($uRow) {
            $userId = $uRow['user_id'];
            $_SESSION['userid'] = $userId;
        }
    }

    if (!$userId) {
        $userId = $candidateId ?: 1;
    }

    $sourceId = filter_var($_POST['Call_type'] ?? null, FILTER_VALIDATE_INT);
    if (!$sourceId) {
        outputResult(0, 'Please select a Call Source.');
    }

    $sourceCheck = $con->prepare(
        "SELECT id FROM lead_sources WHERE id = :id AND status = 1"
    );
    $sourceCheck->execute(['id' => $sourceId]);

    if (!$sourceCheck->fetch(PDO::FETCH_ASSOC)) {
        outputResult(0, 'The selected Call Source is invalid.');
    }

    // Support the different company/client fields in the existing form.
    $company = trim($_POST['client_org1'] ?? '');

    if ($company === '' && !empty($_POST['client_orgg'])) {
        $companyValue = $_POST['client_orgg'];
        $parts = explode('-', $companyValue, 2);
        $company = trim($parts[1] ?? '');
    }

    $clientName = trim($_POST['client_name'] ?? '');

    if ($clientName === '') {
        $clientName = trim($_POST['client_org'] ?? '');
    }

    $clientId = null;
    $individualClientId = 0;
    $services = (int)($_POST['services'] ?? 12);
    $callType = trim($_POST['cust_type'] ?? '');

    // Existing corporate company: client_orgg contains "id-name".
    if ($callType === '1' && trim($_POST['client_type'] ?? '') === '1') {
        $selectedCompany = trim($_POST['client_orgg'] ?? '');
        $parts = explode('-', $selectedCompany, 2);

        if (isset($parts[0]) && ctype_digit($parts[0])) {
            $clientId = (int)$parts[0];
        }
    }

    // Existing individual customer: resolve its database ID.
    if ($callType === '2' && trim($_POST['client_type1'] ?? '') === '4') {
        $individualName = trim($_POST['client_org'] ?? '');

        if ($individualName === '') {
            outputResult(0, 'Please select an Existing Individual Customer.');
        }

        $individualStmt = $con->prepare("
            SELECT id
            FROM individual_form
            WHERE client_name = :client_name
            LIMIT 1
        ");
        $individualStmt->execute(['client_name' => $individualName]);

        $individualRow = $individualStmt->fetch(PDO::FETCH_ASSOC);

        if ($individualRow) {
            $individualClientId = (int)$individualRow['id'];
        } else {
            outputResult(0, 'Existing individual customer was not found.');
        }
    }

    $contact = trim($_POST['contact'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $alternativeEmail = trim($_POST['mail'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $clientType = trim($_POST['client_type'] ?? '');

    if ($clientType === '') {
        $clientType = trim($_POST['client_type1'] ?? '');
    }

    $productService = trim($_POST['Product'] ?? '');
    if ($productService === 'Select') {
        $productService = '';
    }
    $remarks = trim($_POST['remarks'] ?? '');

    if ($contact === '') {
        outputResult(0, 'Please enter the contact number.');
    }

    $con->beginTransaction();

    $stmt = $con->prepare("
        INSERT INTO crm_leads (
            client_org,
            client_name,
            client_id,
            ind_client_id,
            services,
            contact,
            whatsapp,
            email,
            alternative_email,
            address,
            website,
            call_type,
            call_source,
            client_type,
            product_service,
            remarks,
            employee,
            created_by,
            created_on,
            status
        ) VALUES (
            :client_org,
            :client_name,
            :client_id,
            :ind_client_id,
            :services,
            :contact,
            :whatsapp,
            :email,
            :alternative_email,
            :address,
            :website,
            :call_type,
            :call_source,
            :client_type,
            :product_service,
            :remarks,
            :employee,
            :created_by,
            NOW(),
            1
        )
    ");

    $stmt->execute([
        'client_org' => $company,
        'client_name' => $clientName,
        'client_id' => $clientId,
        'ind_client_id' => $individualClientId,
        'services' => $services,
        'contact' => $contact,
        'whatsapp' => $whatsapp,
        'email' => $email,
        'alternative_email' => $alternativeEmail,
        'address' => $address,
        'website' => $website,
        'call_type' => $callType,
        'call_source' => $sourceId,
        'client_type' => $clientType,
        'product_service' => $productService,
        'remarks' => $remarks,
        'employee' => $candidateId,
        'created_by' => $userId
    ]);

    $leadId = (int)$con->lastInsertId();

    $feedbacks = $_POST['feedback1'] ?? [];
    $feedbackDates = $_POST['feedback_date1'] ?? [];
    $followupDates = $_POST['fed_date1'] ?? [];

    $feedbackStmt = $con->prepare("
        INSERT INTO crm_leads_feedback
            (lead_id, feedback, feedback_date, followup_date)
        VALUES
            (:lead_id, :feedback, :feedback_date, :followup_date)
    ");

    if (is_array($feedbacks)) {
        foreach ($feedbacks as $index => $feedbackText) {
            $feedbackText = trim((string)$feedbackText);

            if ($feedbackText === '') {
                continue;
            }

            $feedbackDate = $feedbackDates[$index] ?? date('Y-m-d');
            $followupDate = trim((string)($followupDates[$index] ?? ''));

            $feedbackStmt->execute([
                'lead_id' => $leadId,
                'feedback' => $feedbackText,
                'feedback_date' => $feedbackDate !== ''
                    ? str_replace('/', '-', $feedbackDate)
                    : date('Y-m-d'),
                'followup_date' => $followupDate !== ''
                    ? $followupDate
                    : null
            ]);
        }
    }

    $con->commit();

    outputResult(1, 'Lead added successfully.');
} catch (Throwable $e) {
    if (isset($con) && $con->inTransaction()) {
        $con->rollBack();
    }

    error_log('Lead save error: ' . $e->getMessage());
    outputResult(0, 'Unable to save lead: ' . $e->getMessage());
}
