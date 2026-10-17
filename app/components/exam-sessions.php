<?php 
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
?>

<div class="card card-default m-b-0">
  <div class="card-header " role="tab" id="headingExamSessions">
    <div class="card-title">
      <a class="collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapseExamSessions" aria-expanded="false" aria-controls="collapseExamSessions">
          Exam Sessions
        </a>
    </div>
  </div>
  <div id="collapseExamSessions" class="collapse" role="tabcard" aria-labelledby="headingExamSessions">
    <div class="card-body">

      <div class="row p-b-20">
        <div class="col-lg-12 text-end">
          <button type="button" class="btn btn-primary" onclick="addComponents('exam-sessions', 'lg', <?=$university_id?>)">Add</button>
        </div>
      </div>

      <div class="row p-b-20">
        <div class="col-lg-12">
          <div class="table-responsive">
            <table class="table table-hover nowrap" id="tableExamSessions">
              <thead>
                <tr>
                  <th width="20%">Name</th>
                  <th>Serial NUmber</th>
                  <th>Range</th>
                  <th>Date of Issue</th>
                  <th>Certificate Serial No.</th>
                  <th data-orderable="false">Re-Reg</th>
                  <th data-orderable="false">Back-Paper</th>
                  <!-- <th data-orderable="false"></th> -->
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


<script type="text/javascript">
  var table = $('#tableExamSessions');
  var settings = {
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'ajax': {
      'url':'/app/components/exam-sessions/server',
      type: 'POST',
      "data":function(data) {
        data.university_id = '<?=$university_id?>';
      },
    },
    'columns': [  
      { 
        data: "Name"
      },{ 
        data : "serialNum" ,
        render : function(data,type,row) {
          return (data != null) ? data : "Not Assign";
        } 
      },{
        data : "range" , 
        render : function(data,type,row) {
          return (data != null) ? data : "Not Assign";
        } 
      },{
        data : "dateOfIssue" , 
        render : function(data,type,row) {
          let dates = (data != '' && data != null) ? data.split(",").map((ele) => '<span class="label label-primary">'+ele+'</span>').join("") : "Not Assign";
          return '<div class="d-flex" style = "gap:2px;">'+dates+'</div>';
        }
      },{
        data : "certificate_serialNum" , 
        render : function(data,type,row) {
          return (data != null) ? data : "Not Assign";
        }
      },{ 
        data: "RR_Status",
        "render": function(data, type, row){
          var active = data==1 ? 'Active' : 'Inactive';
          var checked = data==1 ? 'checked' : '';
          return '<div class="form-check form-check-inline switch switch-lg success">\
            <input onclick="changeRRStatus(\''+row.ID+'\');" type="checkbox" '+checked+' id="le-status-switch-'+row.ID+'">\
            <label for="le-status-switch-'+row.ID+'">'+active+'</label>\
          </div>';
        }
      },
      { data: "BP_Status",
        "render": function(data, type, row){
          var active = data==1 ? 'Active' : 'Inactive';
          var checked = data==1 ? 'checked' : '';
          return '<div class="form-check form-check-inline switch switch-lg success">\
            <input onclick="changeBPStatus(\''+row.ID+'\');" type="checkbox" '+checked+' id="ct-status-switch-'+row.ID+'">\
            <label for="ct-status-switch-'+row.ID+'">'+active+'</label>\
          </div>';
        }
      },
      // { data: "ID",
      //   "render": function(data, type, row){
      //     return '<div class="text-end">\
      //       <i class="uil uil-edit icon-xs cursor-pointer" onclick="editComponents(\'exam-sessions\', \''+data+'\', \'lg\');"></i>\
      //       <i class="uil uil-trash icon-xs cursor-pointer" onclick="destroyComponents(\'exam-sessions\', \'ExamSessions\', \''+data+'\');"></i>\
      //     </div>'
      //   }
      // },
    ],
    "sDom": "<t><'row'<p i>>",
    "destroy": true,
    "scrollCollapse": true,
    "oLanguage": {
        "sLengthMenu": "_MENU_ ",
        "sInfo": "Showing <b>_START_ to _END_</b> of _TOTAL_ entries"
    },
    "aaSorting": [],
    "iDisplayLength": 5
  };

  table.dataTable(settings);

  function changeRRStatus(id){
    $.ajax({
      url:'/app/components/exam-sessions/rr_status?id='+id,
      type: 'GET',
      dataType: 'json',
      success: function(data) {
        if(data.status == 200){
          notification('success', data.message);
          $('#tableExamSessions').DataTable().ajax.reload(null, false);
        }else{
          notification('danger', data.message);
        }
      }
    })
  }

  function changeBPStatus(id){
    $.ajax({
      url:'/app/components/exam-sessions/bp_status?id='+id,
      type: 'GET',
      dataType: 'json',
      success: function(data) {
        if(data.status == 200){
          notification('success', data.message);
          $('#tableExamSessions').DataTable().ajax.reload(null, false);
        }else{
          notification('danger', data.message);
        }
      }
    })
  }
</script>
