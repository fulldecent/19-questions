<?php
################################################################################
## GET objects.php?q=an ipad
##
## [
##   {"objectId": 333, "name":"an iPad", "subname":"a toy made by Apple, Inc."},
##   ...
## ]
################################################################################

require '../../local/config.php';
require '../../sources/autoload.php';
header('Content-type: application/json');

$query = empty($_GET['q']) ? '' : $_GET['q'];
if (strlen($query) < 3) {
  echo '[]';
  exit;
}

$database = new \NineteenQ\Db();
$sql = 'SELECT objectid objectId, name, subname FROM objects WHERE name LIKE ? ORDER BY name LIMIT 20';
$statement = $database->prepare($sql);
$statement->execute(['%' . $query . '%']);

echo json_encode($statement->fetchAll(\PDO::FETCH_OBJ));
