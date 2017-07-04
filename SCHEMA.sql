CREATE TABLE `questions` (
	`questionid`	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
	`name`	TEXT NOT NULL,
	`subname`	TEXT
);
CREATE TABLE "logs" (
	`id`	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
	`host`	TEXT NOT NULL,
	`datetime`	TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`objectid`	INTEGER NOT NULL,
	`answers`	TEXT NOT NULL
);
CREATE TABLE "objects" (
	`objectid`	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
	`name`	TEXT NOT NULL,
	`subname`	TEXT,
	`hits`	INTEGER NOT NULL DEFAULT 0,
	`visible`	INTEGER NOT NULL DEFAULT 0,
	`calc_logl`	REAL NOT NULL
);
CREATE TABLE "answers" (
	`objectid`	INTEGER NOT NULL,
	`questionid`	INTEGER NOT NULL,
	`yes`	INTEGER NOT NULL,
	`no`	INTEGER NOT NULL,
	`skip`	INTEGER NOT NULL,
	`calc_y3lmin1`	REAL NOT NULL,
	`calc_n3lmin1`	REAL NOT NULL,
	`calc_s3lmin1`	REAL NOT NULL,
	`calc_y3lll`	REAL NOT NULL,
	`calc_n3lll`	REAL NOT NULL,
	`calc_s3lll`	REAL NOT NULL,
	`calc_y3ll`	REAL NOT NULL,
	`calc_n3ll`	REAL NOT NULL,
	`calc_s3ll`	REAL NOT NULL,
	PRIMARY KEY(`objectid`,`questionid`)
);
CREATE UNIQUE INDEX `questions_name` ON `questions` (`name` ,`subname` );
CREATE UNIQUE INDEX `objects_name` ON `objects` (`name` ,`subname` )
