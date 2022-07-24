TODO: fix LaTeX transcription errors in formulas, see blog post and double check

# The math

You have a bag of marbles {3 red, 2 blue, 1 black}. What is the probability of choosing the black one with a blindfold on? $\frac{1}{6}$. This is BASIC PROBABILITY (we wont be using this).

You have a bag of some purple, green, and white marbles. You pick & replace a marble several times, the observation results are {4 purple, 2 green, 6 white}. What is the probability of picking purple next time? The PROBABILITY is unknown, but you can ESTIMATE it as $\frac{(4+1)}{(4+1) + (2+1) + (6+1)}$. This is MAXIMUM LIKELIHOOD ESTIMATION.

(Another ESTIMATE could be "there are so many colors out there, the odds of getting exactly green again are zero". There are even more ESTIMATION techniques, but we are using MLE today.)

Before somebody picks a marble, we may want to calculate ENTROPY, which is how much uncertainty we have about which marble they will pick. ENTROPY requires knowledge of PROBABILITY which we don't have, but we can ESTIMATE using MAXIMUM LIKELIHOOD ESTIMATION. For more on ENTROPY, see http://fulldecent.blogspot.com/2009/12/interesting-properties-of-entropy.html

Someone reaches in that bag and pull out a "dark" marble. Here everyone agrees that purple and green are dark but white isn't. We don't know the PROBABILITY it is purple or green, but can use MLE to get $\frac{(4+1)}{(4+1) + (2+1)}$ chance of purple and $\frac{(2+1)}{(4+1) + (2+1)}$ chance of green. This is MAXIMUM A POSTERIORI ESTIMATION.

In the real world, someone picks a marble and tells us it's "dark". We can't be certain on the definition of "dark", but three people said purple is dark, 2 said green was dark, 1 said green was not dark, and nobody said anything about white. Likelihood of each marble in this situation is ESTIMATED with MLE and
MAP.

-   Purple: $\frac{(4+1)}{(4+1) + (2+1) + (6+1)}\frac{(3+1)}{((3+1) + (0+1))total}$

-   Green:  $\frac{(2+1)}{(4+1) + (2+1) + (6+1)}\frac{(2+1)}{((2+1) + (1+1))total}$

-   White:  $\frac{(6+1)}{(4+1) + (2+1) + (6+1)}\frac{(0+1)}{((0+1) + (0+1))total}$

Those likelihoods above can be used to ESTIMATE the ENTROPY of the situation.

Someone just chose a marble, what is the PROBABILITY of them agreeing it is "dark"? We don't know what marbles there are and we are we're not even sure about colors now, but we can use MLE and MAP to ESTIMATE. So let's compare the ESTIMATES of "dark" and not "dark". The LIKELIHOOD of them agreeing "dark" will be the sum of the first three divided by all six options.

**Situations they would agree it is "dark" are (using MLE):**

-   Purple: $\frac{(4+1)}{(4+1) + (2+1) + (6+1)}\frac{(3+1)}{(3+1) + (0+1)}$

-   Green:  $\frac{(2+1)}{(4+1) +(2+1) + (6+1)}\frac{(2+1)}{(2+1) + (1+1)}$

-   White:  $\frac{(6+1)}{(4+1) + (2+1) + (6+1)}\frac{(0+1)}{(0+1) + (0+1)}$

**Situations they would not agree it is "dark" are (using MLE):**

-   Purple: $\frac{(4+1)}{(4+1) + (2+1) + (6+1)}\frac{(0+1)}{(3+1) + (0+1)}$

-   Green:  $\frac{(2+1)}{(4+1) + (2+1) + (6+1)}\frac{(1+1)}{(2+1) + (1+1)}$

-   White:  $\frac{(6+1)}{(4+1) + (2+1) + (6+1)}\frac{(0+1)}{(0+1) + (0+1)}$

After they answer we can calculate ENTROPY of the marbles using MAP. But since we have estimated the likelihood of both possible results and the ENTROPY under each, we can superpose before asking the question to calculate the EXPECTED VALUE of ENTROPY. Comparing ENTROPY before we ask to EXPECTED VALUE after we ask gives us the EXPECTED ENTROPY reduction of the question.

The goal of this game is to choose the best questions (i.e. that are ESTIMATED to reduce EXPECTED ENTROPY the most).

## What’s going on here

The algorithm is:

1.  Use past answers and bayesian median likelihood formula to assess "probabilities" of objects being correct.
    
2.  Choose questions which minimize the expected entropy of this set of probabilities. (We use "expected" here: a weighted average based on likelihood of each question response)

Calculating entropy in bits is slow:

$$-\sum{\frac{x_i}{\sum x_i} log_2(\frac{x_i}{\sum{x_i}})}$$

or:

$$log_2(\prod{\frac{x_i}{\sum{x_i}}^\frac{-x_i}{\sum{x_i}}})$$

because you need to find the total likelihood and then go revisit each outcome to perform the calculation, and you need to do logarithms or exponents. This needs to be done for each question for each object for each round.

**Optimization:**

$$log_2(\frac{\sum{x_i-\sum(x_i log_2 x_i)}}{\sum{x_i}})$$

here you can pre-calculate most of the math AND you only need to visit outcomes once.

http://fulldecent.blogspot.com/2009/12/interesting-properties-of-entropy.html
