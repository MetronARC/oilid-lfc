<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>OILid</title>
    <!-- Load Bootstrap first -->
    <link
        rel="stylesheet"
        href="<?= base_url('css/bootstrap.min.css') ?>" />
    <!-- Load icons -->
    <!-- Load custom styles last -->
    <link rel="stylesheet" href="<?= base_url('css/login-style.css') ?>" />
    <link href="<?= base_url('img/favicon.png') ?>" rel="icon" />
    <link
        rel="stylesheet"
        href="https://unicons.iconscout.com/release/v2.1.9/css/unicons.css" />

    <!-- Add SweetAlert2 -->
    <script src="<?= base_url('js/sweetalert2@11.js') ?>"></script>
</head>

<body>
    <?= $this->renderSection('page-content') ?>
</body>

</html>