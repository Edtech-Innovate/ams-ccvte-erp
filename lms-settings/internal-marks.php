<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/header-top.php'); ?>
<style>
  .select2-container .select2-selection {
    border-radius: 10px;
    height: 48px !important;
    font-size: 17px;
    font-family: system-ui;
  }

  .select2-container .select2-selection .select2-selection__arrow {
    top: auto;
    bottom: 11px;
  }

  .select2-container--open .select2-selection {
    box-shadow: none;
    border: 1px solid #2b303b !important;
  }

  .select2-results .select2-results__option--highlighted {
    background-color: #55638d !important;
    border-radius: 3px;
    color: #ffffff !important;
  }
</style>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/header-bottom.php'); ?>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/menu.php');
include($_SERVER['DOCUMENT_ROOT'] . '/includes/helpers.php');
include($_SERVER['DOCUMENT_ROOT'] . '/includes/ClassHelper.php');
unset($_SESSION['current_session']);
unset($_SESSION['filterBySubCourses']);
unset($_SESSION['filterByExamStatus']);
unset($_SESSION['durationFilter']);
unset($_SESSION['subCourseFilter']);
unset($_SESSION['usersFilter']);
unset($_SESSION['filterByDuration']);
unset($_SESSION['filterByVerticalType']);

$sub_course_arr = new ClassHelper();
$sub_courses = $sub_course_arr->getUserSubCourse($conn, $_SESSION['ID'], $_SESSION['Role'],$_SESSION['university_id']);
function verticalTypeFunc()
{
    $verticalType = '<option value="">Select Vertical Type</option>';
    $verticalType .= '<option value="1">Edtech Innovate</option>';
    $verticalType .= '<option value="0">IITS LLP Paramedical</option>';
    return $verticalType;
}

?>
<!-- START PAGE-CONTAINER -->
<div class="page-container ">
  <?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/topbar.php'); ?>
  <!-- START PAGE CONTENT WRAPPER -->
  <div class="page-content-wrapper ">
    <!-- START PAGE CONTENT -->
    <div class="content ">
      <!-- START JUMBOTRON -->
      <div class="jumbotron" data-pages="parallax">
        <div class=" container-fluid   sm-p-l-0 sm-p-r-0">
          <div class="inner">
            <!-- START BREADCRUMB -->
            <ol class="breadcrumb d-flex flex-wrap justify-content-between align-self-start">
               <?php $breadcrumbs = array_filter(explode("/", $_SERVER['REQUEST_URI']));
              for ($i = 1; $i <= count($breadcrumbs); $i++) {
                if (count($breadcrumbs) == $i):
                  $active = "active";
                  $crumb = explode("?", $breadcrumbs[$i]);
                  $marks_type = ($_SESSION['university_id'] == 48) ? "Internal Marks List" : "Internal Marks List";
                  echo '<li class="breadcrumb-item ' . $active . '">' . $marks_type . '</li>';
                endif;
              }
              ?>
               <div>
                <style>
                  .jumbotron {
                    overflow: visible !important;
                  }

                  .card .card-header {
                    z-index: 0 !important;
                  }
                </style>
                <!-- <div class="<?= $class1 ?> "> -->
                <?php 
                $role=$_SESSION['Role'];
                if ($role !== 'Counsellor' && $role !== 'Sub-Counsellor') { ?>
                <button class="custom_add_button" aria-label="" title="" data-toggle="tooltip"
                  data-original-title="Export Internal Marks" onclick="exportData()"><i class="uil uil-down-arrow"></i>
                  Export</button>
                 
                <!-- </div> -->
                <!-- <div class="dropdown pull-right">
                  <button class=" profile-dropdown-toggle custom_add_button ml-2" type="button" data-toggle="dropdown" aria-haspopup="true"
                    aria-expanded="false" aria-label="profile dropdown">
                    <span class="" style="border-radius:6px;">Guide & Tutorial
                    </span>
                  </button>
                  <div class="dropdown-menu dropdown-menu-right profile-dropdown" role="menu">
                    <a href="#" class="dropdown-item"><span><br />Guide tutorial<b></b></span></a>
                    <div class="dropdown-divider"></div>
                    <a href="/assets/guide/internal-marks.mp4" class="dropdown-item">How to add Internal Marks ?</a>
                  </div>
                </div> -->
                 <?php } ?>
              </div>
            </ol>
            <!-- END BREADCRUMB -->
     

          </div>
        </div>
      </div>
      <!-- END JUMBOTRON -->
      <!-- START CONTAINER FLUID -->
      <div class=" container-fluid">
        <!-- BEGIN PlACE PAGE CONTENT HERE -->
        <div class="card card-transparent">
          <div class="card-header">

          <?php if($_SESSION['university_id']==48) { 
            $class = "col-md-2";
            $class1 = "col-md-2";
            $search_class = "col-md-2";

           }else{
            $class = "col-md-2";
            $class1 = "col-md-2";
            $search_class = "col-md-2";

           } ?>
   
            <div class="row">
              <div class="col-md-2 m-b-10">
                <div class="form-group">
                  <select class="full-width" style="width:40px" data-init-plugin="select2" id="sub_courses"
                    onchange="addFilter(this.value, 'sub_courses')" data-placeholder="Choose Program">
                    <option value="">Choose Program</option>
                    <?php echo $sub_courses; 
                    ?>
                  </select>
                </div>
              </div>
             <?php if($_SESSION['university_id']==47) { ?>
              <div class="<?=  $class ?> m-b-10">
                <div class="form-group">
                  <select class="full-width" style="width:40px" data-init-plugin="select2" id="duration"
                    onchange="addFilter(this.value, 'duration')" data-placeholder="Choose Duration">
                    <option value="">Choose Duration</option>
                  </select>
                </div>
              </div>
              <?php } ?>
          
                <?php if ($_SESSION['Role'] != "Center" && $_SESSION['Role'] !="Sub-Center") { ?>
                  <div class="<?=  $class ?> ">
                    <select class="form-control" name="vartical_type" id="vartical_type"
                      onchange="addFilter(this.value, 'vartical_type')">
                      <?= verticalTypeFunc() ?>
                    </select>
                  </div>
                <?php } ?>

                <div class="<?=  $class1 ?> ">
               <!-- <button class="btn btn-info" aria-label="" title="" data-toggle="tooltip"
                  data-original-title="Export Internal Marks" onclick="exportData()"><i class="uil uil-down-arrow"></i>
                  Export</button>-->
              </div>
               <div class="col-md-2"></div>
              <div class="<?=  $search_class ?>">
                <input type="text" id="users-search-table" class="form-control pull-right custom_search_section" placeholder="Search">
              </div>
            </div>
            <div class="clearfix"></div>
          </div>

          <div class="card-body">
            <div class="">
              <table class="table table-hover nowrap table-responsive" id="users-table">
                <thead>
                  <tr>
                    <th>Student Name</th>
                    <th>Unique ID</th>
                    <th>Enrollment No.</th>
                    <th>Sub-Course Name</th>
                    <th>Duration</th>
                    <th>Centre Name</th>
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
    </div>
    <!-- END PAGE CONTENT -->
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer-top.php'); ?>
    <script type="text/javascript">
      $(function () {
        $("#vartical_type").select2({
          placeholder: "Choose Vertical Type",
        });

        var table = $('#users-table');
        var role = '<?= $_SESSION['Role'] ?>';
        var uni_id = '<?= $_SESSION['university_id'] ?>';
        let marks_type = (uni_id == 47) ? "Internal" : "Internal";
        var showcolumn = role!="Center"&&role!="Sub-Center"?true:false;
   
        var settings = {
          'processing': true,
          'serverSide': true,
          'serverMethod': 'post',
          'ajax': {
            'url': '/app/results/internl-marks-server'
          },
          'columns': [{
            data: "full_name",width: "15%",
          },

          {
            data: "Unique_ID",width: "15%",
          },
          {
            data: "Enrollment_No",width: "15%",
          },
          {
            data: "sub_course_name",width: "15%",
          },

          {
            data: "Duration",width: "15%",
            visible: (uni_id == 48) ? true : false,
          },
          {
            data: "user_name",width: "50%",
          },

          {
            data: "ID",width: "15%",
            "render": function (data, type, row) {
                 if (role !== 'Counsellor' && role !== 'Sub-Counsellor') {
                var idForUse = row.Enrollment_No==""?row.Unique_ID:row.Enrollment_No;
              var intMarkButton = '<button class="badge badge-success p-2 cursor-pointer border-0" title="Obtain ' + marks_type + ' Marks" onclick="obtExtMarks(\'' + idForUse + '\',\'' + row.Duration + '\',\'' + row.user_code + '\')" >Obtain ' + marks_type + ' Marks</button>';
              return '<div class="button-list text-end">\
              ' + intMarkButton + '\
            </div>'}else{
                return '<p></p>';
            }
            },
            // visible:showcolumn
          },

          ],
          "sDom": "<t><'row'<p i>>",
          "destroy": true,
          "scrollCollapse": true,
          "oLanguage": {
            "sLengthMenu": "_MENU_ ",
            "sInfo": "Showing <b>_START_ to _END_</b> of _TOTAL_ entries"
          },
        //   "aaSorting": [],
        //   "iDisplayLength": 25,
        //   "drawCallback": function (settings) {
        //     $('[data-toggle="tooltip"]').tooltip();
        //   },
        };

        table.dataTable(settings);

        // search box for table
        $('#users-search-table').keyup(function () {
          table.fnFilter($(this).val());
        });

      })

      function obtExtMarks(enroll, duration, user_code) {
        $.ajax({
          url: '/app/results/create-internal-marks',
          type: 'POST',
          data: { enroll: enroll, current_duration: duration, user_code: user_code },
          success: function (data) {
            $('#lg-modal-content').html(data);
            $('#lgmodal').modal('show');
            // }
          }
        })
      }
      function addFilter(id, by) {
        $.ajax({
          url: '/app/applications/filter',
          type: 'POST',
          data: {
            id,
            by
          },
          dataType: 'json',
          success: function (data) {
            if (by == "sub_courses") {
              getDuration(id);
            }

            if (data.status) {
              $('.table').DataTable().ajax.reload(null, false);
            }
          }
        })
      }

      function getDuration(id) {
        $.ajax({
          url: '/app/subjects/get-duration',
          data: { id: id },
          type: 'POST',
          success: function (data) {
            $("#duration").html(data);
            // addFilter(id, 'duration');
          }
        })
      }

      function exportData() {
        var url = '';
        var sub_courses = $("#sub_courses").val();
        var search = $('#users-search-table').val();
        var url = search.length > 0 ? "?search=" + search : "";
        window.open('/app/results/export-internal-marks' + url);
      }
    </script>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer-bottom.php'); ?>