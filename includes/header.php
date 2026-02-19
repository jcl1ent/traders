<?php
// ensure configuration (BASE_URL etc.) is available
if (!defined('BASE_URL')) {
    include_once __DIR__ . '/../config.php';
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <base href="<?= BASE_URL ?>">
    <title>
        <?php if(isset($page_title)) {echo"$page_title";} ?>

    </title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  </head>
  <body></body>