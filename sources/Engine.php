<?php
namespace NineteenQ;

# WARNING: these functions (except the creator) can be used for SQL injection
class Engine
{
  var $database;

  var $state;               // a token to describe this state
  var $yesQ, $noQ, $skipQ, $guesses;  // questionsids describing my state
  var $pastQuestions;       // has arrays of <question, subtext, answer (one of yes|no|skip)>
  var $maxFreq;             // frequency of top object
  var $debug;               // an array, each element is a debug event

  // State is a string like: y23n359s293y3y28
  // meaning: the user replied YES to question #23, and replied NO to ...
  function __construct($state = '')
  {
    $this->database = new \NineteenQ\Db("mysql:host=127.0.0.1;port=8889;dbname=19q", "root", "aoeusnth");
    $this->state = $state;

    ##
    ## Parse the STATE string
    ##
    $this->yesQ = $this->noQ = $this->skipQ = array();
    $this->pastQuestions = array();
    preg_match_all('/([ynsg])(\d+)/', $state, $regs, PREG_SET_ORDER);
    foreach ($regs as $reg) {
      if ($reg[1]=='y') {
        $dh = $this->query('SELECT name, sub FROM questions WHERE id='.$reg[2]);
        list($name, $sub) = mysql_fetch_array($dh);
        $this->yesQ[] = $reg[2];
        $this->pastQuestions[] = array($name, $sub, 'yes', $reg[2]);
      } elseif ($reg[1]=='n') {
        $dh = $this->query('SELECT name, sub FROM questions WHERE id='.$reg[2]);
        list($name, $sub) = mysql_fetch_array($dh);
        $this->noQ[] = $reg[2];
        $this->pastQuestions[] = array($name, $sub, 'no', $reg[2]);
      } elseif ($reg[1]=='s') {
        $dh = $this->query('SELECT name, sub FROM questions WHERE id='.$reg[2]);
        list($name, $sub) = mysql_fetch_array($dh);
        $this->skipQ[] = $reg[2];
        $this->pastQuestions[] = array($name, $sub, 'skip', $reg[2]);
      } elseif ($reg[1]=='g') {
        $this->guesses[] = $reg[2];
        $dh = $this->query('SELECT name, sub FROM objects WHERE id='.$reg[2]);
        list($name, $sub) = mysql_fetch_array($dh);
        $this->pastQuestions[] = array('I am guessing that it is '.$name, $sub, 'wrong', $reg[2]);
      }
    }

    ## MAXIMUM LIKELIHOOD ESTIMATION crash course:
    ## 7 people told us "cats are big", 3 said they aren't. What are the odds
    ## that the next person says a cat is big? (7+1)/(7+1+3+1)

    ##
    ## Create EVIDENCE temporary table
    ## This maps each object to a log posterior probability*3 using the users'
    ## PASTQUESTIONS as evidence, when the ANSWERS table maps those questions to an object
    ##
    $sources = array();
    if (count($this->yesQ))
      $sources[] = "SELECT objectid, log(2, 3*(yes+1)/(yes+no+skip+3)) as lp3 FROM answers WHERE questionid IN (".join($this->yesQ,",").")";
    if (count($this->noQ))
      $sources[] = "SELECT objectid, log(2, 3*(no+1)/(yes+no+skip+3)) as lp3 FROM answers WHERE questionid IN (".join($this->noQ,",").")";
    if (count($this->skipQ))
      $sources[] = "SELECT objectid, log(2, 3*(skip+1)/(yes+no+skip+3)) as lp3 FROM answers WHERE questionid IN (".join($this->skipQ,",").")";
    if (!count($sources))
      $sources[] = 'SELECT NULL as objectid, 0 as lp3';

    $sql = "CREATE TEMPORARY TABLE evidence (id SMALLINT, lp3 FLOAT, PRIMARY KEY(id))
            SELECT objectid as id, SUM(lp3) AS lp3
              FROM (".join($sources, " UNION ALL ").") xx GROUP BY objectid";
    $dh = $this->query($sql);

    ##
    ## Create STATE temporary table
    ## This MAPS all objects to a posterior probability and caches some expressions
    ##
    $sql = "CREATE TEMPORARY TABLE
                   state (id SMALLINT, freq FLOAT, flogf FLOAT, logf FLOAT, PRIMARY KEY(id)) ENGINE=MEMORY
            SELECT id,
                   hits * COALESCE(POW(2,lp3), 1) AS freq,
                   hits * COALESCE(POW(2,lp3), 1) * (log(2, hits) + COALESCE(lp3, 0)) AS flogf,
                   log(2, hits) + COALESCE(lp3, 0) AS logf
            FROM objects LEFT JOIN evidence USING(id) WHERE hidden=0";
    $this->query($sql);

    if (count($this->guesses)) {
      $sql = "DELETE FROM state WHERE id IN (".join($this->guesses,',').")";
      $this->query($sql);
    }

## TODO FINE TUNE THIS FACTOR
    ## DO APPROXIMATION
    $factor = 40;
    $dh = $this->query('SELECT MAX(freq) FROM state');
    list($this->maxFreq) = mysql_fetch_array($dh);
    $this->query("DELETE FROM state WHERE freq < {$this->maxFreq} / $factor");
  }

  function query($sql, $buffered=true)
  {
    $bt = debug_backtrace();
    if (isset($bt[1]))
      $func = $bt[1]['function'];
    else
      $func = "TOP LEVEL";

    $startTime = microtime(1);
    if ($buffered)
      $result = mysql_query($sql, $this->database);
    else
      $result = mysql_unbuffered_query($sql, $this->database);

    if(!$result)
      die("DATABASE ERROR<br>in $func<br>".mysql_error()."<br>$sql");

    $this->debug[] = sprintf("<em>$func</em> query took %0.3fs:<br>",microtime(1)-$startTime).$sql;
    return $result;
  }

  function getNumQuestions()
  {
    return count($this->pastQuestions);
  }

  function getPastQuestions()
  {
    return $this->pastQuestions;
  }

  // ENTROPY crash course:
  // You have a apples, b bananas, and c carrots. Each of those is a frequency.
  // sum f = a+b+c; sum f log f = a log a + b log b + c log c
  // Entropy of your produce is log sumf - sumflogf / sumf

  // Returns the entropy of the current state:
  //   array: entropy in bits, sumf, sumflogf
  function getEntropy()
  {
    if (isset($this->_entropy)) return $this->_entropy;
    $dh = $this->query('SELECT log(2,SUM(freq))-SUM(flogf)/SUM(freq), SUM(freq), SUM(flogf) FROM state');
    return $this->_entropy = mysql_fetch_array($dh);
  }

  // Result contains arrays of (probability (of 1), objectid, object name, object subtext)
  function getTopHunches($count = 9)
  {
    list($entropy, $stateSumF, $stateSumFLogF) = $this->getEntropy();

    $this->hunches = array();
    $sql = "SELECT id, freq, name, sub FROM state NATURAL JOIN objects ORDER BY freq DESC LIMIT $count";
    $dh = $this->query($sql);

    while (list($id, $freq, $name, $sub) = mysql_fetch_array($dh))
    {
      $this->hunches[] = array($freq/$stateSumF, $id, $name, $sub);
    }

    assert(count($this->hunches));
    return $this->hunches;
  }

  // Find the question to minimize state entropy
  // returns array($score, $id, $name, $sub);
  function getTopQuestions($count = 1)
  {
    list($entropy, $stateSumF, $stateSumFLogF) = $this->getEntropy();
    $questions = array(); // contains arrays like (score, questionid, name, subtext)

    $questionsToSkip = array_merge($this->yesQ, $this->noQ, $this->skipQ, array(0));
    $sql = "SELECT questionid, name, sub,
              $stateSumF + SUM(3*(yes+1)/(yes+no+skip+3) * state.freq - state.freq) as YesSumF,
              $stateSumFLogF + SUM(3*(yes+1)/(yes+no+skip+3) * state.freq * (log(2,3*(yes+1)/(yes+no+skip+3)) + state.logf) - state.flogf) as YesSumFlogF,
              $stateSumF + SUM(3*(no+1)/(yes+no+skip+3) * state.freq - state.freq) as NoSumF,
              $stateSumFLogF + SUM(3*(no+1)/(yes+no+skip+3) * state.freq * (log(2,3*(no+1)/(yes+no+skip+3)) + state.logf) - state.flogf) as NoSumFlogF,
              $stateSumF + SUM(3*(skip+1)/(yes+no+skip+3) * state.freq - state.freq) as SkipSumF,
              $stateSumFLogF + SUM(3*(skip+1)/(yes+no+skip+3) * state.freq * (log(2,3*(skip+1)/(yes+no+skip+3)) + state.logf) - state.flogf) as SkimSumFlogF
            FROM answers, state, questions
            WHERE answers.questionid NOT IN (".join($questionsToSkip, ',').")
            AND answers.questionid = questions.id
            AND answers.objectid = state.id
            GROUP BY answers.questionid";
    $dh = $this->query($sql);

    while (list($id, $name, $sub, $YS, $YSLS, $NS, $NSLS, $SS, $SSLS) = mysql_fetch_array($dh))
    {
      #TODO push ALL this math into the database? to make it lame and unreadable?
      $YesProb = $YS / ($YS+$NS+$SS);
      $NoProb = $NS / ($YS+$NS+$SS);
      $SkipProb = $SS / ($YS+$NS+$SS);

      $YesEntropy = log($YS,2) - $YSLS / $YS;
      $NoEntropy = log($NS,2) - $NSLS / $NS;
      $SkipEntropy = log($SS,2) - $SSLS / $SS;

      $questionScore = $entropy - ($YesProb*$YesEntropy + $NoProb*$NoEntropy + $SkipProb*$SkipEntropy);
      $questions[] = array($questionScore, $id, $name, $sub, $YesProb, $NoProb);
    }

    rsort ($questions);
    return array_slice($questions, 0, $count);
  }

## TODO ADD EARLY GUESS (before Q19) IF THE SCORE FOR GET GUESS QUESTION exceeds the Normal Question
  // Returns array(score of question "i am thinking of OBJ", top object name, top object subtext)
  function getGuessQuestion()
  {
    $hunches = $this->getTopHunches();
    list($p, $id, $name, $sub) = $hunches[0];

    list($entropy, $stateSumF, $stateSumFLogF) = $this->getEntropy();

    $maxFLogF = $this->maxFreq * log($this->maxFreq, 2);
    if (abs($this->maxFreq - $stateSumF) < 1)
      $score = $entropy;
    else
    {
      $entropyWithoutTopHunch = log($stateSumF-$this->maxFreq, 2) - ($stateSumFLogF - $maxFLogF) / ($stateSumF-$this->maxFreq);
      $entropyOfGuessTopHunch = $this->maxFreq / $stateSumF * 0.00 + (1-$this->maxFreq / $stateSumF) * $entropyWithoutTopHunch;
      $score = $entropy - $entropyOfGuessTopHunch;
    }
    return array($score, $id, "I am guessing that it is $name", $sub);
  }

  // gets an array: question, question subtext, array of arrays: description, token
  function getNextQuestion()
  {
    $answers = array();

    $qNum = count($this->pastQuestions) + 1;

    if ( ($qNum == 19) || ($qNum > 21 && $qNum % 5 == 0) )
    {
      list($gscore, $gid, $gname, $gsub) = $this->getGuessQuestion();
      $answers[] = array('Right',$this->state.'w'.$gid);
      $answers[] = array('Wrong',$this->state.'g'.$gid);
      return array($gname, $gsub, $answers);
    }

    $questions = $this->getTopQuestions(5);
    $choice = 0;
    if ($questions[4][0] > $questions[0][0] * 0.90)
    {
      # Have some fun here, the top questions are pretty close, pick one at random
      $choice = rand(0,4);
    }

    list($nscore, $nid, $nname, $nsub) = $questions[$choice];

    $answers[] = array('Yes',$this->state.'y'.$nid);
    $answers[] = array('No',$this->state.'n'.$nid);
    $answers[] = array('Skip this question',$this->state.'s'.$nid);

    return array($nname, $nsub, $answers);
  }

  function addObject($name)
  {
    $name = preg_replace('/[^a-z0-9_, ]/','',$name);
    $dh = $this->query("SELECT id FROM objects WHERE name = '$name'");
    if (list($id) = mysql_fetch_array($dh))
      return $id;

    $this->query("INSERT INTO objects (name, sub, hits, loghits, hidden) VALUES ('$name', '', 0, 0, 1)");
    return mysql_insert_id();
  }

  // commit answers to the database using the current state
  //
  function teach($objectid)
  {
    $objectid = intval($objectid);

    $this->query("UPDATE objects SET hits=hits+1, loghits=log2(hits+1)*65536 WHERE id=$objectid");
    $this->query("INSERT INTO logs (host,date,objectid,answers) VALUES (".sprintf("%u", ip2long($_SERVER['REMOTE_ADDR'])).",NOW(),$objectid,\"".mysql_escape_string($_REQUEST['q']).'")');

    foreach ($this->pastQuestions as $question)
    {
      list($name, $sub, $ans, $qid) = $question;
      if ($ans == 'wrong') continue;

      $this->query("INSERT INTO answers (objectid,questionid,$ans) VALUES (".$objectid.",$qid,1) ON DUPLICATE KEY UPDATE $ans=$ans+1")
        or die(__LINE__.mysql_error());
      $this->query("UPDATE answers SET pyes2=((yes+1)/(yes+no+skip+2))*2*65536 WHERE questionid=$qid AND objectid=".$objectid);
      $this->query("UPDATE answers SET pyes2min1=((yes+1)/(yes+no+skip+2))*2*65536-65536 WHERE questionid=$qid AND objectid=".$objectid);
      $this->query("UPDATE answers SET logpyes2=log2(((yes+1)/(yes+no+skip+2))*2)*65536 WHERE questionid=$qid AND objectid=".$objectid);
      $this->query("UPDATE answers SET pno2=((no+1)/(yes+no+skip+2))*2*65536 WHERE questionid=$qid AND objectid=".$objectid);
      $this->query("UPDATE answers SET pno2min1=((no+1)/(yes+no+skip+2))*2*65536-65536 WHERE questionid=$qid AND objectid=".$objectid);
      $this->query("UPDATE answers SET logpno2=log2(((no+1)/(yes+no+skip+2))*2)*65536 WHERE questionid=$qid AND objectid=".$objectid);
      $this->query("UPDATE answers SET pskip2=((skip+1)/(yes+no+skip+2))*2*65536 WHERE questionid=$qid AND objectid=".$objectid);
      $this->query("UPDATE answers SET pskip2min1=((skip+1)/(yes+no+skip+2))*2*65536-65536 WHERE questionid=$qid AND objectid=".$objectid);
      $this->query("UPDATE answers SET logpskip2=log2(((skip+1)/(yes+no+skip+2))*2)*65536 WHERE questionid=$qid AND objectid=".$objectid);
    }
  }
}

?>
