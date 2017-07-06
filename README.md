# 19 Questions

**Welcome to the 19 Questions game!**

19 Questions is a machine learning game which asks you questions and guesses an
object you are thinking about. It uses your answers to improve its interrogation
technique.

Access the demo site at: https://phor.net/apps/19q/

Although inspired by neural networking, 19 Questions is based entirely on the
Bayesian inference technique.

![screen shot 2017-07-06 at 11 56 39 am](https://user-images.githubusercontent.com/382183/27921147-d5e5e11e-6244-11e7-8e2b-b2570aca8eac.png)

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
