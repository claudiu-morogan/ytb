<?php
$command = escapeshellcmd('python ../python/main.py');
$output = exec($command);
// redirect to list
echo "<script> location.href='http://ytb.code/?action=list'; </script>";

?>

