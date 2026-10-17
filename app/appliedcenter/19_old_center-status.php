<?php
require '../../includes/db-config.php';

if (isset($_GET['id'])) {
  session_start();

  $id = intval($_GET['id']);

  // ✅ Directly query using mysqli_query instead of prepared statement
  $query = "SELECT form_type FROM Users WHERE ID = $id AND Role = 'Center'";
  $result = mysqli_query($conn, $query);

  $currentStatus = 0;
  if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $currentStatus = intval($row['form_type']);
  }

  $statuses = [
    1 => 'Pending',
    2 => 'Approved',
    3 => 'Rejected'
  ];
?>

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

  <script>
    $('#statusForm').on('submit', function(e) {
      e.preventDefault();

      $('#loader').show(); // Show loader
      $('button[type="submit"]').prop('disabled', true); // Disable submit button

      $.ajax({
        url: '/app/appliedcenter/status',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
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
        error: function() {
          $('#loader').hide(); // Hide loader
          $('button[type="submit"]').prop('disabled', false); // Enable button

          notification('danger', 'API request failed.');
        }
      });
    });
  </script>

<?php } ?>