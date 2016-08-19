The Math
========

You have a bag of marbles {3 red, 2 blue, 1 black}. What is the probability of
choosing the black one with a blindfold on? 1 in 6. This is BASIC PROBABILITY
(we wont be using this).

You have a bag of purple, green, and white marbles. You pick & replace a marble
several times, the results are {4 purple, 2 green, 6 white}. What is the
propability of picking purple next time? The PROBABILITY is unknown, but you can
ESTIMATE it as (4+1)/(4+1 + 2+1 + 6+1). This is MAXIMUM LIKELIHOOD ESTIMATION.

(Another ESTIMATE could be "there are some many colors out there, the odds of
getting exactly green again are zero". There are even more ESTIMATION
techniques, but we are using MLE today.)

Before somebody picks a marble, we may want to calculate ENTROPY, which is how
much uncertainty we have about which marble they will pick. ENTROPY requires
knowledge of PROBABILITY which we don't have, but we can ESTIMATE using MAXIMUM
LIKELIHOOD ESTIMATION. For more on ENTROPY, see
http://fulldecent.blogspot.com/2009/12/interesting-properties-of-entropy.html

Someone reaches in that bag and pull out a "dark" marble, and everyone agrees
that purple and green are dark but white isn't. We don't know the PROBABILITY it
is purple or green, but can use MLE to get (4+1)/(4+1 + 2+1) chance of purple
and (2+1)/(4+1 + 2+1) chance of green. This is MAXIMUM A POSTERIORI ESTIMATION.

In the real world, someone picks a marble and tells us it's "dark". We can't be
certain on the definition of "dark", but three people said purple is dark, 2
said green was dark, 1 said green was not dark, and nobody said anything about
white. Likelihood of each marble in this situation is ESTIMATED with MLE and
MAP.

-   Purple: (4+1)/(4+1 + 2+1 + 6+1) \* (3+1)/(3+1 + 0+1) / total

-   Green:  (2+1)/(4+1 + 2+1 + 6+1) \* (2+1)/(2+1 + 1+1) / total

-   White:  (6+1)/(4+1 + 2+1 + 6+1) \* (0+1)/(0+1 + 0+1) / total

Those likelihoods above can be used to ESTIMATE the ENTROPY of the situation.

Someone just chose a marble, what is the PROBABILITY of them agreeing it is
"dark"? We don't know what marbles there are and we are we're not even sure
about colors now, but we can use MLE and MAP to ESTIMATE. So let's compare the
ESTIMATES of "dark" and not "dark". The LIKELIHOOD of them agreeing "dark" will
be the sum of the first three divided by all six options.

**Situations they would agree it is "dark" are (using MLE):**

-   Purple: (4+1)/(4+1 + 2+1 + 6+1) \* (3+1)/(3+1 + 0+1)

-   Green:  (2+1)/(4+1 + 2+1 + 6+1) \* (2+1)/(2+1 + 1+1)

-   White:  (6+1)/(4+1 + 2+1 + 6+1) \* (0+1)/(0+1 + 0+1)

**Situations they would not agree it is "dark" are (using MLE):**

-   Purple: (4+1)/(4+1 + 2+1 + 6+1) \* (0+1)/(3+1 + 0+1)

-   Green:  (2+1)/(4+1 + 2+1 + 6+1) \* (1+1)/(2+1 + 1+1)

-   White:  (6+1)/(4+1 + 2+1 + 6+1) \* (0+1)/(0+1 + 0+1)

After they answer we can calculate ENTROPY of the marbles using MAP. But since
we have estimated the likelihood of both possible results and the ENTROPY under
each, we can superpose before asking the question to calculate the EXPECTED
VALUE of ENTROPY. Comparing ENTROPY before we ask to EXPECTED VALUE after we ask
gives us the EXPECTED ENTROPY reduction of the question.

The goal of this game is to choose the best questions (i.e. that are ESTIMATED
to reduce EXPECTED ENTROPY the most).

What’s Going on Here
====================

The algorithm is:

1.  Use past answers and bayesian median likelihood formula to assess
    "probabilities" of objects being correct.

2.  Choose questions which minimize the expected entropy of this set of
    probabilities. (We use "expected" here: a weighted average based on
    likelihood of each question response)

Calculating entropy is slow:

>   \-sum (x\_i/sum x) log2 (x\_i/sum x) (in bits)

—or—

>   log2 prod (x\_i/sum x)\^-(x\_i/sum x) (in bits)

because you need to do a floating point op (log or exponent) after calculation
p\_x, which depends on each likelihood vs. the total likelihood. This needs to
be done for each question for each object for each round.

**Optimization:**

>   entropy = log2 sum x - (sum x\_i log2 x\_i) / sum x

here you can precalculate most of the FP math

http://fulldecent.blogspot.com/2009/12/interesting-properties-of-entropy.html

Other Stuff To Clean Up
=======================

`+---------+------------------+------+-----+---------+----------------+`

`| Field   | Type             | Null | Key | Default | Extra          |`

`+---------+------------------+------+-----+---------+----------------+`

`| id      | int(10) unsigned | NO   | PRI | NULL    | auto_increment | `

`| hits    | int(10) unsigned | NO   |     | 1       |                | `

`| loghits | int(10) unsigned | NO   |     | NULL    |                | `

`| name    | varchar(30)      | NO   | MUL | NULL    |                | `

`| sub     | varchar(30)      | NO   |     | NULL    |                | `

`+---------+------------------+------+-----+---------+----------------+`

`5 rows in set (0.00 sec)`

 

`hits = 5`

`lights = (int) log2 hits(5) * 65536`

 

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
+------------+------------------+------+-----+---------+-------+
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
| Field      | Type             | Null | Key | Default | Extra |
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
+------------+------------------+------+-----+---------+-------+
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
| objectid   | int(10) unsigned | NO   | PRI | NULL    |       | 
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
| questionid | int(10) unsigned | NO   | PRI | NULL    |       | 
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
| yes        | int(10) unsigned | NO   |     | 0       |       | 
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
| pyes2      | int(10) unsigned | NO   |     | NULL    |       | 
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
| pyes2min1  | int(10)          | NO   |     | NULL    |       | 
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
| logpyes2   | int(10)          | NO   |     | NULL    |       | 
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
| no         | int(10) unsigned | NO   |     | 0       |       | 
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
| pno2       | int(10) unsigned | NO   |     | NULL    |       | 
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
| pno2min1   | int(10)          | NO   |     | NULL    |       | 
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
| logpno2    | int(10)          | NO   |     | NULL    |       | 
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
+------------+------------------+------+-----+---------+-------+
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
10 rows in set (0.01 sec)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

 

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
UPDATE objects SET loghits=log2(hits)*65536;
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
UPDATE answers SET pyes2=((yes+1)/(yes+no+skip+2))*2*65536;
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
UPDATE answers SET pyes2min1=((yes+1)/(yes+no+skip+2))*2*65536-65536;
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
UPDATE answers SET logpyes2=log2(((yes+1)/(yes+no+skip+2))*2)*65536;
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
UPDATE answers SET pno2=((no+1)/(yes+no+skip+2))*2*65536;
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
UPDATE answers SET pno2min1=((no+1)/(yes+no+skip+2))*2*65536-65536;
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
UPDATE answers SET logpno2=log2(((no+1)/(yes+no+skip+2))*2)*65536;
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
UPDATE answers SET pskip2=((skip+1)/(yes+no+skip+2))*2*65536;
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
UPDATE answers SET pskip2min1=((skip+1)/(yes+no+skip+2))*2*65536-65536;
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
UPDATE answers SET logpskip2=log2(((skip+1)/(yes+no+skip+2))*2)*65536;
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
## All logs are base 2!!!
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
## All "floats are / 65536"
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

 

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
CREATE TEMPORARY TABLE prod (id INT UNSIGNED, sl INT UNSIGNED, PRIMARY KEY(id)) SELECT objectid as id, SUM(logprel2) AS sl FROM (SELECT null as objectid, null as logprel2 UNION ALL SELECT objectid, logpyes2 as logprel2 FROM answers WHERE questionid IN (144,19,46) UNION ALL SELECT objectid, logpno2 as logprel2 FROM answers WHERE questionid IN (106,48,32,53,190)) xx GROUP BY objectid;
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
CREATE TEMPORARY TABLE state (id INT UNSIGNED, freq INT, flogf INT, logf INT, PRIMARY KEY(id)) SELECT id, hits*COALESCE(POW(2,sl/65536+16), 65536) AS freq,              hits*COALESCE(POW(2,sl/65536), 1) * (loghits+COALESCE(sl,0)) AS flogf,              loghits+COALESCE(sl,0) AS logf FROM objects NATURAL LEFT JOIN prod;
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
SELECT questionid, name, sub, 4252.3333+(SUM(freq*pyes2min1) / 4294967296) as ty, 4252.3333+(SUM(freq*pno2min1) / 4294967296) as tn, 6.5534+SUM((freq*pyes2 / 4294967296 * (logf+logpyes2)) - flogf)/65536 as syly, 6.5534+SUM((freq*pno2 / 4294967296 * (logf+logpno2)) - flogf)/65536 as snln FROM state INNER JOIN answers ON state.id=objectid AND questionid NOT IN (106) INNER JOIN questions on questionid=questions.id GROUP BY questionid;
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
http://login.tudou.com/reg.do
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
