# 19-questions

A machine learning model and game which asks you questions to learn what object you are thinking about.

Access the demo site at: [apps.phor.net/19q/](https://apps.phor.net/19q/).

## Install

Create a SQL database using SCHEMA.sql and assign an account with permissions to that database.

Copy sources/config.example.php to sources/config.php and provide details to access this database.

Then manually add a some data to the database to get started, or run harvestfrom20q to interrogate another popular server.

Copy the files to your server and you can now play by accessing the website from a browser.

## What it does

This program documents experiences from past player interaction and uses this as evidence to assume connections between objects (e.g. “apple") and predicates e.g. “is larger than a loaf of bread”).

The program uses statistical inference to estimate which object you are thinking of based on answers you provide. Then it asks you a question which hopefully will bifurcate the remaining universe of likely objects.

The computer wins if it correctly guesses the player’s object within 19 questions.

## How it works

The player’s history of answers are compared against other player’s past responses to questions. We use an unbiased maximum likelihood estimator to find the likelihood of each known object being the player’s object. We use these likelihood weightings and estimate the entropy of this uncertainty. We then choose a question which is expected to minimize this entropy. If entropy is low we hazard a guess of what the object is.

For a deeper technical explanation, see TECHNICAL.md and https://blog.phor.net/interesting-properties-of-entropy

## References

1. This project is built based on [best practices documented in php-package-template](https://github.com/fulldecent/php-package-template/), release 1.0.0. [`.editorconfig`](.editorconfig) takes that release's PHP rule: [PSR-12](https://www.php-fig.org/psr/psr-12/) four-space indent for `*.php`, and the shared UTF-8, LF, and final-newline rules.
1. 19-questions is a PHP website installed from [SCHEMA.sql](SCHEMA.sql). php-package-template release 1.0.0 is a Composer package. This repository does not take its `composer.json`, PHPUnit, PHPStan, `.php-version`, or the Release Please workflow that attests a zip.
1. This project is released under the [MIT license](LICENSE).

 
