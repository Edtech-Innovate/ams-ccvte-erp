<?php
session_start();

$docket_id = '';
if ($_REQUEST['docket_id']) {
  $docket_id = trim($_REQUEST['docket_id']);
}

?>
<!-- Modal -->
<div class="modal-header clearfix text-left">
  <button aria-label="" type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="pg-icon">close</i>
  </button>
  <h5>Upload Certificate</h5>
</div>
<form role="form" id="form-upload" action="/app/dispatch_certificate/uploadFile" method="POST" enctype="multipart/form-data">
  <div class="modal-body">
    <div class="row">
      <div class="col-md-12">
        <input name="file" type="file" accept=".pdf, .doc, .docx, application/msword" />
      </div>
    </div>
  </div>
  <div class="modal-footer clearfix text-end">
    <div class="col-md-4 m-t-10 sm-m-t-10">
      <button aria-label="" type="submit" id="submit-button" class="btn btn-primary btn-cons btn-animated from-left">
        <span>Upload</span>
        <span class="hidden-block">
          <i class="uil uil-upload"></i>
        </span>
      </button>
    </div>
  </div>
</form>

<script>

$(function(){
  $('#form-upload').validate({
    rules: {
      file: {required:true},
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

$("#form-upload").on('submit',function(e){
  e.preventDefault();
  $('.modal').modal('hide');
  if ($("#form-upload").valid()) { 
    var url = this.action;
    var formData = new FormData(this);
    formData.append('docketId','<?=$docket_id?>');
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
  }
});

</script>
