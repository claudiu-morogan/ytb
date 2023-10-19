<?php
echo 'incep';
$command = escapeshellcmd('python ../main.py');
$output = exec($command);
var_dump($output);

