#!/usr/bin/perl

#
# TODO: add asserts, like question text < 30 chars
#

use strict;
use warnings;
use LWP::UserAgent;
use DBI;

#open OBJECTS, '>>objects.txt';
#open QUESTIONS, '>>questions.txt';
#open DATA, '>>data.txt';

my $dbh = DBI->connect('DBI:mysql:19q:127.0.0.1','root','')
  or die "DB error ".$@;

my $agent =  new LWP::UserAgent;
$agent->agent ('Mozilla/4.0');

my $playing = 0;
my $actionform = undef;
my $theanswer = '';
my $number = undef;   # Q1, Q2 -> 1, 2 ...
my $question = '';    # the last question asked
my $questionsub = ''; #   and the second part
my $answers = [];     # array of answers `yes`, `no`, ...
my $choices = {};     # hash from answers to URLs
my ($object, $objectsub);   # when 20q makes a guess
my $objectid = 0;     # the mysql insertid

my $myanswers = {};

sub request {
	my ($method,$url,$args) = @_;

	my $reply = undef;
	if ($method eq 'GET') {
		$reply = $agent->get ($url);
	}
	elsif ($method eq 'POST') {
		$reply = $agent->post ($url,$args);
	}

	die "HTTP failed $@" unless defined $reply;
	die "Could not access $url: " . $reply->status_line unless $reply->is_success;

	return $reply->content;
}

sub start {
	my $url = 'http://y.20q.net/anon';
	my $login = request ('GET', $url);

	($actionform) = $login =~ /action="(.+?)"/i;
	$url = 'http://y.20q.net'.$actionform;
	my $reply = request ('POST',$url, { submit => 'Play' });

	# Get the first question.
	($number, $question) = $reply =~ /<big><b>Q(\d+)\. &nbsp;(.+?)<br>/i;
	die "First q not found" unless defined $question;

	$playing = 1;

	# Get the choices.
	$answers = [];
	$choices = {};
	while ($reply =~ /<a href="\/anon?(.+?)" target="_top">(.+?)<\/a>/ig) {
		my $label = $2;
		next if $label eq '<font color="#000033"><font size="+3"><b>?</b></font></font>';
		push (@$answers, $label);
		$choices->{$label} = $1;
	}
}

# Send the answer into the game and get the next question
sub answer {
	my ($answer) = @_; # like `yes` or `no`
	my $id = $choices->{$answer} or die "Invalid answer $answer";
	my $reply = request ('GET',"http://y.20q.net/$id");

	if (($answer eq 'Yes') or ($answer eq 'No')) {
		$myanswers->{$question} = $answer;
	}

	# Get their reply
	($number,$question) = $reply =~ /<big><b>Q(\d+)\. &nbsp;(.+?)<br>/i;
	$question =~ m|(.+)\?( <small>\((or bread box)\)</small>)?| if defined $question;
	($question, $questionsub) = ($1, $3);
	$questionsub = '' if not defined $questionsub;

	die "Invalid topic $question" if defined $question and $question =~ /allowed to talk/;

	# We sent "right", collect feedback on our guesses
	if ($answer eq 'Right') {
		$playing = 0; # game over
print "--> sent RIGHT\n";

		# OUR GUESSES
		while ( my ($id, $value) = each(%$myanswers) ) {
			my $sth = $dbh->prepare('SELECT id FROM questions WHERE name=?');
			$sth->execute(trim($id));
			my @result = $sth->fetchrow_array() or die "Not found \"$id\"";
			my ($yes, $no, $skip) = (0, 0, 0);
			($yes, $no) = (1, 0) if $value eq 'Yes';
			($yes, $no) = (0, 1) if $value eq 'No';
			die "Crazy status $value" if $yes == 0 and $no == 0;
            my $pyes2 = ($yes+1)/($yes+$no+$skip+2)*2*65536;
            my $pno2 = ($no+1)/($yes+$no+$skip+2)*2*65536;
            my $pskip2 = ($skip+1)/($yes+$no+$skip+2)*2*65536;
			my $logpyes2 = log(($yes+1)/($yes+$no+$skip+2)*2)/log(2)*65536;
			my $logpno2 = log(($no+1)/($yes+$no+$skip+2)*2)/log(2)*65536;
			my $logpskip2 = log(($skip+1)/($yes+$no+$skip+2)*2)/log(2)*65536;

print "--> guess is setting: \"$id\" should be \"$value\"\n";
$dbh->do('INSERT IGNORE INTO answers 
              (objectid,questionid,
                 yes,pyes2,pyes2min1,logpyes2,
                 no,pno2,pno2min1,logpno2,
                 skip,pskip2,pskip2min1,logpskip2) 
          VALUES 
              (?,?,?,?,?,?,?,?,?,?,?,?,?,?)', 
          undef, $objectid, $result[0],
          $yes, $pyes2, ($pyes2-65536), $logpyes2,
          $no, $pno2, ($pno2-65536), $logpno2,
          $skip, $pskip2, ($pskip2-65536), $logpskip2);
		}


		while ($reply =~ />([^<>?]+)\? You said ([^,]+), 20Q was taught by other players that the answer is ([^.]+)./ig) {
			my $id    = trim($1);
			my $label = $2;

			my $sth = $dbh->prepare('SELECT id FROM questions WHERE name=?');
			$sth->execute(trim($id));
			my @result = $sth->fetchrow_array() or die "Not found \"$id\"";
			my ($yes, $no, $skip) = (0, 0, 0);
			($yes, $no) = (6, 0) if $3 eq 'Yes';
			($yes, $no) = (0, 6) if $3 eq 'No';
			($yes, $no, $skip) = (1, 1, 3) if $3 eq 'Unknown';
			($yes, $no) = (2, 0) if $3 eq 'Sometimes';
			($yes, $no) = (4, 0) if $3 eq 'Probably';
			($yes, $no) = (0, 4) if $3 eq 'Doubtful';
			die "Crazy status $3" if $yes == 0 and $no == 0;
            my $pyes2 = ($yes+1)/($yes+$no+$skip+2)*2*65536;
            my $pno2 = ($no+1)/($yes+$no+$skip+2)*2*65536;
            my $pskip2 = ($skip+1)/($yes+$no+$skip+2)*2*65536;
			my $logpyes2 = log(($yes+1)/($yes+$no+$skip+2)*2)/log(2)*65536;
			my $logpno2 = log(($no+1)/($yes+$no+$skip+2)*2)/log(2)*65536;
			my $logpskip2 = log(($skip+1)/($yes+$no+$skip+2)*2)/log(2)*65536;

print "--> got feedback: \"$id\" should be \"$3\"\n";
$dbh->do('INSERT INTO answers 
              (objectid,questionid,
                 yes,pyes2,pyes2min1,logpyes2,
                 no,pno2,pno2min1,logpno2,
                 skip,pskip2,pskip2min1,logpskip2) 
          VALUES 
              (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
          ON DUPLICATE KEY UPDATE
              yes=?, pyes2=?, pyes2min1=?, logpyes2=?,
              no=?, pno2=?, pno2min1=?, logpno2=?,
              skip=?, pskip2=?, pskip2min1=?, logpskip2=?', 
          undef, $objectid, $result[0],
          $yes, $pyes2, ($pyes2-65536), $logpyes2,
          $no, $pno2, ($pno2-65536), $logpno2,
          $skip, $pskip2, ($pskip2-65536), $logpskip2,
          $yes, $pyes2, ($pyes2-65536), $logpyes2,
          $no, $pno2, ($pno2-65536), $logpno2,
          $skip, $pskip2, ($pskip2-65536), $logpskip2);
		}

		return;
	}

	# Are they guessing at the answer?
	if ($reply =~ /<a href="\/anon\?(.+?)">Right<\/a>\, <a href="\/anon\?(.+?)">Wrong<\/a>\, <a href="\/anon\?(.+?)"> Close <\/a>.+<br>/i) {
		$answers = [ qw(Right Wrong Close) ];
		$choices = {
			Right => "?$1",
			Wrong => "?$2",
			Close => "?$3",
		};
		$question =~ m|I am guessing that it is ([^<]+[^< ])\s?(<small>\((.+)\)</small>)?|;
		($object, $objectsub) = ($1, $3);
		$objectsub = '' if not defined $objectsub;

$dbh->do('INSERT INTO objects (hits,loghits,name,sub) VALUES (1,?,?,?) ON DUPLICATE KEY UPDATE loghits=LOG2(hits+1)*65536, hits=hits+1', 
         undef, log(1)/log(2)*65536,$object,$objectsub);
$objectid = $dbh->{'mysql_insertid'};

		return;
	}

	# Get the new answers.
	$answers = [];
	$choices = {};
	while ($reply =~ /<a href="\/anon?(.+?)" target="_top">(.+?)<\/a>/ig) {
		my $id    = $1;
		my $label = $2;
		next if $label eq '<font color="#000033"><font size="+3"><b>?</b></font></font>';
		$label =~ s/&nbsp;//g;
		push (@$answers, $label);
		$choices->{$label} = $id;
	}

$dbh->do('INSERT IGNORE INTO questions (name,sub) VALUES (?,?)', 
        undef, $question, $questionsub);
}


# Returns a random valid item from $answers
sub choose {
	my @opts = grep(!/Unknown|Irrelevant|Probably|Doubtful|Wrong|Close/,@$answers);
#print "REAL OPTIONS: ".join(", ",@opts)." -- $ind\n";
	return $opts[rand @opts];
}


  #
  # The full game
  #

  start();  # load first question ("Is it an animal...") and set $number, $question

  while ($playing) {
    my $answer = choose();  # pick random from $answers
	print "Q$number.  \"$question\" -- $answer\n";
    answer ($answer);
  }

  print "Game Over\n";


sub trim($)
{
	my $string = shift;
	$string =~ s/^\s+//;
	$string =~ s/\s+$//;
	return $string;
}


