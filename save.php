<?php
require 'local/config.php';
require 'sources/autoload.php';

if (!isset($_GET)) die('saving error with factset');
$NQ = new \NineteenQ\Engine(empty($_GET['q']) ? '' : $_GET['q']);

$objectID = 0;
if (isset($_GET['obj'])) {
  $objectID = intval($_GET['obj']);
} else if (isset($_GET['objectname'])) {
  $objectID = $NQ->getObjectByName($_GET['objectname']);
} else {
  die('Invalid object');
}

list($objectName, $objectSubName) = $NQ->getObject($objectID);
if (empty($objectName)) die('Object database error');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="robots" content="noindex" />
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>19 Questions</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css" integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/common.css">
    <script>var q="<?= htmlentities($query) ?>"</script>
    <style>
      body {background: #F1EBEB; margin-top: 2em}
    </style>
  </head>
  <body>
    <div class="container">
      <h2>19 Questions <small class="text-secondary">you think of something, we guess it</small></h2>
      <div class="jumbotron">
<?php
if (isset ($_POST['action']) && $_POST['action'] == 'save') {
  $NQ->teach($objectID);
  echo "<p>Thanks for playing. <a href=\"play.php\" class=\"btn btn-lg btn-primary\">Play again</a> <a href=\"index.php\" class=\"btn btn-lg btn-secondary\">Go to the 19Q homepage</a>";
  echo "<hr>";
  echo '<p>Please tell everyone you know about this project. That is how I learn.';
  echo "<p><a href=\"http://www.facebook.com/sharer.php?u=http%3A%2F%2Fgoo.gl%2F3XhDR&t=19 Questions Game\" class=\"btn btn-lg btn-success\">Share on Facebook</a>
          <a href=\"http://twitter.com/intent/tweet?text=19 Questions Game&url=http%3A%2F%2Fgoo.gl%2F3XhDR\" class=\"btn btn-lg btn-success\">Share on Twitter</a>
          <a href=\"https://plusone.google.com/_/+1/confirm?hl=en&url=http%3A%2F%2Fgoo.gl%2F3XhDR\" class=\"btn btn-lg btn-success\">Share on Google Plus</a></p>";
  echo '<p>See GitHub project. https://github.com/fulldecent/19-questions';
} else {
?>
        <form method="post">
          <input name="obj" value="<?= htmlentities($_GET['obj']) ?>" type="hidden">
          <input name="objectname" value="<?= htmlentities($_GET['objectname']) ?>" type="hidden">
          <input name="q" value="<?= htmlentities($_GET['q']) ?>" type="hidden">
          <input name="action" value="save" type="hidden">
          <p class="lead">You are about to teach <b>19 Questions</b> this information about <b><?= htmlspecialchars($objectName) ?></b>.</p>
          <input class="btn btn-lg btn-primary" type="submit" value="Teach 19 Questions"></p>
        </form>
        <hr>
<?php
  $pastQuestions = $NQ->askedQuestions;
  foreach (array_reverse($pastQuestions) as $pastQuestion)
  {
    list($name, $subtext, $answer) = $pastQuestion;
    if ($answer == 'wrong') continue;
    if (strlen($subtext)) $name .= " ($subtext)";
    echo "<p>".htmlentities($name)." &mdash; $answer</p>";
  }
}
?>
      </div>
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
