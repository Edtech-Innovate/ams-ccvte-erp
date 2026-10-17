<link href="../../assets/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" media="screen" />
<link href="../../assets/plugins/bootstrap-tag/bootstrap-tagsinput.css" rel="stylesheet" type="text/css" />
<!-- Modal -->
<div class="modal-header clearfix text-left">
  <button aria-label="" type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="pg-icon">close</i>
  </button>
  <h5>Add <span class="semi-bold">Specialization</span></h5>
</div>

<?php
require '../../includes/db-config.php';
session_start();
?>
<form role="form" id="form-add-sub-course" action="/app/sub-courses/store" method="POST" enctype="multipart/form-data">
  <div class="modal-body">
    <div class="row">
      <input type="hidden" name="university_id" value="48" id="university_id">
      <div class="col-md-6" style="padding-left: 0;">
        <div class="form-group form-group-default required">
          <label>Department </label>
          <select class="full-width" style="border: transparent;" id="department" name="department" onchange="getProgram(this.value)">
            <option value="">Choose</option>
            <?php
            $getDepartment = $conn->query("SELECT ID, Name FROM Departments WHERE Status=1");
            while ($row = $getDepartment->fetch_array()) { ?>
              <option value="<?= $row['ID'] ?>"><?= $row['Name'] ?></option>
            <?php } ?>
          </select>
        </div>
      </div>
      <div class="col-md-6" style="padding-right: 0;">
        <div class="form-group form-group-default required">
          <label>Program</label>
          <select class="full-width" style="border: transparent;" id="course" name="course">
            <option value="">Choose</option>
          </select>
        </div>
      </div>
    </div>
    <!-- Name -->
    <div class=" row">
      <div class="col-md-6">
        <div class="form-group form-group-default required">
          <label>Name</label>
          <input type="text" name="name" class="form-control" placeholder="ex: Mechanical Engineering" required>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group form-group-default required">
          <label>Short Name</label>
          <input type="text" name="short_name" class="form-control" placeholder="ex: ME" required>
        </div>
      </div>
    </div>
    <!-- Scheme & Mode -->
    <div class="row">
      <div class="col-md-6">
        <div class="form-group form-group-default required">
          <label>Scheme</label>
          <select class="full-width" style="border: transparent;" id="scheme" name="scheme">

          </select>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group form-group-default required">
          <label>Mode</label>
          <select class="full-width" style="border: transparent;" id="mode" name="mode" onchange="getFeeSructures()">

          </select>
        </div>
      </div>
      <div class="col-md-6" style="padding-left: 0;">
        <div class="form-group form-group-default required">
          <label>Month</label>
        <input type="number" name="min_duration" id="min_duration" class="full-width" style="border: transparent;" value=""  placeholder="ex: 6">
        </div>
      </div>
 
    <!-- Eligibility -->
    <?php $eligibilities = array("Last Qulifications"); ?>
    
      <div class="col-md-6">
        <div class="form-group form-group-default required form-group-default-select2 ">
          <label style="z-index:9999">Academic Eligibility<span style="color: red;">*</span></label>
          <select class=" full-width" data-init-plugin="select2" id="eligibilities" name="eligibilities[]" multiple>
            <?php foreach ($eligibilities as $eligibility) { ?>
              <option value="<?= $eligibility ?>"><?= $eligibility ?></option>
            <?php } ?>
          </select>
        </div>
      </div>
    </div>
    <!-- Course Category -->

    <div id="fee">
    </div>
    <input type="hidden" id="university_name" name="university_name" class="form-control" value="<?= $_SESSION['university_name'] ?>">
  </div>
  <div class="modal-footer clearfix text-end">
    <div class="col-md-4 m-t-10 sm-m-t-10">
      <button aria-label="" type="submit" class="btn btn-primary btn-cons btn-animated from-left">
        <span>Save</span>
        <span class="hidden-block">
          <i class="pg-icon">tick</i>
        </span>
      </button>
    </div>
  </div>
</form>
<script type="text/javascript" src="../../assets/plugins/select2/js/select2.full.min.js"></script>
<script>
  $(function() {
    $("#eligibilities").select2();
    // $("#duractions").select2();
    $("#course_category").select2();
    var id = 48;
    getDetails(id);
  })

  function getProgram(department_id) {
    $.ajax({
      url: '/app/sub-courses/program?id=' + department_id,
      type: 'GET',
      success: function(data) {
        $('#course').html(data);
      }
    });
  }

  function getDetails(id = 48) {
    if(id == 48){
      $('#course_category_drp').removeClass('d-none')
    }


    $.ajax({
      url: '/app/sub-courses/schemes?id=' + id,
      type: 'GET',
      success: function(data) {
        $('#scheme').html(data);
      }
    });

    $.ajax({
      url: '/app/sub-courses/modes?id=' + id,
      type: 'GET',
      success: function(data) {
        $('#mode').html(data);
      }
    });
  }

  function getFeeSructures() {
    const durations = $('#min_duration').val();
    const university_id = $('#university_id').val();
    const mode = $('#mode').val();
    $.ajax({
      url: '/app/sub-courses/fee-structures?durations=' + durations + '&university_id=' + university_id + '&mode=' + mode,
      type: 'GET',
      success: function(data) {
        $('#fee').html(data);
      }
    });
  }

  $(function() {
    $('#form-add-sub-course').validate({
      rules: {
        department:{
          required: true
        },
        min_duration:{
          required: true
        },
        name: {
          required: true
        },
        short_name: {
          required: true
        },
        course: {
          required: true
        },
        scheme: {
          required: true
        },
        mode: {
          required: true
        },
        lateral: {
          required: true
        },
        ct_transfer: {
          required: true
        },
        'eligibilities[]': {
          required: true
        }
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

  $("#form-add-sub-course").on("submit", function(e) {
    if ($('#form-add-sub-course').valid()) {
      $(':input[type="submit"]').prop('disabled', true);
      var formData = new FormData(this);
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
            $('#sub-courses-table').DataTable().ajax.reload(null, false);
          } else {
            $(':input[type="submit"]').prop('disabled', false);
            notification('danger', data.message);
          }
        }
      });
      e.preventDefault();
    }
  });

  function chooseDuration(selectElement) {
    var selectedValues = Array.from(selectElement.selectedOptions, option => option.value);
    $('#set_course_category').removeClass('d-none');

    // Mapping of conditions to HTML content-
    var conditions = [{
        values: ['certification', 'certified', 'advance_diploma'],
        content: '<option value="3">3 Months</option><option value="6">6 Months</option><option value="11/certified">11 Months Certified</option><option value="11/advance-diploma">11 Months Advance Diploma</option>'
      },
      {
        values: ['certification', 'certified'],
        content: '<option value="3">3 Months</option><option value="6">6 Months</option><option value="11/certified">11 Months Certified</option>'
      },
      {
        values: ['certification', 'advance_diploma'],
        content: '<option value="3">3 Months</option><option value="11/advance-diploma">11 Months Advance Diploma</option>'
      },
      {
        values: ['certified', 'advance_diploma'],
        content: '<option value="6">6 Months</option><option value="11/certified">11 Months Certified</option><option value="11/advance-diploma">11 Months Advance Diploma</option>'
      },
      {
        values: ['advance_diploma'],
        content: '<option value="6" selected>6 Months'
      },
      {
        values: ['certified'],
        content: '<option value="6" selected >6 Months</option><option value="11/advance-diploma">11 Months Certified</option>'
      },
      {
        values: ['certification'],
        content: '<option value="3" selected>3 Months</option>'
      }
    ];

    // Find the first condition that matches
    var matchedCondition = conditions.find(condition => condition.values.every(value => selectedValues.includes(value)));

    // Apply the matched HTML content
    $('#set_course_category').html('<div class="form-group form-group-default form-group-default-select2 required">\
        <label style="z-index:9999">Durations</label>\
        <select class="full-width" data-init-plugin="select2" id="duractions" name="duractions[]" multiple>\
            ' + matchedCondition.content + '\
        </select>\
    </div>');

    $("#duractions").select2();
  }
</script>