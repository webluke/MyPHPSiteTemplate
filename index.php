<?php
require_once('include/common.php');
$tpl = $m->loadTemplate('index');
echo $tpl->render($data);
