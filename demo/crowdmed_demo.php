<?php

$mtime = microtime(true);
$random = rand();

$pac = md5($mtime) . 'R' . $random;

header('Location: https://www.feedback.infosurv.com/se.ashx?s=4D441EF013BAE172&pac=' . $pac);
?>