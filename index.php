<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>19 Questions</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css" integrity="sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/common.css">
  </head>
  <body>
    <div class="container">
      <div class="jumbotron">
        <h1>19 Questions</h1>
        <p class="lead">Just think of something a common person would know about, then I ask you questions and guess what it is.</p>
        <p><a class="btn btn-success btn-lg" href="play.php"><i class="glyphicon glyphicon-play"></i> Play Now</a></p>
      </div>

      <div class="row marketing">
        <div class="col-lg">
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

        <div class="col-lg">
          <h4>Statistics</h4>
            <table class="table">
<?php
require 'local/config.php';
require 'sources/autoload.php';
$db = new \NineteenQ\Db();

$qCount = $db->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$oCount = $db->query("SELECT COUNT(*) FROM objects")->fetchColumn();
$cCount = $db->query("SELECT COUNT(*) FROM answers")->fetchColumn();
$aCount = $db->query("SELECT COALESCE(SUM(yes+no+skip), 0) FROM answers")->fetchColumn();
$oHits = $db->query("SELECT COALESCE(SUM(hits), 0) FROM objects")->fetchColumn();
$newObjects = $db->query("SELECT COUNT(*) FROM objects WHERE visible=0")->fetchColumn();

echo "<tr><td>Questions in knowledge base:<td>".number_format($qCount)."\n";
echo "<tr><td>Objects in knowledge base:<td>".number_format($oCount)."\n";
echo "<tr><td>Connections in knowledge base:<td>".number_format($cCount)."\n";
echo "<tr><td>Games played:<td>".number_format($oHits)."\n";
echo "<tr><td>New user-submitted objects:<td>".number_format($newObjects)."\n";
echo "<!--<tr><td>Total questions asked<td>$aCount-->\n";

## Calculate entropy of objects
## Iterative calculation of entropy (the "with bits" flavor)
## https://fulldecent.blogspot.com/2009/12/interesting-properties-of-entropy.html

$sumFrequencies = 0;
$logBase = 2; // bits
$sumFLogF = 0;
//$productFPowerF = 1;
$entropy = 0; // in bits

$query = $db->query("SELECT hits FROM objects WHERE hits > 0");
$hitCounts = $query->fetchAll(PDO::FETCH_COLUMN);
foreach ($hitCounts as $frequency) {
//TODO: Don't use frequency, use freq+1, also use precalculated values  
  $sumFrequencies += $frequency;
  $sumFLogF += $frequency * log($frequency, $logBase);
  //$productFPowerF *= pow($frequency, $frequency);
}

if ($sumFrequencies > 0) {
  $entropy = log($sumFrequencies, $logBase) - $sumFLogF / $sumFrequencies;
  //$entropy = log($sumFrequencies * pow($productFPowerF, -1/$sumFrequencies), $logBase);
}

echo "<tr><td>Total object entropy:<td>".round($entropy,2)." bits\n";
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
