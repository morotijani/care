<?php    
    require ('system/DatabaseConnector.php');
    $title = '404 - Levina - Namibra';
    $body_class = "";
    $playSound = false;
    require ('system/inc/head.php');
?>

    <!-- Page content -->
    <main class="page-wrapper">
        <div class="d-flex flex-column flex-lg-row justify-content-between min-vh-100 position-relative">
            <div class="position-absolute top-0 start-0 w-100 h-100 d-lg-none">
                <div class="d-dark-mode-none position-absolute top-0 start-0 w-100 h-100" style="background-color: #f2f3f7;"></div>
            </div>
            <div class="d-flex flex-column justify-content-center w-lg-50 position-relative z-2 mt-auto mt-lg-0">
            <div class="text-center text-lg-start pt-5 pb-3 py-lg-5 px-3 px-sm-4 px-lg-5 mx-auto" style="max-width: 630px;">
                <h1 class="display-1">Error 404</h1>
                <p class="lead pb-2 mb-4 mb-lg-5">The page you are looking for was moved, removed or might never existed.</p>
                <a class="btn btn-lg btn-primary" href="<?= PROOT; ?>app/index">Go to homepage</a>
            </div>
        </div>
        <div class="d-flex flex-column position-relative justify-content-end align-items-center w-lg-50 overflow-hidden">
            <div class="position-absolute top-0 start-0 w-100 h-100 d-none d-lg-block">
                <div class="d-dark-mode-none position-absolute top-0 start-0 w-100 h-100" style="background-color: #f2f3f7;"></div>
                <div class="d-none d-dark-mode-block position-absolute top-0 start-0 w-100 h-100" style="background-color: rgba(255,255,255, .04);"></div>
            </div>
            <div class="position-relative z-2" style="max-width: 948px;">
                <img src="<?= PROOT; ?>assets/media/layer01.png" alt="Lady" id="lady">
                <img class="position-absolute top-0 start-0 w-100 h-100" src="<?= PROOT; ?>assets/media/layer02-light.png" alt="Bubble" id="bubble">
                <div class="position-absolute top-0 start-0 w-100 h-100" id="question">
                    <img class="d-dark-mode-none" src="<?= PROOT; ?>assets/media/layer03-light.png" alt="Question mark">
                    <img class="d-none d-dark-mode-block" src="<?= PROOT; ?>assets/media/layer03-dark.png" alt="Question mark">
                </div>
            </div>
        </div>
    </div>
<?php require('system/inc/footer.php'); ?>
