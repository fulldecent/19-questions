<?php
require 'local/config.php';
require 'sources/autoload.php';

$nq = new \NineteenQ\Engine(empty($_GET['q']) ? '' : $_GET['q']);
$numQuestions = count($nq->askedQuestions);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta name="robots" content="noindex" />
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>19 Questions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
    <script>var q=<?= json_encode($nq->state) ?>;</script>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
    <link href="//cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.0/inputs-ext/typeaheadjs/lib/typeahead.js-bootstrap.css" rel="stylesheet" media="screen">
    <script src="//cdnjs.cloudflare.com/ajax/libs/typeahead.js/0.10.2/typeahead.bundle.min.js"></script>
  </head>
  <body class="bg-light lead">
    <div class="container-md my-5">
      <main class="bg-info my-5 p-5 text-center rounded">
        <h1>19 Questions <small class="text-muted">you think of something, we guess it</small></h1>

<?php if (isset($_GET['wrapup']) || $numQuestions >= 40): ?>
        <p class="display-2 text-success">You win this round!</p>
        <h3>Were you thinking of one of these:</h3>
        <?php
        foreach ($nq->getTopHunches() as $hunch) {
          $nameHtml = htmlspecialchars($hunch->name);
          if (!empty($hunch->subname)) $nameHtml .= '<small>(' . htmlspecialchars($hunch->subname) . ')</small>';
          echo "<a class=\"btn btn-secondary\" href=\"save.php&#63;obj={$hunch->objectId}&amp;q=".$nq->state."\">{$nameHtml}</a>\n";
        }
        ?>
        <hr>
        <h3>If not, please type the thing here:</h3>
        <form class="form form-inline mx-auto" action="save.php" method="get">
          <input name="q" type="hidden" value="<?= $nq->state ?>">
          <input name="objectname" id="theobjectname" class="form-control">
          <input type="submit" value="Submit" class="btn btn-primary">
        </form>
        <script>
        var bestPictures = new Bloodhound({
          datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
          queryTokenizer: Bloodhound.tokenizers.whitespace,
          remote: 'api/v1/objects.php?q=%QUERY'
        });
        bestPictures.initialize();
        $('#theobjectname').typeahead(null, {
          name: 'best-pictures',
          displayKey: 'name',
          source: bestPictures.ttAdapter(),
          templates: {
            suggestion: function(datum){return datum['name']}
          }
        });
        </script>
      </div>
    </body>
  </html>
<?php exit; ?>
<?php endif; ?>


<?php

  if ($numQuestions >= 19) {
  	echo "<div class=\"alert alert-info\"><strong>You've won this round!</strong> You can continue answering a few more questions or <a href=\"".basename($_SERVER['PHP_SELF'])."&#63;wrapup=yes&amp;q=".$nq->state."\" class=\"btn btn-info\">tell me what you were thinking of</a> so I can learn.</div>\n";
  }

  echo "<p class=\"lead\"><b>#". ($numQuestions+1) ."</b> ";

  list($text, $subtext, $choices) = $nq->getNextQuestion();
  if (strlen($subtext)) $text .= " ($subtext)";
  echo "$text? ";

  foreach ($choices as $choice) {
    if (preg_match('/w([0-9]+)/',$choice[1],$regs))
      $prefix = "save.php&#63;obj={$regs[1]}&amp;q=";
    else
      $prefix = basename($_SERVER['PHP_SELF']).'&#63;'.(isset($_GET['debug'])?'debug&amp;':'')."q=";
    echo "<a class=\"btn btn-outline-primary\" rel=\"nofollow\" href=\"$prefix".$choice[1]."\">".$choice[0]."</a> ";
  }
  echo "</p><hr />";

  foreach (array_reverse($nq->askedQuestions) as $pastQuestion) {
    list($name, $subtext, $answer) = $pastQuestion;
    if (strlen($subtext)) $name .= " ($subtext)";
    echo "<p>".htmlentities($name)." &mdash; $answer</p>";
  }
?>
      </main>

      <section>
        <button id="show-brain-dump" class="btn btn-info">Show Brain Dump</button>

        <div id="brain-dump" class="row row-cols-1 row-cols-md-2 d-none">
          <div class="col">
            <h2>Hunches</h2>
            <table class="table table-condensed">
<?php
    foreach ($nq->getTopHunches() as $hunch) {
      $htmlName = htmlspecialchars($hunch->name);
      if (!empty($hunch->subname)) {
        $htmlName .= ' <small><em>(' . htmlspecialchars($hunch->subname) . ')</em></small>';
      }
      echo '<tr><td>' . $htmlName . '<td>' . number_format($hunch->likelihood*100 / $nq->objectSumLikelihood, 3) . '%';
      echo '<tr><td colspan=2>';
      echo '<div class="progress">';
      echo '<div class="progress-bar bg-info" title="' . number_format($hunch->likelihood*100 / $nq->objectSumLikelihood, 3) . '% likelihood" role="progressbar" style="width: ' . number_format($hunch->likelihood*100 / $nq->objectSumLikelihood, 3) . '%"></div>';
      echo '</div>';
    }
    echo '<tr><td><em>Total entropy:<td>' . number_format($nq->objectEntropy, 2) . ' bits';
?>
            </table>
          </div>
          <div class="col">
            <h2>Top questions</h2>
            <table class="table table-condensed">
<?php
    foreach ($nq->getBestQuestions(10) as $question) {
      list($score, $id, $name, $sub, $yes, $no) = $question;
      if (strlen($sub)) $name .= " <small><em>($sub)</em></small>";
      echo "<tr><td>$name<td>" . number_format($score, 3) . " bits";
      echo "<tr><td colspan=2>";
      echo '<div class="progress">';
      echo '<div class="progress-bar bg-success" title="' . number_format($yes*100, 2) . '% yes" role="progressbar" style="width: ' . number_format($yes*100, 2) . '%"></div>';
      echo '<div class="progress-bar bg-info" title="' . number_format((1-$yes-$no)*100, 2) . '% skip" role="progressbar" style="width: ' . number_format((1-$yes-$no)*100, 2) . '%"></div>';
      echo '<div class="progress-bar bg-danger" title="' . number_format($no*100, 2) . '% no" role="progressbar" style="width: ' . number_format($no*100, 2) . '%"></div>';
      echo '</div>';
    }
?>
            </table>
          </div>
        </div>
      </section>
    </div>

    <footer>
      <div class="container-md text-muted my-5">
        No cookies. No analytics.
      </div>
    </footer>
    <script>
      document.getElementById("show-brain-dump").addEventListener("click", function() {
        document.getElementById("brain-dump").classList.toggle("d-none");
        document.getElementById("show-brain-dump").classList.toggle("d-none");
      });
    </script>
  </body>
</html>
