CREATE TABLE `objects` (
  `id` smallint(6) NOT NULL,
  `name` varchar(30) NOT NULL,
  `sub` varchar(30) NOT NULL,
  `hits` smallint(6) NOT NULL,
  `hidden` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `questions` (
  `id` smallint(6) NOT NULL,
  `name` varchar(30) NOT NULL,
  `sub` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `answers` (
  `objectid` smallint(6) NOT NULL,
  `questionid` smallint(6) NOT NULL,
  `yes` smallint(6) NOT NULL,
  `no` smallint(6) NOT NULL,
  `skip` smallint(6) NOT NULL,
  `pyes2` int NOT NULL,     -- ((yes+1)/(yes+no+skip+2))*2*65536
  `pyes2min1` int NOT NULL, -- ((yes+1)/(yes+no+skip+2))*2*65536-65536
  `logpyes2` int NOT NULL,  -- log2(((yes+1)/(yes+no+skip+2))*2)*65536
  `pno2` int NOT NULL,
  `pno2min1` int NOT NULL,
  `logpno2` int NOT NULL,
  `pskip2` int NOT NULL,
  `pskip2min1` int NOT NULL,
  `logpskip2` int NOT NULL,
  PRIMARY KEY (`objectid`,`questionid`),
  UNIQUE KEY (`questionid`,`objectid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `log` (
  `id` int NOT NULL,
  `host` int NOT NULL,
  `date` datetime NOT NULL,
  `objectid` smallint(6) NOT NULL,
  `answers` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
