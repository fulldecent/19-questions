<?php
require 'sources/autoload.php';
require 'sources/config.php';

$query = isset($_GET['q']) ? $_GET['q']: ''; #TODO urlencode or anything here?
$NQ = new \NineteenQ\Engine($query);
$numQuestions = $NQ->getNumQuestions();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="robots" content="noindex" />
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>19 Questions</title>
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <script>var q="<?= htmlentities($query) ?>"</script>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
    <link href="//cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.0/inputs-ext/typeaheadjs/lib/typeahead.js-bootstrap.css" rel="stylesheet" media="screen">
    <script src="//cdnjs.cloudflare.com/ajax/libs/typeahead.js/0.10.2/typeahead.bundle.min.js"></script>
    <style>
      body {background: #F1EBEB; margin-top: 2em}
      .well {background:-webkit-linear-gradient(#5CCEEE 0%, #93e1f6 100%); border: none}
    </style>
  </head>
  <body>
    <div class="container">

      <div class="well well-lg">
          <h2>19 Questions <small style="color:#333">you think of something, we guess what it is</small></h2>
        <hr>
<?php
if (isset($_GET['wrapup']) || $numQuestions >= 40)
{
	echo "<div class=\"alert alert-info\"><strong>You've won this round!</strong></div>\n";
  echo "<h3>Were you thinking of one of these:</h3>";
  echo "<ul style=\"list-style:square\">\n";
  foreach ($NQ->getTopHunches(9) as $hunch)
  {
    list($prob, $id, $name, $sub) = $hunch;
    if (strlen($sub)) $name .= " ($sub)";
    echo "<li><a href=\"save.php&#63;obj={$id}&amp;q=".htmlentities($query)."\">{$name}</a></li>\n";
  }
  echo "</ul>\n";
  echo "<h3>If not, please type the thing here:</h3>";
?>
<p/>
<form class="form form-inline" action="save.php" method="get">
<input name="objectname" id="theobjectname" class="form-control">
<input name="q" type="hidden" value="<?= htmlentities($query) ?>">
<input type=submit value="Submit" class="btn btn-default">

<script>
var bestPictures = new Bloodhound({
  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
  queryTokenizer: Bloodhound.tokenizers.whitespace,
  remote: 'jscomplete.php?q=%QUERY'
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

</form>

  </div></body></html>
<?php
    exit;
  }

  if ($numQuestions >= 19)
  {
  	echo "<div class=\"alert alert-info\"><strong>You've won this round!</strong> You can continue answering a few more questions or <a href=\"".basename($_SERVER['PHP_SELF'])."&#63;wrapup=yes&amp;q=".$query."\" class=\"btn btn-info\">tell me what you were thinking of</a> so I can learn.</div>\n";
  }

  echo "<p><b>#". ($numQuestions+1) ."</b> ";

  list($text, $subtext, $choices) = $NQ->getNextQuestion();
  if (strlen($subtext)) $text .= " ($subtext)";
  echo "$text? ";

  foreach ($choices as $choice)
  {
    if (preg_match('/w([0-9]+)/',$choice[1],$regs))
      $prefix = "save.php&#63;obj={$regs[1]}&amp;q=";
    else
      $prefix = basename($_SERVER['PHP_SELF']).'&#63;'.(isset($_GET['debug'])?'debug&amp;':'')."q=";
    echo "<a class=\"btn btn-default\" rel=\"nofollow\" href=\"$prefix".$choice[1]."\">".$choice[0]."</a> ";
  }
  echo "</p><hr />";

  $pastQuestions = $NQ->getPastQuestions();
  foreach (array_reverse($pastQuestions) as $pastQuestion)
  {
    list($name, $subtext, $answer) = $pastQuestion;
    if (strlen($subtext)) $name .= " ($subtext)";
    echo "<p>".htmlentities($name)." &mdash; $answer</p>";
  }
?>
      </div>

<?php
  if (isset($_GET['debug'])) {
?>
      <div class="row">
        <div class="col-md-4">
          <h2>Hunches</h2>
          <table class="table table-condensed">
<?php
    $i=0;
    foreach ($NQ->getTopHunches(20) as $hunch)
    {
      list($prob, $id, $name, $sub) = $hunch;
      if (strlen($sub)) $name .= " <small><em>($sub)</em></small>";
      $prob = sprintf("%0.3f%%",$prob*100);
      echo "<tr><td>".++$i.".<td>$name<td>$prob\n";
    }
    list($entropy, $sumF, $sumFLogF) = $NQ->getEntropy();
    echo "<tr><td><td><em>Total entropy: ".sprintf("%.2f",$entropy)." bits\n";
?>
          </table>
        </div>
        <div class="col-md-4">
          <h2>Top questions</h2>
          <table class="table table-condensed">
<?php
    $i=0;
    foreach ($NQ->getTopQuestions(10) as $question)
    {
      list($score, $id, $name, $sub, $yes, $no) = $question;
      if (strlen($sub)) $name .= " <small><em>($sub)</em></small>";
      echo "<tr><td>".++$i.".<td>$name<td style=\"white-space: nowrap\">YES ".sprintf("%.1f",$yes*100)."%<br>NO ".sprintf("%.1f",$no*100)."% <td>".sprintf("%.3f",$score)." bits";
    }
?>
          </table>
        </div>
        <div class="col-md-4">
          <h2>Profiling</h2>
          <table class="table table-condensed">
<?php
    foreach ($NQ->debug as $line)
    {
      echo "<tr><td>";
      print_r($line);
    }
?>
          </table>
          <p>

<?php
  } else {
?>
        <div><a class="btn btn-info" href="?debug&amp;q=<?= isset($_GET['q'])?htmlentities($_GET['q']):"" ?>">Turn on debug mode</a></div>
<?php
  }
?>
    </div>
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
