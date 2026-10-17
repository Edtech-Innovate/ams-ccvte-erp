<?php
if (isset($_GET['id'])) {
  require '../../includes/db-config.php';
  $id = intval($_GET['id']);
  $alloted_center = $conn->query("SELECT ID, User_ID FROM Syllabi WHERE ID = $id");
  if ($alloted_center && $alloted_center->num_rows > 0) {
    $alloted_center_arr = $alloted_center->fetch_assoc();
    $user_ids = isset($alloted_center_arr['User_ID']) ? $alloted_center_arr['User_ID'] : '';

    $center_ids = json_decode($user_ids, true);
    if (is_array($center_ids)) {
        $alloted_center = implode(",", $center_ids);
    } else {
        $alloted_center = '';
    }
} else {
    $alloted_center = ''; 
}
}
?>
<!-- Modal -->
<div class="modal-header clearfix text-left">
  <button aria-label="" type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="pg-icon">close</i>
  </button>
  <h5><span class="semi-bold">Allot User</span></h5>
</div>
<form role="form" id="form-edit-allot-center" action="/app/subjects/allotment" method="POST" enctype="multipart/form-data">
  <div class="modal-body">
    <div class="row">
      
      <div class="col-md-12">
        <div class="form-group form-group-default required">
          <label>User Code</label>
          <textarea type="text" name="name" class="form-control" placeholder="ex: C001, C002,.."  ><?= $alloted_center ?></textarea>
        </div>
      </div>
    </div>
  </div>
  <div class=" modal-footer clearfix text-end">
    <div class="col-md-4 m-t-10 sm-m-t-10">
      <button aria-label="" type="submit" class="btn btn-primary btn-cons btn-animated from-left">
        <span>Update</span>
        <span class="hidden-block">
          <i class="pg-icon">tick</i>
        </span>
      </button>
    </div>
  </div>
</form>

<script>
  $(function() {
    $('#form-edit-allot-center').validate({
      rules: {
        // name: {
        //   required: true
        // },
      },
      highlight: function(element) {
        $(element).addClass('error');
        $(element).closest('.form-control').addClass('has-error');
      },
      unhighlight: function(element) {
        $(element).removeClass('error');
        $(element).closest('.form-control').removeClass('has-error');
      }
    });
  })

  $("#form-edit-allot-center").on("submit", function(e) {
    if ($('#form-edit-allot-center').valid()) {
      $(':input[type="submit"]').prop('disabled', true);
      var formData = new FormData(this);
      formData.append('id', '<?= $id ?>');
      $.ajax({
        url: this.action,
        type: 'post',
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function(data) {
          if (data.status == 200) {
            $('.modal').modal('hide');
            notification('success', data.message);
            $('#departments-table').DataTable().ajax.reload(null, false);
          } else {
            $(':input[type="submit"]').prop('disabled', false);
            notification('danger', data.message);
          }
        }
      });
      e.preventDefault();
    }
  });
</script>
