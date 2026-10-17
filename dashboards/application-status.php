<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/header-top.php'); ?>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/header-bottom.php'); ?>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/menu.php'); ?>

<style>
    .status-badge {
        padding: 6px 12px;
        font-size: 14px;
        font-weight: bold;
        border-radius: 20px;
        display: inline-block;
    }

    .status-pending {
        background-color: #ffc107;
        color: #000;
    }

    .payment-pending {
        background-color: #fd7e14;
        color: #fff;
    }

    .payment-paid {
        background-color: #20c997;
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

    .text-warning {
        font-weight: 500;
    }
</style>

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
                            <span class="status-badge status-pending">Pending</span>

                            <div class="mt-4 text-warning">
                                Your application is currently under review. Please check back later.
                            </div>

                            <?php
                            // session_start();
                            $paymentStatus = $_SESSION['Payment_Status'] ?? 'Pending';
                            $payClass = $paymentStatus === 'Paid' ? 'payment-paid' : 'payment-pending';
                            ?>

                            <div class="mt-4">
                                <h4>Payment Status</h4>
                                <span class="status-badge <?= $payClass ?>"><?= $paymentStatus ?></span>
                            </div>

                            <?php if ($paymentStatus === 'Pending'): ?>
                                <!-- Button to Open Modal -->
                                <button type="button" class="btn btn-primary btn-action mt-4" data-toggle="modal" data-target="#paymentModal">
                                    Make Payment
                                </button>
                            <?php else: ?>
                                <a href="/dashboards/center-dashboard.php" class="btn btn-success btn-action mt-4">Go to Dashboard</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Mode Modal -->
            <div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="paymentModalLabel">Select Payment Mode</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body text-center">
                            <a href="/accounts/online-payments" class="btn btn-success btn-lg m-2">Online Payment</a>
                            <a href="/accounts/offline-payments" class="btn btn-secondary btn-lg m-2">Offline Payment</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer-top.php'); ?>
        <?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer-bottom.php'); ?>
    </div>
</div>
