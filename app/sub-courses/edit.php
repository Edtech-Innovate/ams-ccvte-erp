<?php
if (isset($_GET['id'])) {
  require '../../includes/db-config.php';
  require '../../includes/helpers.php';

  session_start();

  $id = intval($_GET['id']);
  $sub_course = $conn->query("SELECT * FROM Sub_Courses WHERE id = $id AND University_ID = " . $_SESSION['university_id'] . " ");
  $sub_course = mysqli_fetch_assoc($sub_course);
  $month = getMonths($sub_course['Min_Duration']);
  $month = $sub_course['Mode_ID']==1?"Year":"Month"
  // print_r($sub_course);die;
?>
  <link href="../../assets/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" media="screen" />
  <link href="../../assets/plugins/bootstrap-tag/bootstrap-tagsinput.css" rel="stylesheet" type="text/css" />
  <!-- Modal -->
  <div class="modal-header clearfix text-left">
    <button aria-label="" type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="pg-icon">close</i>
    </button>
    <h5>Edit <span class="semi-bold">Specialization</span></h5>
  </div>
  <form role="form" id="form-edit-sub-course" action="/app/sub-courses/update" method="POST" enctype="multipart/form-data">
    <div class="modal-body">

      <!-- University & Course -->

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
                <option value="<?= $row['ID'] ?>" <?php if($row['ID'] ==$sub_course['Department_ID']){echo "selected";}else{ echo "";} ?>><?= $row['Name'] ?></option>
              <?php } ?>
            </select>
          </div>
        </div>

        <div class="col-md-6">
          <div class="form-group form-group-default required">
            <label>Program</label>
            <select class="full-width" style="border: transparent;" id="course" name="course">
              <option value="">Choose</option>

            </select>
          </div>
        </div>

      </div>

      <!-- Name -->
      <div class="row">
        <div class="col-md-6">
          <div class="form-group form-group-default required">
            <label>Name</label>
            <input type="text" name="name" class="form-control" placeholder="ex: Mechanical Engineering" value="<?= $sub_course['Name'] ?>" required>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group form-group-default required">
            <label>Short Name</label>
            <input type="text" name="short_name" class="form-control" placeholder="ex: ME" value="<?= $sub_course['Short_Name'] ?>" required>
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
        <label>Duration <span> <?php if(!empty($sub_course['Min_Duration'])){?> <b>(<?= $month ?>)</b><?php } ?> </span>
        </label>
        <input type="number" name="min_duration" class="full-width" style="border: transparent;" value="<?= $sub_course['Min_Duration'] ?>"  placeholder="ex: 6 (In Months)">
      
      </div>
      
      </div>
    

      <!-- Eligibility -->
      <?php $eligibilities = array("Last Qulifications");
      $selected_eligibility = !empty($sub_course['Eligibility']) ? json_decode($sub_course['Eligibility'], true) : [];

      ?>

        <div class="col-md-6">
          <div class="form-group form-group-default form-group-default-select2 required">
            <label style="z-index:9999">Academic Eligibility</label>
            <select class=" full-width" data-init-plugin="select2" id="eligibilities" name="eligibilities[]" multiple>
              <?php foreach ($eligibilities as $eligibility) { ?>
                <option value="<?= $eligibility ?>" <?php echo in_array($eligibility, $selected_eligibility) ? 'selected' : '' ?>><?= $eligibility ?></option>
              <?php } ?>
            </select>
          </div>
        </div>
      </div>
  
      <div id="fee">
      </div>
    </div>
    <div class="modal-footer clearfix text-end">
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
  <script type="text/javascript" src="../../assets/plugins/select2/js/select2.full.min.js"></script>
  <script>
    $(function() {
      $("#eligibilities").select2();
      $("#course_category").select2();
      $(".min_duration_skill").select2();
      var id = 48;
      getDetails(id);
      getProgram('<?= $sub_course['Department_ID'] ?>');
    })

    function getProgram(department_id) {
      $.ajax({
        url: '/app/sub-courses/program?id=' + department_id,
        type: 'GET',
        success: function(data) {
          $('#course').html(data);
          $('#course').val(<?= $sub_course['Course_ID'] ?>);
        }
      });
    }

    function getDetails(id) {
      // if (id == 48) {
        $('#course_category_drp').removeClass('d-none');
      // }
      // $.ajax({
      //   url: '/app/sub-courses/courses?id=' + id,
      //   type: 'GET',
      //   success: function(data) {
      //     $('#course').html(data);
      //     $('#course').val(<?= $sub_course['Course_ID'] ?>);
      //   }
      // });

      $.ajax({
        url: '/app/sub-courses/schemes?id=' + id,
        type: 'GET',
        success: function(data) {
          $('#scheme').html(data);
          $('#scheme').val(<?= $sub_course['Scheme_ID'] ?>);
        }
      });

      $.ajax({
        url: '/app/sub-courses/modes?id=' + id,
        type: 'GET',
        success: function(data) {
          $('#mode').html(data);
          $('#mode').val(<?= $sub_course['Mode_ID'] ?>);
          getFeeSructures();
        }
      });
    }

    getDetails(<?= $sub_course['University_ID'] ?>);

    function getFeeSructures() {
      const id = '<?= $sub_course['ID'] ?>';
      const durations = $('#min_duration').val();
      const university_id = $('#university_id').val();
      const mode = $('#mode').val();
      $.ajax({
        url: '/app/sub-courses/fee-structures-edit?id=' + id + '&durations=' + durations + '&university_id=' + university_id + '&mode=' + mode,
        type: 'GET',
        success: function(data) {
          $('#fee').html(data);
        }
      });
    }

    $(function() {
      $('#form-edit-sub-course').validate({
        rules: {
          min_duration:{
            required: true
          },
          department: {
            required: true
          },
          name: {
            required: true
          },
          short_name: {
            required: true
          },
          university_id: {
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

    $("#form-edit-sub-course").on("submit", function(e) {
      if ($('#form-edit-sub-course').valid()) {
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

      // Mapping of conditions to HTML content
      var conditions = [{
          values: ['certification', 'certified', 'advance_diploma'],
          content: '<option value="3">3 Months</option><option value="6">6 Months</option><option value="11-1">11 Months Certified</option><option value="11-2">11 Months Advance Diploma</option>'
        },
        {
          values: ['certification', 'certified'],
          content: '<option value="3">3 Months</option><option value="6">6 Months</option><option value="11-1">11 Months Certified</option>'
        },
        {
          values: ['certification', 'advance_diploma'],
          content: '<option value="3">3 Months</option><option value="11-2">11 Months Advance Diploma</option>'
        },
        {
          values: ['certified', 'advance_diploma'],
          content: '<option value="6">6 Months</option><option value="11-1">11 Months Certified</option><option value="11-2">11 Months Advance Diploma</option>'
        },
        {
          values: ['advance_diploma'],
          content: '<option value="6" selected>6 Months'
        },
        {
          values: ['certified'],
          content: '<option value="6" selected >6 Months</option><option value="11-1">11 Months Certified</option>'
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
        <select class="full-width" data-init-plugin="select2" id="min_duration" name="min_duration[]" multiple>\
            ' + matchedCondition.content + '\
        </select>\
    </div>');

      $("#min_duration").select2();
    }
  </script>
<?php } ?>