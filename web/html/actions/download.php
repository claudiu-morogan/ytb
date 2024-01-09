<?php
// $command = escapeshellcmd('python ../python/main.py');
// $output = exec($command);

$response = file_get_contents('http://ytb_python:353/trigger-downloads');
echo $response;
if ($response == '"success"')
// redirect to list
    echo "<script> location.href='http://ytb.code/?action=list'; </script>";
else
    echo 'EROARE';

?>

