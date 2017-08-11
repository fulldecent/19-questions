<?php
namespace NineteenQ;

class Engine
{
  private $database;

  /**
   * A string describing game state
   * Example: y23n359s293y3y28
   * Meaning: the user replied YES to question #23, and replied NO to ...
   * @var string
   */
  var $state;

  /// Question IDs describing my state
  var $yesses, $noes, $skips;

  /// Object IDs that I guessed that were wrong
  var $guesses;

  // [[QUESTION_NAME, QUESTION_SUBNAME, YES/NO/SKIP]]
  var $askedQuestions = [];

  var $objectEntropy = NULL; // Entropy of estimated object likelihoods
  var $objectSumLikelihood; // Sum of likelihoods
  var $objectSumLLL;     // Sum of (likelihood * log(likelihood))

  var $debug = []; // Random info

  function __construct($state = '')
  {
    $start = microtime(true);
    $this->database = new \NineteenQ\Db();
    $questionStatement = $this->database->prepare('SELECT name, subname FROM questions WHERE questionid=?');
    $objectStatement = $this->database->prepare('SELECT name, subname FROM objects WHERE objectid=?');

    ##
    ## Parse the STATE string
    ##
    $this->yesses = $this->noes = $this->skips = $this->guesses = [];
    $this->askedQuestions = [];
    preg_match_all('/([ynsg])(\d+)/', $state, $regs, PREG_SET_ORDER);
    foreach ($regs as $reg) {
      switch ($reg[1]) {
        case 'y':
          $questionStatement->execute([$reg[2]]);
          list($name, $sub) = $questionStatement->fetch(\PDO::FETCH_NUM);
          $this->yesses[] = $reg[2];
          $this->askedQuestions[] = array($name, $sub, 'yes', $reg[2]);
          break;
        case 'n':
          $questionStatement->execute([$reg[2]]);
          list($name, $sub) = $questionStatement->fetch(\PDO::FETCH_NUM);
          $this->noes[] = $reg[2];
          $this->askedQuestions[] = array($name, $sub, 'no', $reg[2]);
          break;
        case 's':
          $questionStatement->execute([$reg[2]]);
          list($name, $sub) = $questionStatement->fetch(\PDO::FETCH_NUM);
          $this->skips[] = $reg[2];
          $this->askedQuestions[] = array($name, $sub, 'skip', $reg[2]);
          break;
        case 'g':
          $objectStatement->execute([$reg[2]]);
          list($name, $sub) = $objectStatement->fetch(\PDO::FETCH_NUM);
          $this->guesses[] = $reg[2];
          $this->askedQuestions[] = array('I am guessing that it is ' . $name, $sub, 'wrong', $reg[2]);
          break;
        default:
          break;
      }
      $this->state .= $reg[1] . $reg[2];
    }
    $this->debug[__FUNCTION__] = number_format(microtime(true) - $start, 3) . ' SECONDS';
  }

  /**
   * estimateObjectLikelihoods
   *
   * Create temporary table `evidence` storing object IDs and likelihood.
   * Likelihood that the user believes OBJECT matches given yes/no/skip predicates.
   *
   * If we have no past knowledge regarding QUESTION, then we estimate likelihood
   * that user agrees QUESTION is YES for an object is 1/3. (Because there are
   * three options, yes/no/skip).
   *
   * To simplify this default value, we instead calculate the log (base 2) of
   * three times the likelihood. Now the default is log(3*1/3)=0. Much nicer.
   */
  function estimateObjectLikelihoods()
  {
    $start = microtime(true);
    if (!empty($this->_didEstimateObjectLikelihoods)) return;
    $this->_didEstimateObjectLikelihoods = 1;

    $placeholdersY = join(array_fill(0, count($this->yesses), '?'), ',');
    $placeholdersN = join(array_fill(0, count($this->noes), '?'), ',');
    $placeholdersS = join(array_fill(0, count($this->skips), '?'), ',');
    $placeholdersG = join(array_fill(0, count($this->guesses), '?'), ',');
    $sql = "
    SELECT objects.objectid,
           objects.calc_logl + COALESCE(SUM(evidence.logl), 0) logl
--           1 + COALESCE(SUM(evidence.logl), 0) logl
--           COALESCE(SUM(evidence.logl), 0) logl
      FROM objects
           LEFT JOIN
           (SELECT objectid, calc_y3ll logl FROM answers WHERE questionid IN ($placeholdersY)
             UNION ALL
            SELECT objectid, calc_n3ll logl FROM answers WHERE questionid IN ($placeholdersN)
             UNION ALL
            SELECT objectid, calc_s3ll logl FROM answers WHERE questionid IN ($placeholdersS)) evidence
           ON evidence.objectid = objects.objectid
     WHERE visible = 1
       AND objects.objectid NOT IN ($placeholdersG)
     GROUP BY objects.objectid";
    $binds = array_merge($this->yesses, $this->noes, $this->skips, $this->guesses);
    $statement = $this->database->prepare($sql);
    $statement->execute($binds);

    // I'd rather do this transformation in SQL but SQLite math functions are limited
    $this->database->beginTransaction();
    $this->database->exec('CREATE TEMPORARY TABLE object_likelihood(objectid PRIMARY KEY, l REAL, lll REAL)');
    $insertStatement = $this->database->prepare('INSERT INTO object_likelihood VALUES(?,?,?)');
    $this->objectEntropy = 0;
    $this->objectSumLikelihood = 0;
    $this->objectSumLLL = 0;
    while($row = $statement->fetch(\PDO::FETCH_NUM)) {
      list($objectId, $logL) = $row;
      $l = pow(2, $logL);
      $values = [$objectId, $l, $l*$logL];
      $insertStatement->execute($values);
      $this->objectSumLikelihood += $l;
      $this->objectSumLLL += $l*$logL;
    }
    $this->objectEntropy = log($this->objectSumLikelihood, 2) - $this->objectSumLLL / $this->objectSumLikelihood;
    $this->database->commit();
    $this->debug[__FUNCTION__] = number_format(microtime(true) - $start, 3) . ' SECONDS';
  }

  // Returns [(object)[objectId=>..., name=>..., subname=>..., likelihood=>...]]
  // Sorted in order of best guess first
  function getTopHunches()
  {
    if (!empty($this->hunches)) return $this->hunches;
    $this->estimateObjectLikelihoods();
    $sql = '
      SELECT objects.objectid objectId, objects.name, objects.subname, l likelihood
        FROM object_likelihood
        JOIN objects ON objects.objectid = object_likelihood.objectid
       ORDER BY l DESC
       LIMIT 10
    ';
    $statement = $this->database->query($sql);
    $this->hunches = $statement->fetchAll(\PDO::FETCH_OBJ);
    return $this->hunches;
  }

  /**
   * getBestQuestions
   *
   * Each question we could ask has a YES/NO/SKIP answer. We can estimate
   * likelihood of each response and the entropy of system state given responses.
   * So we pick the question that are expected to reduce entropy the most.
   *
   * @return [[score, questionid, name, subname, yesLikelihood, noLikelihood]]
   */
  function getBestQuestions()
  {
    if (!empty($this->_bestQuestions)) return $this->_bestQuestions;
    $start = microtime(true);
    $this->estimateObjectLikelihoods();
    $binds = array_merge($this->yesses, $this->noes, $this->skips);
    $placeholdersSkipQuestions = join(array_fill(0, count($binds), '?'), ',');
    $sql = "SELECT questions.questionid, questions.name, questions.subname,
                   calc_y3lmin1 * SUM(state.l) as yes_delta_l,
                   SUM(calc_y3lmin1 * state.lll + state.l * calc_y3lll) as yes_delta_lll,
                   calc_n3lmin1 * SUM(state.l) as no_delta_l,
                   SUM(calc_n3lmin1 * state.lll + state.l * calc_n3lll) as no_delta_lll,
                   calc_s3lmin1 * SUM(state.l) as skip_delta_l,
                   SUM(calc_s3lmin1 * state.lll + state.l * calc_s3lll) as skip_delta_lll,
COUNT(*), SUM(state.l), SUM(state.lll), SUM(calc_y3lmin1 + calc_n3lmin1 + calc_s3lmin1)
              FROM questions
              JOIN answers ON answers.questionid = questions.questionid
              JOIN object_likelihood state ON answers.objectid = state.objectid
             WHERE questions.questionid NOT IN ($placeholdersSkipQuestions)
             GROUP BY questions.questionid";
    $statement = $this->database->prepare($sql);
    $statement->execute($binds);
    $questions = [];
    while ($row = $statement->fetch(\PDO::FETCH_NUM)) {
#var_dump($row);
#die();
      list($questionId, $name, $subname, $yesDeltaL, $yesDeltaLLL, $noDeltaL, $noDeltaLLL, $skipDeltaL, $skipDeltaLLL) = $row;
      $yesSumL = $yesDeltaL + $this->objectSumLikelihood;
      $noSumL = $noDeltaL + $this->objectSumLikelihood;
      $skipSumL = $skipDeltaL + $this->objectSumLikelihood;
      $yesSumLLL = $yesDeltaLLL + $this->objectSumLLL;
      $noSumLLL = $noDeltaLLL + $this->objectSumLLL;
      $skipSumLLL = $skipDeltaLLL + $this->objectSumLLL;
      $denom = $yesSumL + $noSumL + $skipSumL;
      $yesLikelihood = $yesSumL / $denom;
      $noLikelihood = $noSumL / $denom;
      $skipLikelihood = $skipSumL / $denom;
      $yesEntropy = log($yesSumL, 2) - $yesSumLLL / $yesSumL;
      $noEntropy = log($noSumL, 2) - $noSumLLL / $noSumL;
      $skipEntropy = log($skipSumL, 2) - $skipSumLLL / $skipSumL;
      $score = $this->objectEntropy - ($yesLikelihood*$yesEntropy + $noLikelihood*$noEntropy + $skipLikelihood*$skipEntropy);
      $questions[] = [$score, $questionId, $name, $subname, $yesLikelihood, $noLikelihood];
    }
    rsort($questions);
    $this->debug[__FUNCTION__] = number_format(microtime(true) - $start, 3) . ' SECONDS';
    $this->_bestQuestions = array_slice($questions, 0, 10);
    return $this->_bestQuestions;
  }

  // Returns array(score of question "i am thinking of OBJ", top object name, top object subtext)
  // [score, questionid, name, subname, yesLikelihood, noLikelihood]
  function getGuessQuestion()
  {
    $hunches = $this->getTopHunches();
    $topHunch = $hunches[0];

    $entropyIfGuessCorrect = 0;
    $sumLikelihoodIfGuessWrong = $this->objectSumLikelihood - $topHunch->likelihood;
    $sumLLLIfGuessWrong = $this->objectSumLLL - $topHunch->likelihood * log($topHunch->likelihood, 2);
    $entropyIfGuessWrong = log($sumLikelihoodIfGuessWrong, 2) - $sumLLLIfGuessWrong / $sumLikelihoodIfGuessWrong;
    $expectedEntropy = $entropyIfGuessCorrect * ($topHunch->likelihood/$this->objectSumLikelihood) +
                       $entropyIfGuessWrong * (1 - $topHunch->likelihood/$this->objectSumLikelihood);
    $score = $this->objectEntropy - $expectedEntropy;

    //TODO remove this fudge factor when we have some reliable data in ANSWERS table
    $score = $score / 3;

    return [$score, $topHunch->objectId, 'I am guessing that it is ' . $topHunch->name, $topHunch->subname];
  }

  // [name, subname, [response, token]]
  function getNextQuestion()
  {
    $answers = [];
    $questionNumber = count($this->askedQuestions) + 1;
    $doGuessQuestion = false;
    if (($questionNumber == 19) || ($questionNumber > 21 && $questionNumber % 4 == 0)) {
      $doGuessQuestion = true;
    }

    $questions = $this->getBestQuestions();
    $guessQuestion = $this->getGuessQuestion();
    if ($guessQuestion[0] > $questions[0][0]) { // rank by ->score
      $doGuessQuestion = true;
    }

    if ($doGuessQuestion) {
      list($gscore, $gid, $gname, $gsub) = $this->getGuessQuestion();
      $answers[] = ['Right', $this->state.'w'.$gid];
      $answers[] = ['Wrong', $this->state.'g'.$gid];
      return [$gname, $gsub, $answers];
    }

    if (!count($questions)) return ['no questions', '', []];
    $choice = 0;
    if ($questions[4][0] > $questions[0][0] * 0.90) {
      # Have some fun here, the top questions are pretty close, pick one at random
      $choice = rand(0,4);
    }

    list($nscore, $nid, $nname, $nsub) = $questions[$choice];
    $answers[] = ['Yes', $this->state.'y'.$nid];
    $answers[] = ['No', $this->state.'n'.$nid];
    $answers[] = ['Skip this question', $this->state.'s'.$nid];
    return [$nname, $nsub, $answers];
  }

  function getObject($objectId)
  {
    $statement = $this->database->prepare('SELECT name, subname FROM objects WHERE objectid = ?');
    $statement->execute([$objectId]);
    return $statement->fetch(\PDO::FETCH_NUM);
  }

  function getObjectByName($name)
  {
    $name = preg_replace('/[^a-z0-9_, ]/','', $name);
    $sql = 'SELECT objectid FROM objects WHERE name = ?';
    $statement = $this->database->prepare($sql);
    $statement->execute([$name]);
    if ($objectId = $statement->fetchColumn()) {
      return $objectId;
    }

    $sql = 'INSERT INTO objects (name, hits, calc_logl) VALUES (?,?,?)';
    $statement = $this->database->prepare($sql);
    $statement->execute([$name, 1, log(1+1, 2)]);
    return $this->database->lastInsertId();
  }

  // commit answers to the database using the current state
  function teach($objectId)
  {
    $sql1 = 'SELECT hits FROM objects WHERE objectid = ?';
    $statement1 = $this->database->prepare($sql1);
    $statement1->execute([$objectId]);
    $hits = $statement1->fetchColumn();

    $sql2 = '
      UPDATE objects
         SET hits = hits + 1,
             calc_logl = ?
       WHERE objectid = ?
    ';
    $statement2 = $this->database->prepare($sql2);
    $statement2->execute([log($hits + 1, 2), $objectId]);

    $sql3 = 'INSERT OR IGNORE INTO answers (objectid, questionid) VALUES (?,?)';
    $statement3 = $this->database->prepare($sql3);
    $sql4y = 'UPDATE answers SET yes = yes+1 WHERE objectid = ? AND questionid = ?';
    $statement4y = $this->database->prepare($sql4y);
    $sql4n = 'UPDATE answers SET no = no+1 WHERE objectid = ? AND questionid = ?';
    $statement4n = $this->database->prepare($sql4n);
    $sql4s = 'UPDATE answers SET skip = skip+1 WHERE objectid = ? AND questionid = ?';
    $statement4s = $this->database->prepare($sql4s);

    $sql5 = 'SELECT yes, no, skip FROM answers WHERE objectid = ? AND questionid = ?';
    $statement5 = $this->database->prepare($sql5);
    $sql6 = 'UPDATE answers SET calc_y3lmin1=?, calc_n3lmin1=?, calc_s3lmin1=?, calc_y3lll=?, calc_n3lll=?, calc_s3lll=?, calc_y3ll=?, calc_n3ll=?, calc_s3ll=? WHERE objectid = ? AND questionid = ?';
    $statement6 = $this->database->prepare($sql6);

    foreach ($this->askedQuestions as $question) {
      list($name, $subname, $answer, $questionId) = $question;
      $statement4 = null;
      switch ($answer) {
        case 'yes':
          $statement4 = $statement4y;
          break;
        case 'no':
          $statement4 = $statement4n;
          break;
        case 'skip':
          $statement4 = $statement4s;
          break;
      }
      if (!empty($statement4)) {
        $statement4->execute([$objectId, $questionId]);
        $statement5->execute([$objectId, $questionId]);
        list($yes, $no, $skip) = $statement5->fetch(\PDO::FETCH_NUM);
        $binds = [];
        $binds[] = 3*($yes+1)/($yes+$no+$skip+3)-1;  // calc_y3min1
        $binds[] = 3*($no+1)/($yes+$no+$skip+3)-1;   // calc_n3min1
        $binds[] = 3*($skip+1)/($yes+$no+$skip+3)-1; // calc_s3min1
        $binds[] = $binds[0] * log($binds[0], 2);    // calc_y3lll
        $binds[] = $binds[1] * log($binds[1], 2);    // calc_n3lll
        $binds[] = $binds[2] * log($binds[2], 2);    // calc_s3lll
        $binds[] = log($binds[0], 2);                // calc_y3ll
        $binds[] = log($binds[1], 2);                // calc_n3ll
        $binds[] = log($binds[2], 2);                // calc_s3ll
        $statement6->execute($binds);
      }
    }
  }
}

?>
