<?php
require 'local/config.php';
require 'sources/autoload.php';

if (!isset($_GET)) die('saving error with factset');
$nq = new \NineteenQ\Engine(empty($_GET['q']) ? '' : $_GET['q']);

$objectID = 0;
if (isset($_GET['obj'])) {
  $objectID = intval($_GET['obj']);
} else if (isset($_GET['objectname'])) {
  $objectID = $nq->getObjectByName($_GET['objectname']);
} else {
  die('Invalid object');
}
list($objectName, $objectSubName) = $nq->getObject($objectID);
if (empty($objectName)) die('Object database error');
//$nq->teach($objectID);
header('Location: index.php?playagain=1');

//TODO: use POST to access this page instead of GET