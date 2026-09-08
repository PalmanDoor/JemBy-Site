<?php
header('Content-Type: application/json');

$images = array_diff(scandir('img'), array('..', '.'));

echo json_encode($images);
?>