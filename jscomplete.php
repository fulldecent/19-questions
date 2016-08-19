<?php
require 'sources/autoload.php';
require 'sources/config.php';

header('Content-type: application/javascript');

if (strlen($_GET['q']) < 3) exit;

$dh = mysql_query('SELECT id,name,sub FROM objects WHERE name LIKE "%'.mysql_real_escape_string($_GET['q']).'%" ORDER BY name LIMIT 20')
  or die(mysql_error());

echo htmlentities($_GET['cb']).'[';

$i=0;
while (list($id, $name, $sub) = mysql_fetch_array($dh))
{
  if ($sub) $sub = " ($sub)";
  if ($i++) echo ',';
  echo "{\"id\":\"$id\",\"name\":\"$name$sub\"}";
}

echo ']';
?>
