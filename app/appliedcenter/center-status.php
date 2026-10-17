<?php
require '../../includes/db-config.php';



if (isset($_GET['id'])) {
  session_start();

  $id = intval($_GET['id']);
//   $apiUrl = "http://latestccvte.local/admin/app/center-online-partner/get-data.php";
  $apiUrl = (BASE_URL . "admin/app/center-online-partner/get-data.php");


  // Fetch data
  $response = file_get_contents($apiUrl);
  $responseData = json_decode($response, true);

  $currentStatus = 0;
  if (!empty($responseData['data'])) {
    foreach ($responseData['data'] as $item) {
      if (isset($item['ID']) && intval($item['ID']) === $id) {
        $currentStatus = intval($item['Status']);
        break;
      }
    }
  }

  $statuses = [
    0 => 'Pending',
    1 => 'Approved',
    2 => 'Rejected'
  ];
?>

<!-- <style>
  select option:disabled {
    color: #ccc !important;
    background-color: #f8f9fa;
  }
</style> -->

<div class="modal-header bg-light border-bottom-0">
  <h5 class="modal-title text-primary">
    <i class="uil uil-check-circle mr-1"></i> Manage Application Status
  </h5>
  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
    <span aria-hidden="true">&times;</span>
  </button>
</div>

<div class="modal-body">
  <form id="statusForm" class="p-2">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="form-group mb-4">
      <label for="status" class="font-weight-semibold text-dark">Select Application Status</label>
      <select name="status" id="status" class="form-control custom-select shadow-sm">
        <?php foreach ($statuses as $key => $label): ?>
          <option value="<?= $key ?>" <?= ($currentStatus === $key ? 'selected' : '') ?>>
            <?= $label ?>
          </option>
        <?php endforeach; ?>
      </select>
      <!-- <select name="status" id="status" class="form-control custom-select shadow-sm">
  <?php
    foreach ($statuses as $key => $label):
      // Disable logic based on current status
      $disabled = false;

      // If approved, disable all except approved
      if ($currentStatus === 1 && $key !== 1) $disabled = true;

      // If rejected, disable all except rejected
      if ($currentStatus === 2 && $key !== 2) $disabled = true;

      // If pending, allow all (no disable)

      echo '<option value="' . $key . '" ' .
            ($currentStatus === $key ? 'selected' : '') . 
            ($disabled ? ' disabled' : '') .
           '>' . $label . '</option>';
    endforeach;
  ?>
</select> -->

    </div>

    <div class="text-right">
      <button type="submit" class="btn btn-sm btn-primary">
        <i class="uil uil-save mr-1"></i> Update Status
      </button>
    </div>
  </form>
  <div id="loader" style="display: none; text-align: center;">
  <div class="spinner-border text-primary" role="status">
    <span class="sr-only">Loading...</span>
  </div>
</div>

</div>


<!-- <script>
  $('#statusForm').on('submit', function (e) {
    e.preventDefault();

    $.ajax({
      url: 'http://latestccvte.local/admin/app/center-online-partner/update-status.php',
      type: 'POST',
      data: $(this).serialize(),
      dataType: 'json',
      success: function (response) {
        if (response.status === 200) {
          notification('success', response.message);
          $('#lgmodal').modal('hide');
          $('#users-table').DataTable().ajax.reload(null, false);
        } else {
          notification('danger', response.message || 'Update failed.');
        }
      },
      error: function () {
        notification('danger', 'API request failed.');
      }
    });
  });
</script> -->
<script>
  const BASE_URL = "<?= BASE_URL ?>";
</script>


<script>
  $('#statusForm').on('submit', function (e) {
    e.preventDefault();

    $('#loader').show(); // Show loader
    $('button[type="submit"]').prop('disabled', true); // Disable submit button

    $.ajax({
    //   url: 'http://latestccvte.local/admin/app/center-online-partner/update-status.php',
      url: BASE_URL + "admin/app/center-online-partner/update-status",
      type: 'POST',
      data: $(this).serialize(),
      dataType: 'json',
      success: function (response) {
        $('#loader').hide(); // Hide loader
        $('button[type="submit"]').prop('disabled', false); // Enable button

        if (response.status === 200) {
          notification('success', response.message);
          $('#lgmodal').modal('hide');
          $('#users-table').DataTable().ajax.reload(null, false);
        } else {
          notification('danger', response.message || 'Update failed.');
        }
      },
      error: function () {
        $('#loader').hide(); // Hide loader
        $('button[type="submit"]').prop('disabled', false); // Enable button

        notification('danger', 'API request failed.');
      }
    });
  });
</script>

<?php } ?>
