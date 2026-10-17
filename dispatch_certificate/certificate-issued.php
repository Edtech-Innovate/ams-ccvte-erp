<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/header-top.php'); ?>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/header-bottom.php'); ?>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/menu.php'); ?>
<!-- START PAGE-CONTAINER -->
<div class="page-container">
  <?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/topbar.php'); ?>
  <!-- START PAGE CONTENT WRAPPER -->
  <div class="page-content-wrapper ">
    <!-- START PAGE CONTENT -->
    <div class="content ">
      <!-- START JUMBOTRON -->
      <div class="jumbotron" data-pages="parallax">
        <div class=" container-fluid sm-p-l-0 sm-p-r-0">
          <div class="inner">
            <!-- START BREADCRUMB -->
            <ol class="breadcrumb d-flex flex-wrap justify-content-between align-self-start">
              <?php
              $breadcrumbs = array_filter(explode("/", $_SERVER['REQUEST_URI']));
              for ($i = 1; $i <= count($breadcrumbs); $i++) {
                if (count($breadcrumbs) == $i) :
                  $active = "active";
                  $crumb = explode("?", $breadcrumbs[$i]);
                  $breadcrumbText = str_replace("-", " ", $crumb[0]); // Replace hyphens with spaces
                  echo '<li class="breadcrumb-item ' . $active . '">' . ucwords(strtolower($breadcrumbText)) . '</li>';
                endif;
              }
              ?>
              <div>
                <button class="btn btn-link bg-primary text-white" aria-label="" title="" data-toggle="tooltip" data-original-title="Upload" onclick="add('dispatch_certificate', 'lg')">Upload <i class="uil uil-export ml-2"></i></button>
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
            <div class="pull-right">
              <div class="col-xs-12">
                <input type="text" id="users-search-table" class="form-control pull-right custom_search_section" placeholder="Search">
              </div>
            </div>
            <div class="clearfix"></div>
          </div>
          <div class="card-body">
            <div class="">
              <table class="table table-hover nowrap table-responsive" id="marksheet-entry-table">
                <thead>
                  <tr>
                    <th>Student_ID</th>
                    <!--<th>Enrollment_No</th>-->
                    <th>Exam Session</th>
                    <th>Duration</th>
                    <th>Certificate_No</th>
                    <th>Docket_Id</th>
                    <th>Dispatch_status</th>
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
      $(function() {
        var table = $('#marksheet-entry-table');
        var settings = {
          'processing': true,
          'serverSide': true,
          'serverMethod': 'post',
          'ajax': {
            'url': '/app/dispatch_certificate/certificate_entry-server'
          },
          'columns': [
        //       {
        //     data: "Student_id",
        //     width: "20%",
        //     render: function(data, type, row) {
        //       return "<b>" + data + "</b>";
        //     }
        //   },
          {
            data: "Student_ID",
            width: "20%",
            render: function(data, type, row) {
              return "<b>" + data + "</b>";
            }
          }, {
            data: "Exam_session",
            width: "20%",
          }, {
            data: "Duration",
            width: "20%",
          }, {
            data: "Certificate_No",
            width: "20%",
          }, {
            data: "Docket_Id",
            width: "20%",
            render: function(data, type, row) {
              if (data == null) {
                return "<div class = 'badge badge-danger'>Not Assign</div>";
              } else {
                return data;
              }
            }
          }, {
            data: "Dispatch_status",
            width: "20%",
            render: function(data, type, row) {
              if (data == '1') {
                var edit = '<span class="badge badge-danger cursor-pointer">Not Dispatched</span>';
                return edit;
              } else {
                var edit = '<span class="badge badge-success cursor-pointer">Dispatched</span>';
                return edit;
              }
            }
          }],
          "sDom": "<t><'row'<p i>>",
          "destroy": true,
          "scrollCollapse": true,
          "oLanguage": {
            "sLengthMenu": "_MENU_ ",
            "sInfo": "Showing <b>_START_ to _END_</b> of _TOTAL_ entries"
          },
          "aaSorting": [],
          "iDisplayLength": 10,
          "drawCallback": function(settings) {
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
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer-bottom.php'); ?>