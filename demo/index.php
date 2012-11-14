<?php

$mtime = microtime(true);
$random = rand();

$pac = md5($mtime) . 'R' . $random;

header('Location: https://www.feedback.infosurv.com/se.ashx?s=4D441EF019D21E68&pac=' . $pac);
?>