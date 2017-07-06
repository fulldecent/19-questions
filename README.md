# 19 Questions

**Welcome to the 19 Questions game!**

19 Questions is a machine learning game which asks you questions and guesses an
object you are thinking about. It uses your answers to improve its interrogation
technique.

Access the demo site at: https://phor.net/apps/19q/

Although inspired by neural networking, 19 Questions is based *entirely on the
Bayesian inference technique*.


## Getting started

Run `php -S localhost:8888` from inside this folder and point your web browser
to [http://localhost:8888](http://localhost:8888).

It uses SQLite so the database is already set up and checked into this
repository.

For production use, copy all files to your web server. Also consider using a
different database (see SCHEMA.sql) for installations with more than 10,000
visitors per hour.


## Documentation

This program remembers past games and uses this as evidence to assume
connections between objects (e.g. “microwave") and predicates (e.g. “is larger
than a loaf of bread”).

The program uses statistical inference (unbiased maximum likelihood estimator)
to estimate which object you are thinking of based on answers you provide. Then
it asks you a question which hopefully will bifurcate the remaining universe of
likely objects.

The computer wins if it correctly guesses the player’s object within 19
questions.

For more discussion of the algorithm, see [TECHNICAL.md](TECHNICAL.md).


## About the maintainer

**William Entriken** is interested to learn more about machine learning and
helps SEPTA, Philadelphia's local municipal transport system, improve their
rail schedules. Read more about the author at: https://phor.net/.


## Contributing to 19 Questions

Contributions to 19 Questions are welcome and encouraged!

If you can improve the statistical calculations, presentation of information or
technical documentation, this would be most appreciated.
