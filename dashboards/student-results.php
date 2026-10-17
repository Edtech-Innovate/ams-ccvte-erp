<style>
  .profile_img {
    width: 100px;
    height: 100px;
    object-fit: fill;
    margin: 10px auto;
    border: 5px solid #ccc;
    border-radius: 50%;
  }

  table,
  tr,
  th,
  th {
    border: none !important;
  }

  .profile_table td {
    padding: .5rem !important;
    border: none !important;

  }

  .tile-progress .tile-footer {
    padding: 9px 20px !important;
  }

  .bg-info {
    background-color: #17a2b8 !important
  }

  .table>thead>tr>th {
    border-bottom: 2px solid #f4f4f4;
    font-weight: bold;
    color: black;
  }

  .bg-success {
    background-color: #28a745 !important;
  }

  .bg-primary {
    background-color: #007bff !important;
  }

  h5 {

    line-height: unset;
    margin-bottom: .5rem;
  }

  h4 {
    font-size: 18px !important;
  }

  .card .card-header {
    padding: 5px 16px 5px 16px !important;
  }
  .card.border.border-danger.shadow {
    height: 585px;
}
</style>
<link rel="stylesheet" href="/assets/css/new-style.css" />
<link rel="stylesheet" href="/assets/css/themify-icons/themify-icons.css" />
<div class="row mb-3">
  <div class="col-md-6">
    <div class="card border-primary shadow">
      <div class="card-header bg-primary text-white separator">
        <h5 class="fw-bold"><i class="ti-user mr-2"></i> Profile</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-12">
   
            <h6 class="fw-semibold">Name: <?= $_SESSION['Candidate_Name'] ?></h6>
            <h6 class="fw-semibold">Registration No: <?= $_SESSION['Registration_No'] ?></h6>
            <h6 class="fw-semibold">Course: <?= $_SESSION['Course'] ?></h6>
            
          </div>
       
        </div>
      </div>
    </div>
  </div>

</div>

<script type="text/javascript">
  function changeNotificationStatus(id) {
    $.ajax({
      url: '/app/notifications/current-notification?id=' + id,
      type: 'GET',
      success: function(data) {
        $("#md-modal-content").html(data);
        $("#mdmodal").modal('show');
      }
    })
  }

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