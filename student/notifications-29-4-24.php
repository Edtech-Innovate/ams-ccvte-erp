<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/header-top.php'); ?>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/header-bottom.php'); ?>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/menu.php'); ?>
<!-- START PAGE-CONTAINER -->
<div class="page-container ">
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
              <?php $breadcrumbs = array_filter(explode("/", $_SERVER['REQUEST_URI']));
              for ($i = 1; $i <= count($breadcrumbs); $i++) {
                if (count($breadcrumbs) == $i) : $active = "active";
                  $crumb = explode("?", $breadcrumbs[$i]);
                  echo '<li class="breadcrumb-item ' . $active . '">' . $crumb[0] . '</li>';
                endif;
              }
              ?>
              <div>

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
        <div class="row">
          <div class="col-md-6">
            <div class="card">
              <div class="card-header seperator d-flex justify-content-between">
                <?php 
                  $current_notification_id = 0;
                  $latest_notification = $conn->query("SELECT * FROM Notifications_Generated WHERE Send_To = 'student'  OR Send_To = '".'all'."' ORDER BY Notifications_Generated.ID DESC LIMIT 1");
                  
                  while ($row = $latest_notification->fetch_assoc()) {
                    $current_notification_id = $row['ID'];
                    ?>
                   
                  <span>Regarding : <?= $row['Heading'] ?></span>
                  <span class="me-auto">Date : <?= $row['Noticefication_Created_on'] ?></span>
                  </div>
                  <div class="card-body">
                    <p><?= $row['Content'] ?></p>
                    <a href="<?=$row['Attachment']?>" target="_blank" download="<?= $row['Heading'] ?>">Download</a>
                  </div>
                <?php } ?>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card custom-card">
                <div class="card-body dash1">
                    <div class="d-flex">
                        <p class="mb-1 tx-inverse">Notifications</p>
                        <div class="ml-auto">
                            <i class="fas fa-chart-line fs-20 text-primary"></i>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>Regarding</th>
                                <th>Content</th>
                                <th>Sent To</th>
                                <th>Noticefication Sent On</th>
                                <th>Attachment</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php 
                            $result_record = $conn->query("SELECT * FROM Notifications_Generated WHERE Send_To = '".'student'."' OR Send_To = '".'all'."' ");
                            $data = array();
                            while ($row = $result_record->fetch_assoc()) { ?>
                                <tr>
                                <td><?=$row['Heading']?></td>
                                <td><a type="btn" onclick="view_content('<?=$row['ID']?>');"><i class="uil uil-eye"></i></a></td>
                                <td><?=$row['Send_To']?></td>
                                <td><?=$row['Noticefication_Created_on']?></td>
                                <td>
                                  <a href="<?=$row['Attachment']?>" target="_blank" download="<?= $row['Heading'] ?>">Download</a>
                                </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>   
          </div>
        </div>
        <!-- END PLACE PAGE CONTENT HERE -->
      </div>
      <!-- END CONTAINER FLUID -->
    </div>
    <!-- END PAGE CONTENT -->
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer-top.php'); ?>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer-bottom.php'); ?>
<script type="text/javascript">
  $(document).ready(function() 
  {
    if(<?=$current_notification_id?> != 0){
      $.ajax({
        url: '/app/notifications/student-read-notification?id=' + <?=$current_notification_id?>,
        type: 'GET',
        success: function(data) {
        }
      })
    }
  });

    function view_content(id) {
      $.ajax({
        url: '/app/notifications/contents?id=' + id,
        type: 'GET',
        success: function(data) {
          $("#md-modal-content").html(data);
          $("#mdmodal").modal('show');
        }
      })
    }
</script>