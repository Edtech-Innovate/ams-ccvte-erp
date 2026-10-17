<!-- Modal -->
<div class="modal-header clearfix text-left mb-1">
    <div class="d-flex justify-content-between">
        <h6>Dispatch Details</h6>
        <button aria-label="" type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="pg-icon">close</i></button>
    </div>
</div>
<!-- START CONTAINER FLUID -->
<div class="container-fluid">
    <!-- BEGIN PlACE PAGE CONTENT HERE -->
    <div class="card card-transparent">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover nowrap" id="dispatched-certificate-table">
                    <thead>
                        <tr>
                            <th>Docket Id</th>
                            <th>Consignment_No</th>
                            <th>Dispatch_By</th>
                            <th>Dispatch_Date</th>
                            <th>Mode</th>
                            <th>Courier By</th> 
                            <th>Dispatch Bill</th>
                            <th>Action</th>
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
    var table = $('#dispatched-certificate-table');
    var settings = {
        'processing': true,
        'serverSide': true,
        'serverMethod': 'post',
        'ajax': {
            'url':'/app/dispatch_certificate/dispatch_details-server',
            'type': 'POST',
            'data' : function(d) {
                d.docket_id = '<?=$_REQUEST['docket_id']?>';
            } 
        },
        'columns': [{ 
                data: "Docket_Id" ,
                render : function(data,type,row) {
                    return '<b>'+data+'</b>';
                }
            },{ 
                data: "consignment_no" ,
                render : function(data,type,row) {
                    if(data != '') {
                        return '<b>'+data+'</b>';
                    } else {
                        return '<b>------</b>';
                    }
                }
            },{ 
                data: "dispatch_by"
            },{ 
                data: "dispatch_date" , 
                render : function(data,type,row) {
                    return '<b>'+data+'</b>';
                }
            },{ 
                data: "dispatch_mode_name",
            },
            { 
                data: "courier_by",
                render : function(data,type,row) {
                    if(data != '') {
                        return '<b>'+data+'</b>';
                    } else {
                        return '<b>------</b>';
                    }
                }
            },
            {
                data : "scan_copy" , 
                render : function(data,type,row) {
                    if(data != '') {
                        var edit = '<a href="'+data+'" class="btn btn-primary btn-animated from-left" download><span style = "font-size:0.70rem;">Download</span><span class="hidden-block"><i class = "uil uil-file-download"></i></span></a>';    
                    } else {
                        var edit = '<span class="badge badge-secondary cursor-pointer">Not Uploaded</span>';
                    }
                    return edit;
                }
            },{
                data : "action",
                render : function(data,type,row) {
                    var edit = '<div onclick = "updateDispatchDetails(&#39;' + row.Docket_Id + '&#39;)"><i class = "uil uil-edit"></i></div>' 
                    return edit;
                }
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

function updateDispatchDetails(docket_id) {
    $.ajax({
        url: '/app/dispatch_certificate/insertDispatchDetails',
        type: 'POST',
        data : {docket_id},
        success: function(data) {
            $('#lg-modal-content').html(data);
            $('#lgmodal').modal('show');
        }
    })
}
</script>