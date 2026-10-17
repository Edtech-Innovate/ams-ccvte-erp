<?php
include($_SERVER['DOCUMENT_ROOT'] . '/includes/header-top.php');
?>

<style>
    .status-badge {
        padding: 6px 12px;
        font-size: 14px;
        font-weight: bold;
        border-radius: 20px;
        display: inline-block;
    }

    .status-rejected {
        background-color: #dc3545;
        color: #fff;
    }

    .card-status-box {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        padding: 30px;
        text-align: center;
    }

    .card-status-box h4 {
        margin-bottom: 15px;
        font-weight: 600;
    }

    .btn-action {
        margin-top: 20px;
        padding: 10px 20px;
        border-radius: 25px;
    }

    .text-danger {
        font-weight: 500;
    }
</style>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/header-bottom.php'); ?>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/menu.php'); ?>

<div class="page-container">
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/topbar.php'); ?>

    <div class="page-content-wrapper">
        <div class="content">
            <div class="jumbotron" data-pages="parallax">
                <div class="container-fluid sm-p-l-0 sm-p-r-0">
                    <div class="inner">
                        <ol class="breadcrumb d-flex flex-wrap justify-content-between align-self-start">
                            <li class="breadcrumb-item active">Application Status</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card-status-box">
                            <h4>Application Status</h4>
                            <span class="status-badge status-rejected">Rejected</span>

                            <div class="mt-4 text-danger">
                                Your application has been rejected by the administration.<br>
                                Please contact support or your center coordinator for further assistance.
                            </div>

                            <a href="/contact-support.php" class="btn btn-danger btn-action mt-4">Contact Support</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer-top.php'); ?>
        <?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer-bottom.php'); ?>
    </div>
</div>
