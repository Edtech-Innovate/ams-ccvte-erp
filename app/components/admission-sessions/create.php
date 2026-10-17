<?php if(isset($_GET['university_id'])){
  require '../../../includes/db-config.php';
?>
  <!-- Modal -->
  <div class="modal-header clearfix text-left">
    <div class="d-flex justify-content-between align-item-center">
      <h6>Add <span class="semi-bold">Admission Session</span></h6>
      <button aria-label="" type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="pg-icon">close</i>
      </button>
    </div>
  </div>
  <form role="form" id="form-add-admission-sessions" action="/app/components/admission-sessions/store" method="POST">
    <div class="modal-body">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group form-group-default required">
            <label>Admission Session Month</label>
            <select class="full-width" style="border: transparent;" name="month" id="month">
              <option value="">Choose</option>
            </select>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group form-group-default required">
            <label>Asmission Session Year</label>
            <select class="full-width" style="border: transparent;" name="year" id="year">
              <option value="">Choose</option>
            </select>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group form-group-default required">
            <label>Scheme</label>
            <select class="full-width" style="border: transparent;" name="scheme" id="scheme">
              <option value="">Choose</option>
              <?php
                $schemes = $conn->query("SELECT ID, Schemes.Name FROM Schemes WHERE Schemes.Status = 1 AND University_ID = ".$_GET['university_id']."");
                while($scheme = $schemes->fetch_assoc()) { ?>
                  <option value="<?=$scheme['ID']?>"><?=$scheme['Name']?></option>
              <?php } ?>
            </select>
          </div>
        </div>
      </div>
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

  <script>

    $(document).ready(function(){
      getMonth();getYear();
    });

    $("#month,#year,#scheme,#exam_session").select2({
      placeholder: 'Choose'
    })

    function getMonth() {
      let months = {1:"Jan",2:"Feb",3:"Mar",4:"Apr",5:"May",6:"Jun",7:"Jul",8:"Aug",9:"Sep",10:"Oct",11:"Nov",12:"Dec"};
      let monthSelect = document.getElementById("month");
      for (const key in months) {
        let options = document.createElement("option");
        options.value = months[key];
        options.text = months[key];
        monthSelect.appendChild(options);
      } 
    }

    function getYear() {
      let selectyear = document.getElementById("year");
      for(let i = 0; i <= 50 ; i++) {
        let options = document.createElement("option");
        options.value = i;
        options.text = 2000+i;
        selectyear.appendChild(options); 
      }
    }

   

    $("#form-add-admission-sessions").on("submit", function(e){
      if($('#form-add-admission-sessions').valid()){
        $(':input[type="submit"]').prop('disabled', true);
        var formData = new FormData(this);
        formData.append('university_id', '<?=$_GET['university_id']?>');
        $.ajax({
          url: this.action,
          type: 'post',
          data: formData,
          cache:false,
          contentType: false,
          processData: false,
          dataType: "json",
          success: function(data) {
              console.log(data);
            if(data.status==200){
              $('.modal').modal('hide');
              notification('success', data.message);
              $('#tableAdmissionSessions').DataTable().ajax.reload(null, false);
            }else{
              $(':input[type="submit"]').prop('disabled', false);
              notification('danger', data.message);
            }
          }
        });
        e.preventDefault();
      }
    });
  </script>
<?php } ?>
