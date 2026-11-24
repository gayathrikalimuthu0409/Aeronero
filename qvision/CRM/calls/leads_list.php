<?php
require '../../../connect.php';
include("../../../user.php");

$user_id = $_SESSION['userid'];
$userrole = $_SESSION['userrole'];
$candidateid = $_SESSION['candidateid'] ?? '';

$department = '';
$check_userid = 990;

$stmt = $con->prepare("
    SELECT a.department, a.user_group_code, b.head_status
    FROM z_user_master a
    LEFT JOIN staff_master b ON a.candidate_id = b.candid_id
    WHERE a.user_id = :uid
");
$stmt->execute(['uid' => $user_id]);
$userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

if ($userInfo && (int)$userInfo['head_status'] === 1) {
    $department = $userInfo['department'];
    $groupCode = $userInfo['user_group_code'];

    $stmt = $con->prepare("
        SELECT user_id
        FROM z_user_master
        WHERE department = :department
          AND user_group_code = :group_code
        LIMIT 1
    ");
    $stmt->execute([
        'department' => $department,
        'group_code' => $groupCode
    ]);
    $groupUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($groupUser) {
        $check_userid = $groupUser['user_id'];
    }
}

if ($userrole === 'R001') {
    $sql = $con->query("
        SELECT l.*,
               s.name AS source_name,
               f.feedback,
               f.feedback_date,
               f.followup_date
        FROM crm_leads l
        LEFT JOIN lead_sources s ON s.id = l.call_source
        LEFT JOIN crm_leads_feedback f ON f.id = (
            SELECT MAX(f2.id)
            FROM crm_leads_feedback f2
            WHERE f2.lead_id = l.id
        )
        ORDER BY l.id DESC
    ");
} elseif ((string)$user_id === (string)$check_userid && $department !== '') {
    $stmt = $con->prepare("
        SELECT l.*,
               s.name AS source_name,
               f.feedback,
               f.feedback_date,
               f.followup_date
        FROM crm_leads l
        LEFT JOIN lead_sources s ON s.id = l.call_source
        LEFT JOIN crm_leads_feedback f ON f.id = (
            SELECT MAX(f2.id)
            FROM crm_leads_feedback f2
            WHERE f2.lead_id = l.id
        )
        LEFT JOIN z_user_master u ON u.user_id = l.created_by
        WHERE u.department = :department
        ORDER BY l.id DESC
    ");
    $stmt->execute(['department' => $department]);
    $sql = $stmt;
} else {
    $stmt = $con->prepare("
        SELECT l.*,
               s.name AS source_name,
               f.feedback,
               f.feedback_date,
               f.followup_date
        FROM crm_leads l
        LEFT JOIN lead_sources s ON s.id = l.call_source
        LEFT JOIN crm_leads_feedback f ON f.id = (
            SELECT MAX(f2.id)
            FROM crm_leads_feedback f2
            WHERE f2.lead_id = l.id
        )
        WHERE l.employee = :candidate
           OR l.created_by = :user_id
        ORDER BY l.id DESC
    ");
    $stmt->execute([
        'candidate' => $candidateid,
        'user_id' => $user_id
    ]);
    $sql = $stmt;
}

function leadEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<style>
    
.lead-action-buttons {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    min-width: 250px;
}

.lead-action-btn {
    padding: 4px 8px !important;
    font-size: 11px !important;
    line-height: 1.4 !important;
    border-radius: 4px !important;
    white-space: nowrap;
}

.lead-action-btn i {
    margin-right: 3px;
}

.lead-action-btn:hover {
    opacity: 0.85;
}

.lead-select-checkbox {
    width: 17px;
    height: 17px;
    cursor: pointer;
}

#bulkMoveBtn {
    margin-left: 10px;
    background: #198754;
    border: 1px solid white;
    color: white;
}

#bulkMoveBtn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

</style>
<div class="card card-primary" style="margin-left:5% !important;">
    <div class="card-header" style="background-color:#009EE3 !important;color:white;">
        <h3 class="card-title" style="display:inline-block;">
            Leads List
        </h3>

        <button type="button"
                onclick="leadsAdd()"
                class="btn btn-primary"
                style="float:right;background:#009EE3;border:1px solid white;margin-left:8px;">
            <i class="fa fa-plus"></i> ADD
        </button>

        <button type="button"
                id="bulkMoveBtn"
                class="btn btn-success"
                onclick="moveSelectedLeads()"
                style="float:right;margin-left:8px;"
                disabled>
            <i class="fa fa-exchange"></i> Move Selected
            (<span id="selectedLeadCount">0</span>)
        </button>

        <?php 
        // ⚠️ Change '4' below to the specific user_id login you want to allow to see this button
        $allowed_import_userid = 44; 
        if ((int)$user_id === $allowed_import_userid): 
        ?>
        <button type="button"
                onclick="openImportModal()"
                class="btn btn-success"
                style="float:right;border:1px solid white;">
            <i class="fa fa-file-excel-o"></i> Import Excel
        </button>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table id="leadsTable"
                   class="table table-bordered table-striped display nowrap"
                   style="width:100%;">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox"
                                class="lead-select-checkbox select-all-leads"
                                title="Select All">
                        </th>
                        <th>#</th>
                        <th>Date</th>
                        <th>Organisation</th>
                        <th>Client</th>
                        <th>Call Source</th>
                        <th>Feedback</th>
                        <th>Feedback Date</th>
                        <th>Followup Date</th>
                        <th>Created By</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php $cnt = 1; ?>
                <?php while ($lead = $sql->fetch(PDO::FETCH_ASSOC)): ?>
                    <?php
                    $createdByName = 'Unknown User';

                    $nameStmt = $con->prepare(
                        "SELECT full_name FROM z_user_master WHERE user_id = :uid LIMIT 1"
                    );
                    $nameStmt->execute(['uid' => $lead['created_by']]);
                    $nameRow = $nameStmt->fetch(PDO::FETCH_ASSOC);

                    if ($nameRow && $nameRow['full_name']) {
                        $createdByName = $nameRow['full_name'];
                    }

                    $moved = (int)$lead['moved_to_enquiry'] === 1;
                    ?>
                    <tr>
                        <td>
                            <?php if (!$moved): ?>
                                <input type="checkbox"
                                    class="lead-select-checkbox lead-row-checkbox"
                                    value="<?= (int)$lead['id'] ?>">
                            <?php endif; ?>
                        </td>

                        <td><?= $cnt++ ?>.</td>
                        <td><?= leadEscape($lead['created_on']) ?></td>
                        <td><?= leadEscape($lead['client_org']) ?></td>
                        <td><?= leadEscape($lead['client_name']) ?></td>
                        <td><?= leadEscape($lead['source_name']) ?></td>
                        <td><?= leadEscape($lead['feedback']) ?></td>
                        <td><?= leadEscape($lead['feedback_date']) ?></td>
                        <td><?= leadEscape($lead['followup_date']) ?></td>
                        <td><?= leadEscape($createdByName) ?></td>
                        <td>
                            <?php if ($moved): ?>
                                <span style="color:green;font-weight:bold;">
                                    Moved to Enquiry
                                </span>
                            <?php elseif ((int)$lead['status'] === 1): ?>
                                <span style="color:green;font-weight:bold;">
                                    Active
                                </span>
                            <?php else: ?>
                                <span style="color:red;font-weight:bold;">
                                    Inactive
                                </span>
                            <?php endif; ?>
                        </td>
                        
                        <td>
                            <div class="lead-action-buttons">
                                <?php if (!$moved): ?>
                                    <button type="button"
                                            class="btn btn-primary btn-sm lead-action-btn"
                                            onclick="moveLead(<?= (int)$lead['id'] ?>)">
                                        <i class="fa fa-exchange"></i>
                                        Move
                                    </button>
                                <?php endif; ?>

                                
                                <button type="button"
                                        class="btn btn-warning btn-sm lead-action-btn"
                                        onclick="calls_edit(<?= (int)$lead['id'] ?>)">
                                    <i class="fa fa-edit"></i> Edit
                                </button>


                                <button type="button"
                                        class="btn btn-danger btn-sm lead-action-btn"
                                        onclick="deleteLeadFeedback(<?= (int)$lead['id'] ?>)">
                                    <i class="fa fa-trash"></i>
                                    Delete Feedback
                                </button>
                            </div>
                        </td>

                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- SheetJS Library for reading Excel files in browser -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<!-- Excel Import Modal -->
<div id="excelImportModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
    <div style="background:#fff; width:500px; max-width:90%; margin:8% auto; padding:20px; border-radius:6px; box-shadow:0 4px 12px rgba(0,0,0,0.2);">
        <h4 style="margin-top:0; border-bottom:1px solid #ddd; padding-bottom:10px;">
            Import Leads from Excel
            <span onclick="closeImportModal()" style="float:right; cursor:pointer; font-size:20px;">&times;</span>
        </h4>
        
        <form id="excelImportForm">
            <div class="form-group" style="margin-top:15px;">
                <label>Select Excel File (<code>.xlsx</code>, <code>.xls</code>, <code>.csv</code>)</label>
                <input type="file" id="excelFileInput" class="form-control" accept=".xlsx, .xls, .csv" required>
            </div>
            
            <div id="importStatus" style="margin-top:10px; display:none; font-weight:bold;"></div>

            <div style="text-align:right; margin-top:20px; border-top:1px solid #ddd; padding-top:15px;">
                <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Cancel</button>
                <button type="submit" id="importSubmitBtn" class="btn btn-success">Upload & Insert</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {

    if (window.jQuery && $.fn.DataTable) {

        if ($.fn.DataTable.isDataTable('#leadsTable')) {
            $('#leadsTable').DataTable().destroy();
        }

        $('#leadsTable').DataTable({

            dom: 'Bfrtip',
            buttons: ['excelHtml5'],
            scrollX: true,
            pageLength: 10,

            // Disable sorting on first column (Select All checkbox)
            columnDefs: [
                {
                    targets: 0,
                    orderable: false,
                    searchable: false
                }
            ],

            drawCallback: function () {
                updateSelectedLeadCount();
            }

        });

        updateSelectedLeadCount();
    }

})();

function leadsAdd() {
    $.ajax({
        type: 'POST',
        url: 'qvision/CRM/calls/leads_add.php',
        success: function (html) {
            $('#main_content').html(html);
        },
        error: function () {
            alert('Unable to open the Add Lead form.');
        }
    });
}

function moveLead(id) {
    if (!confirm('Move this lead to Enquiry?')) return;

    $.ajax({
        type: 'POST',
        url: 'qvision/CRM/calls/leads_move_to_enquiry.php',
        data: { id: id },
        dataType: 'json',
        success: function (res) {
            alert(res.message);
            if (res.status === 1) {
                loadLeadsList();
            }
        },
        error: function (xhr) {
            alert('Unable to move lead. ' + (xhr.responseText || ''));
        }
    });
}



// Prevent checkbox click from triggering column sorting
$(document)
.off('click.leadCheckboxSort')
.on('click.leadCheckboxSort', '.select-all-leads', function(e) {
    e.stopPropagation();
});


/* =========================================
   SELECT ALL LEADS - FIXED
========================================= */

// Handle both original and DataTables cloned headers
$(document)
.off('change.leadSelectAll')
.on('change.leadSelectAll', '.select-all-leads', function () {

    var isChecked = $(this).is(':checked');

    if ($.fn.DataTable &&
        $.fn.DataTable.isDataTable('#leadsTable')) {

        var table = $('#leadsTable').DataTable();

        // Select every filtered lead, including other pages
        table.rows({
            search: 'applied'
        }).every(function () {

            var row = this.node();

            $(row).find('.lead-row-checkbox')
                .prop('checked', isChecked);

        });

    } else {

        $('.lead-row-checkbox').prop('checked', isChecked);

    }

    updateSelectedLeadCount();

});


// =========================================
// INDIVIDUAL CHECKBOX
// =========================================

$(document)
.off('change.leadRowSelect')
.on('change.leadRowSelect', '.lead-row-checkbox', function () {

    updateSelectedLeadCount();

});


// =========================================
// GET SELECTED LEAD IDS
// =========================================

function getSelectedLeadIds() {

    var ids = [];

    if ($.fn.DataTable &&
        $.fn.DataTable.isDataTable('#leadsTable')) {

        var table = $('#leadsTable').DataTable();

        table.rows().every(function () {

            var checkbox = $(this.node())
                .find('.lead-row-checkbox');

            if (checkbox.prop('checked')) {
                ids.push(checkbox.val());
            }

        });

    } else {

        $('.lead-row-checkbox:checked').each(function () {
            ids.push($(this).val());
        });

    }

    return ids;
}


// =========================================
// UPDATE SELECTED COUNT
// =========================================

function updateSelectedLeadCount() {

    var selectedIds = getSelectedLeadIds();

    $('#selectedLeadCount').text(selectedIds.length);

    $('#bulkMoveBtn').prop(
        'disabled',
        selectedIds.length === 0
    );

    var total = 0;
    var checked = 0;

    if ($.fn.DataTable &&
        $.fn.DataTable.isDataTable('#leadsTable')) {

        var table = $('#leadsTable').DataTable();

        table.rows({
            search: 'applied'
        }).every(function () {

            var checkbox = $(this.node())
                .find('.lead-row-checkbox');

            if (checkbox.length) {

                total++;

                if (checkbox.prop('checked')) {
                    checked++;
                }

            }

        });

    } else {

        total = $('.lead-row-checkbox').length;
        checked = $('.lead-row-checkbox:checked').length;

    }

    // Update original and cloned header checkboxes
    $('.select-all-leads')
        .prop('checked', total > 0 && total === checked)
        .prop(
            'indeterminate',
            checked > 0 && checked < total
        );

}




// Move selected leads
function moveSelectedLeads() {

    var selectedIds = getSelectedLeadIds();

    if (selectedIds.length === 0) {
        alert('Please select at least one lead.');
        return;
    }

    if (!confirm(
        'Are you sure you want to move ' +
        selectedIds.length +
        ' selected leads to Enquiry?'
    )) {
        return;
    }

    $('#bulkMoveBtn')
        .prop('disabled', true)
        .html('<i class="fa fa-spinner fa-spin"></i> Moving...');

    $.ajax({

        type: 'POST',

        url: 'qvision/CRM/calls/leads_move_to_enquiry.php',

        data: {
            ids: selectedIds
        },

        dataType: 'json',

        success: function (res) {

            alert(res.message);

            if (res.status === 1) {
                loadLeadsList();
            } else {
                updateSelectedLeadCount();
            }
        },

        error: function (xhr) {

            console.error(xhr.responseText);

            alert('Unable to move selected leads.');

            updateSelectedLeadCount();
        }

    });
}


function deleteLeadFeedback(id) {
    if (!confirm('Delete all feedback records for this lead? The lead itself will remain.')) {
        return;
    }

    $.ajax({
        type: 'POST',
        url: 'qvision/CRM/calls/leads_delete_feedback.php',
        data: { id: id },
        dataType: 'json',
        success: function (res) {
            alert(res.message);
            if (res.status === 1) {
                loadLeadsList();
            }
        },
        error: function () {
            alert('Unable to delete feedback.');
        }
    });
}

function loadLeadsList() {
    $.ajax({
        type: 'POST',
        url: 'qvision/CRM/calls/leads_list.php',
        success: function (html) {
            $('#main_content').html(html);
        },
        error: function () {
            alert('Unable to reload Leads List.');
        }
    });
}

function calls_edit(id) {
    $.ajax({
        type: 'POST',
        url: 'qvision/CRM/calls/leads_edit.php?id=' + id,
        success: function(html) {
            $('#main_content').html(html);
        },
        error: function(xhr) {
            console.error(xhr.responseText);
            alert('Unable to open Edit Lead form.');
        }
    });
}

// --- Excel Import Modal Script ---
function openImportModal() {
    $('#excelFileInput').val('');
    $('#importStatus').hide().text('');
    $('#excelImportModal').fadeIn(200);
}

function closeImportModal() {
    $('#excelImportModal').fadeOut(200);
}

$('#excelImportForm').on('submit', function(e) {
    e.preventDefault();

    var fileInput = document.getElementById('excelFileInput');
    if (!fileInput.files.length) {
        alert('Please choose an Excel file.');
        return;
    }

    var file = fileInput.files[0];
    var reader = new FileReader();

    $('#importSubmitBtn').prop('disabled', true).text('Processing...');
    $('#importStatus').show().css('color', '#0056b3').text('Reading Excel file...');

    reader.onload = function(event) {
        try {
            var data = new Uint8Array(event.target.result);
            var workbook = XLSX.read(data, { type: 'array', cellDates: true });
            var firstSheetName = workbook.SheetNames[0];
            var worksheet = workbook.Sheets[firstSheetName];

            var rows = XLSX.utils.sheet_to_json(worksheet, { defval: "", raw: false });

            if (rows.length === 0) {
                alert('The selected Excel sheet is empty.');
                $('#importSubmitBtn').prop('disabled', false).text('Upload & Insert');
                $('#importStatus').hide();
                return;
            }

            $('#importStatus').text('Uploading ' + rows.length + ' rows to database...');

            $.ajax({
                type: 'POST',
                url: 'qvision/CRM/calls/leads_import_excel.php',
                data: JSON.stringify({ rows: rows }),
                contentType: 'application/json',
                dataType: 'json',
                success: function(res) {
                    $('#importSubmitBtn').prop('disabled', false).text('Upload & Insert');
                    if (res.status === 1) {
                        alert(res.message);
                        closeImportModal();
                        loadLeadsList();
                    } else {
                        $('#importStatus').css('color', 'red').text(res.message);
                    }
                },
                error: function(xhr) {
                    $('#importSubmitBtn').prop('disabled', false).text('Upload & Insert');
                    $('#importStatus').css('color', 'red').text('Server error during import.');
                    console.error(xhr.responseText);
                }
            });

        } catch (err) {
            $('#importSubmitBtn').prop('disabled', false).text('Upload & Insert');
            $('#importStatus').css('color', 'red').text('Invalid Excel file format.');
            console.error(err);
        }
    };

    reader.readAsArrayBuffer(file);
});
</script>