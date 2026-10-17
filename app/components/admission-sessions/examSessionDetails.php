<?php 

$inputData = file_get_contents('php://input');
$inputData = json_decode($inputData,true);
$exam_session_id = $inputData['id'];
$exam_session_name = $inputData['name'];
$semester = $inputData['semester'];

?>
<div class="card p-1" id="session_container_<?=$exam_session_id?>_<?=$semester?>"> 
    <div class="row">
        <div class="col-md-2">
            <div class="form-group form-group-default required">
                <label class="truncate-label" data-bs-toggle="tooltip" title="Exam Session">Exam Session</label>
                <input type="text" name="exam_session_<?=$exam_session_id?>" id="exam_session__<?=$exam_session_id?>" class="form-control form-control-sm exam_session required" value="<?=$exam_session_name?>" readonly>
            </div>
        </div>
        <div class="col-md-1">
            <div class="form-group form-group-default required">
                <label class="truncate-label" data-bs-toggle="tooltip" title="Semester">Semester</label>
                <input type="text" name="semester_<?=$exam_session_id?>" id="semester_<?=$exam_session_id?>" value="<?=$semester?>" class="form-control form-control-sm semester required" readonly>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group form-group-default required">
                <label class="truncate-label" data-bs-toggle="tooltip" title="Marksheet Serial No">Marksheet Serial No</label>
                <input type="text" name="serial_number_<?=$exam_session_id?>" id="serial_number_<?=$exam_session_id?>"  class="form-control form-control-sm serial_number readonly" readonly>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group form-group-default required">
                <label class="truncate-label" data-bs-toggle="tooltip" title="Marksheet Range">Marksheet Range</label>
                <input type="text" name="range_<?=$exam_session_id?>" id="range_<?=$exam_session_id?>" class="form-control form-control-sm range required" onfocus="onRangeInputFieldFocus(event)" onkeyup="checkRangeInput(event)">
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group form-group-default required">
                <label class="truncate-label" data-bs-toggle="tooltip" title="Certificate Serial No">Certificate Serial No</label>
                <input type="text" name="certificate_serial_number_<?=$exam_session_id?>" id="certificate_serial_number_<?=$exam_session_id?>"  class="form-control form-control-sm serial_number readonly" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group form-group-default required">
                <label class="truncate-label" data-bs-toggle="tooltip" title="Date of Issue">Date of Issue</label>
                <input type="text" name="multi_date_<?=$exam_session_id?>" id="multi_date_<?=$exam_session_id?>"  class="form-control form-control-sm multi_date truncate-label required" data-bs-toggle="tooltip" oninput="updateTooltip(event)">
            </div>
            <span style="font-size: smaller;" class="text-primary">Please select 5 dates</span>
        </div>
    </div>
</div> 