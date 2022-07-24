<?php
require 'local/config.php';
require 'sources/autoload.php';
$db = new \NineteenQ\Db();

$qCount = $db->query('SELECT COUNT(*) FROM questions')->fetchColumn();
$oCount = $db->query('SELECT COUNT(*) FROM objects')->fetchColumn();
$cCount = $db->query('SELECT COUNT(*) FROM answers')->fetchColumn();
$aCount = $db->query('SELECT COALESCE(SUM(yes+no+skip), 0) FROM answers')->fetchColumn();
$oHits = $db->query('SELECT COALESCE(SUM(hits), 0) FROM objects')->fetchColumn();
$newObjects = $db->query('SELECT COUNT(*) FROM objects WHERE visible=0')->fetchColumn();

// Calculate entropy of objects
// Iterative calculation of entropy (the "with bits" flavor)
// https://fulldecent.blogspot.com/2009/12/interesting-properties-of-entropy.html

$sumFrequencies = 0;
$logBase = 2; // bits
$sumFLogF = 0;
//$productFPowerF = 1;
$entropy = 0; // in bits

$query = $db->query('SELECT hits FROM objects WHERE hits > 0');
$hitCounts = $query->fetchAll(PDO::FETCH_COLUMN);
foreach ($hitCounts as $frequency) {
  //TODO: Don't use frequency, use freq+1, also use precalculated values
  $sumFrequencies += $frequency;
  $sumFLogF += $frequency * log($frequency, $logBase);
  //$productFPowerF *= pow($frequency, $frequency);
}
if ($sumFrequencies > 0) {
  $entropy = log($sumFrequencies, $logBase) - $sumFLogF / $sumFrequencies;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>19 Questions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css" integrity="sha256-8M+b2Hj+vy/2J5tZ9pYDHeuPD59KsaEZn1XXj3xVhjg=" crossorigin="anonymous">
  </head>
  <body class="bg-light lead">
    <div class="container-md my-5">
<?php if (isset($_GET['playagain'])): ?>
      <p class="alert my-5 bg-success text-center">
        <i class="bi bi-emoji-smile"></i> <strong>Thank you for playing!</strong> Please tell everyone you know about me. That's how I learn.
      </p>
<?php endif; ?>      
      <main class="bg-info my-5 p-5 text-center rounded">
        <h1>19 Questions</h1>
        <p class="lead">Think of something a common person would know about, then I ask you questions and guess what it is.</p>
        <p><a class="btn btn-success btn-lg" href="play.php"><i class="bi bi-play"></i> Play</a></p>
      </main>

      <section class="row row-cols-1 row-cols-lg-2">
        <div class="col">
          <h4>Background</h4>
          <p>
            <em>19 Questions</em> is a machine-learning game that finds out about things by playing games with users. The algorithm is based on Bayesian statistics, compared to other similar games use neural networks.
          </p>
          <p>
            See also:
            <a href="http://fulldecent.blogspot.com/2009/12/interesting-properties-of-entropy.html">notes on the the Bayesian/entropy approach</a>
            and
            <a href="https://github.com/fulldecent/19-questions">the GitHub project</a>.
          </p>
        </div>
        <div class="col">
          <h4>Statistics</h4>
          <table class="table">
            <tr><th>Questions in knowledge base:<td><?= number_format($qCount) ?></td></tr>
            <tr><th>Objects in knowledge base:<td><?= number_format($oCount) ?></td></tr>
            <tr><th>Connections in knowledge base:<td><?= number_format($cCount) ?></td></tr>
            <tr><th>Games played:<td><?= number_format($oHits) ?></td></tr>
            <tr><th>New user-submitted objects:<td><?= number_format($newObjects) ?></td></tr>
            <tr><th>Total object entropy:<td><?= number_format($entropy, 1) ?></td></tr>
          </table>
        </div>
      </section>
    </div>

    <footer>
      <div class="container-md text-muted my-5">
        No cookies. No analytics.
      </div>
    </footer>
  </body>
</html>
