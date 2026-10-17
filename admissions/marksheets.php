<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/header-top.php'); ?>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/header-bottom.php'); ?>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/menu.php');?>

<div class="page-container ">
  <?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/topbar.php');

  $base_url = "http://" . $_SERVER['HTTP_HOST'] . "/";
  ?>
  <div class="page-content-wrapper ">
    <div class="content ">
      <div class="jumbotron" data-pages="parallax">
        <div class=" container-fluid sm-p-l-0 sm-p-r-0">
          <div class="inner">
            <ol class="breadcrumb d-flex flex-wrap justify-content-between align-self-start">
              <?php $breadcrumbs = array_filter(explode("/", $_SERVER['REQUEST_URI']));
              for ($i = 1; $i <= count($breadcrumbs); $i++) {
                if (count($breadcrumbs) == $i):
                  $active = "active";
                  $crumb = explode("?", $breadcrumbs[$i]);
                  echo '<li class="breadcrumb-item ' . $active . '">' . ucwords($crumb[0]) . '</li>';
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
               <div class="dropdown pull-right">
                  <button class=" profile-dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true"
                    aria-expanded="false" aria-label="profile dropdown">
                    <span class="custom_add_button p-2" style="border-radius:6px;">Guide & Tutorial
                    </span>
                  </button>
                  <div class="dropdown-menu dropdown-menu-right profile-dropdown" role="menu">
                    <a href="#" class="dropdown-item"><span><br/>Guide tutorial<b></b></span></a>
                      <div class="dropdown-divider"></div>
                        <a href="/assets/guide/marksheet-download.mp4" class="dropdown-item">How to Download Bulk Marksheet ?</a>
                        
                  </div>
                 </div>

              </div>
            </ol>
          </div>
        </div>
      </div>

      <div class=" container-fluid">
        <div class="card card-transparent">
          <div class="card-header">


            <div class="pull-right">
              <div class="row">
                <!--<div class="col-xs-7" style="margin-right: 10px;">-->
                <!--  <input type="text" id="e-book-search-table" class="form-control pull-right p-2 fw-bold" placeholder="Search">-->
                <!--</div>-->
                <div class="col-xs-5" style="margin-right: 10px;">

                </div>
              </div>
            </div>
            <div class="clearfix"></div>
          </div>
          <div class="card-body">
            <form role="form" id="form-add-e-book" action="/app/marksheets/download-bulk-marksheets" method="POST" enctype="multipart/form-data">
              <div class="modal-body">
                <div class="row justify-content-center align-items-center ">
                  <div class="col-lg-4">
                    <img src="/assets/img/icons/marksheet_user.avif" class="img-fluid" alt="">
                  </div>
                  <div class="col-md-8">
                    <div class="row">
                      <div class="col-md-5 py-0 pl-0 my-0 ml-0 mb-2">
                        <div class="form-group form-group-default required marksheet_custom_field">
                          <label>Program Type</label>
                          <select required class="full-width" style="border: transparent;" id="course_type_id" name="course_type_id" onchange="getSubCourse(this.value);">
                            <option value="">Select</option>
                            <?php
                            $programs = $conn->query("SELECT ID,Name,Short_Name FROM Courses WHERE Status = 1 AND University_ID='" . $_SESSION['university_id'] . "' ");
                            while ($program = $programs->fetch_assoc()) { ?>
                              <option value="<?= $program['ID'] ?>">
                                <?= $program['Name'] . ' (' . $program['Short_Name'] . ')' ?>
                              </option>
                            <?php } ?>
                          </select>
                        </div>
                      </div>
                      <div class="col-md-5 py-0 pl-0 my-0 ml-0 mb-2">
                        <div class="form-group form-group-default marksheet_custom_field">
                          <label>Specialization/Course</label>
                          <select required class="full-width" style="border: transparent;" id="sub_course_id" name="course_id" onchange="getSemester(this.value);">
                            <option value="">Select</option>
                          </select>
                        </div>
                      </div>
                      <?php if ($_SESSION['university_id'] == 48) { ?>
                        <div class="col-md-10 py-0 pl-0 my-0 ml-0 mb-2">
                          <div class="form-group form-group-default marksheet_custom_field">
                            <label>Category</label>
                            <select class="full-width" style="border: transparent;" id="category" name="category">
                              <option value="">Choose Category</option>
                              <option value="3">3 Months</option>
                              <option value="6">6 Months</option>
                              <option value="11/certified">11 Months Certified</option>
                              <option value="11/advance-diploma">11 Months Advance Diploma</option>
                              <option value="12">1 Year</option>
                              <option value="24">2 Year</option>
                            </select>
                          </div>
                        </div>
                      <?php } else { ?>
                        <div class="col-md-10 m-0 p-0">
                          <div class="form-group form-group-default required marksheet_custom_field">
                            <label>Semester</label>
                            <select required class="full-width" name="semester" style="border: transparent;" id="semester">
                              <option value="">Choose</option>
                            </select>
                          </div>
                        </div>
                      <?php  } ?>
                      <div class="col-md-10 m-0 p-0">
                        <div class="form-group form-group-default marksheet_custom_field">
                          <label>Student</label>
                          <!-- <input type="text" class="full-width" placeholder="Enter Enrollment No. Ex : E3241, E3245 " style="border: transparent;" id="student_id" name="student_id"> -->
                          <textarea class="full-width" placeholder="Enter Enrollment No. Ex : E3241, E3245"
                            style="border: transparent; resize: none;" id="student_id" name="student_id" rows="4"></textarea>
                        </div>
                      </div>
                    </div>
                    <div class="row justify-content-start" style="column-gap: 10px;">
                      <div class="col-lg-10 m-0 p-0 ">
                        <div class="modal-footer clearfix justify-content-center">
                          <div class="col-md-12 m-t-10 sm-m-t-10 d-flex justify-content-center">
                            <?php if ($_SESSION['university_id'] == '47') { ?>
                              <input type="submit" class="btn bg-secondary text-white   from-left mark_round" name="marksheet_in_grade" value="Marksheet In Grade">
                              <input type="submit" class="btn btn-primary  mark_round from-left custom_mark_per ml-3" name="marksheet_in_Percentage" value="Marksheet In Percentage">
                            <?php } else { ?>
                              <input type="submit" class="btn btn-primary  mark_round from-left custom_mark_per" value="Save">
                            <?php } ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>              
                  </form>
              </div>
          </div>
        </div>
      </div>

    </div>

    <?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer-top.php'); ?>

    <script src="../assets/js/toastr.min.js"></script>
    <script>

      // $("#form-add-e-book").on('submit',function(e){
      //   e.preventDefault(); // Prevent default form submission
      //   var formData = new FormData(this);
      //   $.ajax({
      //     url: this.action,
      //     type: 'POST',
      //     data : formData,
      //     processData: false,
      //     contentType: false, 
      //     xhrFields: {
      //       responseType: 'blob' // Handle the binary data
      //     },
      //     success: function (data, status, xhr) {
      //       // Create a link element to trigger download
      //       const blob = new Blob([data], { type: 'application/zip' });
      //       const link = document.createElement('a');
      //       link.href = window.URL.createObjectURL(blob);
      //       link.download = 'Marksheets.zip'; // Default file name
      //       document.body.appendChild(link);
      //       link.click();
      //       document.body.removeChild(link);
      //       toastr.success("Marksheet Downloaded!");
      //       $('#form-add-e-book')[0].reset(); // Reset the form
      //     },
      //     error: function (xhr, status, error) {
      //       if (error === 'Method Not Allowed') {
      //         console.log("djnjnjnn");
      //         toastr.error("No record found!");
      //       } else {
      //         toastr.error("Failed to create ZIP file.");
      //       }
      //       $('#form-add-e-book')[0].reset(); // Reset the form
      //     }
      //   });  
      // });

      function getSubCourse(course_id) {
        const durations = $('#min_duration').val();
        const university_id = $('#university_id').val();
        const mode = $('#mode').val();
        $.ajax({
          url: '/app/certificates/get-subcourse?course_id=' + course_id,
          type: 'GET',
          success: function(data) {
            $('#sub_course_id').html(data);
            $("#sub_course_id").select2({
              placeholder: 'Choose Specialization'
            })
          }
        });
      }

      $("#sub_course_id").select2({
        placeholder: 'Choose Specialization'
      })

      $("#course_type_id").select2({
        placeholder: 'Choose Specialization'
      })

      $("#category").select2({
        placeholder: 'Select Category'
      })

      $("#semester").select2({
        placeholder: 'Select Semester'
      })

      function getSemester(id,val=null) {
        $.ajax({
          url: '/app/subjects/semester?id=' + id+"&onload="+val,
          type: 'GET',
          success: function(data) {
            $("#semester").html(data);
            $("#semester").select2({
              placeholder: 'Select Semester'
            })
          }
        })     
      }

    </script>

    <?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer-bottom.php'); ?>
