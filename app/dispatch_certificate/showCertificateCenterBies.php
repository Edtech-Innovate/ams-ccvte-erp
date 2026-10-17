<!-- Modal -->
<div class="modal-header clearfix text-left mb-1">
    <div class="d-flex justify-content-between">
        <h6></h6>
        <button aria-label="" type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="pg-icon">close</i></button>
    </div>
</div>
<!-- START CONTAINER FLUID -->
<div class=" container-fluid">
    <!-- BEGIN PlACE PAGE CONTENT HERE -->
    <div class="card card-transparent">
        <div class="card-header">
            <div class="pull-right">
                <div class="col-xs-12">
                <input type="text" id="users-search-table" class="form-control pull-right" placeholder="Search">
                </div>
            </div>
            <div class="clearfix"></div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover nowrap" id="certificate-entry-table">
                    <thead>
                        <tr>
                            <th>Student_ID</th>
                            <th>Enrollment_No</th>
                            <th>Exam Session</th>
                            <th>Duration</th>
                            <th>Marksheet_No</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    <!-- END PLACE PAGE CONTENT HERE -->
</div>
<!-- END CONTAINER FLUID -->

<script type="text/javascript">
$(function(){
    var table = $('#certificate-entry-table');
    var settings = {
        'processing': true,
        'serverSide': true,
        'serverMethod': 'post',
        'ajax': {
            'url':'/app/document-issuence/certificate/showMarkSheetRecordCenterBies-server',
            'type': 'POST',
            'data' : function(d) {
                d.center_id = '<?=$_REQUEST['center_id']?>';
                d.docket_id = '<?=$_REQUEST['docket_id']?>';
            } 
        },
        'columns': [  { 
            data: "Student_id"
            },{ 
            data: "Enrollment_No"
            },{ 
            data: "Exam_session"
            },{ 
            data: "Duration"
            },{ 
            data: "Marksheet_No",
            }
        ],
        "sDom": "<t><'row'<p i>>",
        "destroy": true,
        "scrollCollapse": true,
        "oLanguage": {
            "sLengthMenu": "_MENU_ ",
            "sInfo": "Showing <b>_START_ to _END_</b> of _TOTAL_ entries"
        },
        "aaSorting": [],
        "iDisplayLength": 5,
        "drawCallback": function( settings ) {
            $('[data-toggle="tooltip"]').tooltip();
        },
    };

    table.dataTable(settings);

    // search box for table
    $('#users-search-table').keyup(function() {
        table.fnFilter($(this).val());
    });

})
</script>