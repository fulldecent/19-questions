19-questions
============

A machine learning model and game which asks you questions to learn what object
you are thinking about.

Access the demo site at: https://phor.net/apps/19q/

Install
-------

Create a SQL database using SCHEMA.sql and assign a user with permissions to
that database.

Copy sources/config.example.php to sources/config.php and provide details to
access this database.

Then manually add a some data to the database to get started, or run
harvestfrom20q to interrogate another popular server.

Copy the files to your server and you can now play by accessing the website from
a browser.

What it Does
------------

This program documents experiences from past user interaction and uses this as
evidence to assume connections between objects (e.g. “apple") and predicates
(e.g. “is larger than a loaf of bread”).

The program uses statistical inference to estimate which object you are thinking
of based on answers you provide. Then it asks you a question which hopefully
will bifurcate the remaining universe of likely objects.

The computer wins if it correctly guesses the player’s object within 19
questions.

How it Works
------------

The user’s history of answers are compared against other user’s past responses
to questions. We use an unbiased maximum likelihood estimator to find the
likelihood of each known object being the user’s object. We use these likelihood
weightings and estimate the entropy of this uncertainty. We then choose a
question which is expected to minimize this entropy. If entropy is low we hazard
a guess of what the object is.

For a deeper technical explanation, see TECHNICAL.md and
<https://fulldecent.blogspot.com/2009/12/interesting-properties-of-entropy.html>

 
