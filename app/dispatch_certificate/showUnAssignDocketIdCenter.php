<?php 

require '../../includes/db-config.php';
session_start();

$unAssignDocketIdCenter = $conn->query("SELECT Users.Name AS Center_Name, certificate_entry.Added_For as `center_id`, COUNT(DISTINCT Students.Enrollment_No) AS Marksheet_count ,certificate_entry.Created_At as `insert_date` FROM `certificate_entry` LEFT JOIN Students ON Students.Enrollment_No = certificate_entry.Enrollment_No LEFT JOIN Users ON Users.ID = certificate_entry.Added_For WHERE certificate_entry.Dispatch_status = '1' AND certificate_entry.Docket_Id IS NULL GROUP BY Users.Name,certificate_entry.Created_At");

$unAssignDocketIdCenter_list = [];
$i = 0;
while($center = mysqli_fetch_assoc($unAssignDocketIdCenter)) {
    $unAssignDocketIdCenter_list[$i]['id'] = $center['center_id'];
    $unAssignDocketIdCenter_list[$i]['name'] = $center['Center_Name'];
    $unAssignDocketIdCenter_list[$i]['count'] = $center['Marksheet_count'];
    $unAssignDocketIdCenter_list[$i]['insert_date'] = $center['insert_date'];
    $i++;
}

if(empty($unAssignDocketIdCenter_list)) {
    echo "all center docket_id assign";
    die;
}

?>
<style>
/* Custom Checkbox Wrapper */
.custom-checkbox {
    display: inline-block;
    position: relative;
    padding-left: 30px;
    cursor: pointer;
    font-size: 16px;
    user-select: none;
}

/* Hide default checkbox */
.custom-checkbox input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
}

/* Custom Checkmark */
.custom-checkbox .checkmark {
    position: absolute;
    top: 0;
    left: 0;
    height: 20px;
    width: 20px;
    background-color: #eee;
    border-radius: 5px; /* Rounded corners */
    border: 1px solid #ccc;
    transition: all 0.3s ease;
}

/* When checked, change background color */
.custom-checkbox input:checked + .checkmark {
    background-color: #503993;
    border-color: #d3cde4;
}

/* Add a checkmark (tick) icon */
.custom-checkbox .checkmark::after {
    content: "";
    position: absolute;
    display: none;
    left: 7px;
    top: 3px;
    width: 5px;
    height: 10px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

/* Show the checkmark when checked */
.custom-checkbox input:checked + .checkmark::after {
    display: block;
}
</style>

<!-- Modal -->
<div class="modal-header clearfix text-left mb-1">
    <div class="d-flex justify-content-between">
        <h6>Genrate Docket Id</h6>
        <button aria-label="" type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="pg-icon">close</i></button>
    </div>
</div>
<!-- START CONTAINER FLUID -->
<div class="container-fluid">
    <form role="form" id="form-generateDocketId" action="/app/dispatch_certificate/generateDocketId" method="POST">
        <?php foreach ($unAssignDocketIdCenter_list as $value) { ?>
            <div class="row mb-2">
                <div class="col-sm-9 pt-2">
                    <label for="<?=$value['id']?>" class="form-check-label">
                        <?=$value['name']?>
                    </label>
                </div>
                <div class="col-sm-3">
                    <label class="custom-checkbox">
                        <input type="checkbox" id="<?=$value['id']?>" name = "<?=$value['name']?>" onclick="" value="<?=$value['id']?>"/>
                        <span class="checkmark"></span>
                    </label>
                </div>
            </div>
        <?php } ?>
        <div class="modal-footer clearfix text-end mt-2 mb-2" style="padding: 4px !important;">
            <div class="col-md-4 m-t-10 sm-m-t-10">
                <button aria-label="" type="submit" id="submit-button" class="btn btn-primary btn-cons btn-animated from-left">
                    <span>Assign DocketId</span>
                    <span class="hidden-block">
                    <i class="uil uil-arrow-up-right"></i>
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>
<!-- END CONTAINER FLUID -->

<script>

$("#form-generateDocketId").on('submit',function(e){
    e.preventDefault();
    $('.modal').modal('hide');
    var url = this.action;
    var formData = new FormData(this);
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
                notification('success', data.message);
                $('.table').DataTable().ajax.reload(null, false);
            } else {
                notification('danger', data.message);
                $('.table').DataTable().ajax.reload(null, false);
            }
        }
    })
});

</script>
