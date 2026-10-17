<?php
require '../../includes/db-config.php';
session_start();

$docket_id = '';
$dispatch_details = [];
if ($_REQUEST['docket_id']) {
    $docket_id = mysqli_real_escape_string($conn,$_REQUEST['docket_id']);
    $dispatch_certificate = $conn->query("SELECT * FROM `dispatch_certificate` WHERE dockect_id = '$docket_id'");
    if($dispatch_certificate->num_rows > 0) {
        $dispatch_details = mysqli_fetch_assoc($dispatch_certificate);
    }
}

?>
<!-- Modal -->
<div class="modal-header clearfix text-left">
    <div class="d-flex justify-content-between">
        <h5>Add Dispatch Details</h5>
        <button aria-label="" type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="pg-icon">close</i></button>
    </div>   
</div>
<form role="form" id="form-dispatch-date" action="/app/dispatch_certificate/storeAndupdateDispatchDetails" method="POST" enctype="multipart/form-data">
    <div class="modal-body">
        <div class="row mb-1 gap-2">
            <div class="col-sm-6">
                <label class="col-sm-12 col-form-label">Dispatch By</label>
                <div class="col-sm-12">
                    <input type="text" class="form-control form-control-sm" name="dispatch_by" id="dispatch_by" value="<?php echo !empty($dispatch_details['dispatch_by']) ? $dispatch_details['dispatch_by'] : ''; ?>" placeholder="eg:- DTDC/Name of Person">
                </div>
            </div>
            <div class="col-sm-6">
                <label class="col-sm-12 col-form-label">Consignment No.</label>
                <div class="col-sm-12">
                    <input type="text" class="form-control form-control-sm" name="consignment_no" id="consignment_no" value="<?php echo !empty($dispatch_details['consignment_no']) ? $dispatch_details['consignment_no'] : ''; ?>" placeholder="eg:- 343XXXXXX12">
                    <span class="text-danger" id= "consignment_no_empty"></span>
                </div>
            </div>
        </div>
        <div class="row mb-1">
            <div class="col-sm-6">
                <label class="col-sm-12 col-form-label">Dispatch Date</label>
                <div class="col-sm-12 input-daterange" id="datepicker-range">
                    <input type="text" class="form-control form-control-sm" name="dispatch_date" id="dispatch_date" value="<?php echo !empty($dispatch_details['dispatch_date']) ? date_format(date_create($dispatch_details['dispatch_date']),'d-m-Y') : ''; ?>" placeholder="select date">
                </div>
            </div>
            <div class="col-sm-6">
                <label class="col-sm-12 col-form-label">Upload Bill</label>
                <div class="col-sm-12">
                    <input name="file" type="file" accept="image/*, .pdf, .csv, .doc, .docx"/>
                </div>
            </div>
        </div>
        <div class="row mb-1">
            <div class="col-sm-6">
                <label class="col-sm-12 col-form-label">Dispatch Mode</label>
                <div class="col-sm-12">
                    <select type="text" class="form-control form-control-sm full-width" data-init-plugin="select2" name="dispatch_mode" id="dispatch_mode" value="">
                        <option value="">Select Mode</option>
                        <option value="1" <?php if(!empty($dispatch_details['mode']) && $dispatch_details['mode'] == '1' ) { ?> selected <?php } ?>>By Courier Company</option>
                        <option value="2" <?php if(!empty($dispatch_details['mode']) && $dispatch_details['mode'] == '2' ) { ?> selected <?php } ?>>By Person</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <label class="col-sm-12 col-form-label">Company Name / Consignee Name</label>
                <div class="col-sm-12">
                    <input type="text" class="form-control form-control-sm" name="courier_by" id="courier_by" value="<?php echo !empty($dispatch_details['courier_by']) ? $dispatch_details['courier_by'] : ''; ?>" placeholder="eg:- ABC Logistics">
                    <span class="text-danger" id= "courier_by"></span>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer clearfix text-end">
        <div class="col-md-4 m-t-10 sm-m-t-10">
            <button aria-label="" type="submit" id="submit-button" class="btn btn-primary btn-cons btn-animated from-left">
                <span>Register</span>
                <span class="hidden-block">
                <i class="uil uil-arrow-up-right"></i>
                </span>
            </button>
        </div>
    </div>
</form>

<script src="/assets/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js" type="text/javascript"></script>
<script src="/assets/plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
<script>

$('#datepicker-range').datepicker({
    format: 'dd-mm-yyyy',
    autoclose: true,
    endDate: '0d'
});

$("#dispatch_mode").select2({
    placeholder: 'Choose Mode'
})

$(function(){
  $('#form-dispatch-date').validate({
    rules: {
        dispatch_by: {required:true},
        dispatch_date : {required:true},
        dispatch_mode : {required:true},
    },
    highlight: function (element) {
        $(element).addClass('error');
        $(element).closest('.form-control').addClass('has-error');
    },
    unhighlight: function (element) {
        $(element).removeClass('error');
        $(element).closest('.form-control').removeClass('has-error');
    }
  });
})

$("#form-dispatch-date").on('submit',function(e){
    e.preventDefault();
    if ($("#form-dispatch-date").valid()) { 
        var url = this.action;
        var formData = new FormData(this);
        var mode = formData.get('dispatch_mode');
        if(mode == '1') {
            if($("#consignment_no").val().length == 0 ) {
                $("#consignment_no_empty").html("Please fill the consignment number");
                return;
            } else {
                $("#consignment_no_empty").html("");
            }   
        } else {
            $("#consignment_no_empty").html("");
        }
        formData.append('docket_id','<?=$docket_id?>');
        $.ajax({
            url : url,
            type : "post",
            dataType: 'json',
            cache : false,
            processData: false,
            contentType: false,
            data : formData ,
            success : function(data) {
                if (data.status == 200) {
                    $('.modal').modal('hide');
                    notification('success', data.message);
                    $('.table').DataTable().ajax.reload(null, false);
                } else {
                    notification('danger', data.message);
                    $('.table').DataTable().ajax.reload(null, false);
                }
            }
        })
    }
});
</script>