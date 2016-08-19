<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>19 Questions</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.3/css/bootstrap.min.css" integrity="sha384-MIwDKRSSImVFAZCVLtU0LMDdON6KVCrZHyVQQj6e8wIEJkW4tvwqXrbMIya1vriY" crossorigin="anonymous">
    <link rel="stylesheet" href="common.css">
    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    <div class="container">
      <div class="jumbotron">
        <h1>19 Questions</h1>
        <p class="lead">Just think of something a common person would know about, then I ask you questions and try to guess what it is.</p>
        <p><a class="btn btn-success btn-lg" href="play.php"><i class="glyphicon glyphicon-play"></i> Play now</a></p>
      </div>

      <div class="row marketing">
        <div class="col-lg-6">
          <h4>Background</h4>
          <p>
            <em>19 Questions</em> is a machine-learning game that finds out about things by playing games with users. The algorithm is based on Bayesian statistics, compared to other similar games which are based neural networks.
          </p>
          <p>
            See also:
            <a href="http://fulldecent.blogspot.com/2009/12/interesting-properties-of-entropy.html">notes on the the Bayesian/entropy approach</a>
            and
            <a href="https://github.com/fulldecent/19-questions">the GitHub project</a>.
          </p>
        </div>

        <div class="col-lg-6">
          <h4>Statistics</h4>
            <table class="table">
<?php
require 'sources/autoload.php';
require 'sources/config.php';

$db = new \NineteenQ\Db("mysql:host=127.0.0.1;port=8889;dbname=19q", "root", "aoeusnth");

$query = $db->select('questions', '', '', 'COUNT(*)');
$qcount = array_pop($query[0]);

$query = $db->select('objects', '', '', 'COALESCE(SUM(hits), 0)');
$ohits = array_pop($query[0]);

$query = $db->select('objects', '', '', 'COUNT(*)');
$ocount = array_pop($query[0]);

$query = $db->select('answers', '', '', 'COALESCE(SUM(yes+no+skip), 0)');
$acount = array_pop($query[0]);

$query = $db->select('answers', '', '', 'COUNT(*)');
$ccount = array_pop($query[0]);

$query = $db->select('objects', 'hidden=1', '', 'COUNT(*)');
$newobjs = array_pop($query[0]);

#var_dump($qcount, $ohits, $ocount, $acount, $ccount, $newobjs);
#die();

echo "<tr><td>Questions in knowledge base:<td>".number_format($qcount)."\n";
echo "<tr><td>Objects in knowledge base:<td>".number_format($ocount)."\n";
echo "<tr><td>Connections in knowledge base:<td>".number_format($ccount)."\n";
echo "<tr><td>Games played:<td>".number_format($ohits)."\n";
echo "<tr><td>New user-submitted objects:<td>".number_format($newobjs)."\n";
echo "<!--<tr><td>Total questions asked<td>$acount-->\n";

###### entropy given
$sum = 0;
$sumflogf = 0;
$prodpowff = 1;
$entropy = 0;

$query = $db->select('objects', '', '', 'hits');
foreach ($query as $row) {
  list($hits) = $row;
  $sum += $hits;
  $sumflogf += $hits*log($hits,2);
  $prodpowff *= pow($hits,$hits);
}

if ($sum > 0) {
  $entropy = log($sum,2) - $sumflogf/$sum;
}
## there's probably a cool way to calculate from $prodpowff too!

echo "<tr><td>Total object entropy:<td>".round($entropy,2)." bits\n";

######### set baseline for hits given ()
# this is like a temporary table, saved in PHP
#$hitsbase = array();
#$dh = mysql_query('SELECT id,hits FROM objects');
#while (list($id,$hits) = mysql_fetch_array($dh))
#{#
#  $hitsbase[$id] = $hits;
#}

?>
          </table>
        </div>
      </div>
    </div> <!-- /container -->
    <script>
      (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
      (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
      m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
      })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

      ga('create', 'UA-52764-3', 'phor.net');
      ga('send', 'pageview');
    </script>
  </body>
</html>
