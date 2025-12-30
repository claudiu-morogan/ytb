<?php
$response = @file_get_contents('http://ytb_python:353/trigger-downloads');

if ($response === false) {
    error_log("Failed to connect to Python backend");
    echo '<div class="alert alert-danger">Failed to connect to download service. Please try again later.</div>';
    exit;
}

if ($response == '"success"') {
    echo "<script> location.href='?action=list'; </script>";
} else {
    error_log("Download failed with response: " . $response);
    echo '<div class="alert alert-danger">Download failed. Please check the logs or try again.</div>';
}

?>

