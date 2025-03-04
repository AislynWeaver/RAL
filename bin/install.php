#!/usr/bin/php
<?php $ROOT = "../";
include "{$ROOT}includes/config.php";
include "{$ROOT}includes/ResourceManager.php";

$RM = new RAL\ResourceManager();
$dbh = $RM->getdb();

if (!$dbh) {
	print "Error connecting to database :(\n";
	print "Please check your DB credentials\n";
	return -1;
}

$CONFIG_RAL_SERVER = CONFIG_RAL_SERVER;
$CONFIG_RAL_USERNAME = CONFIG_RAL_USERNAME;
$CONFIG_RAL_DATABASE = CONFIG_RAL_DATABASE;

print <<<CONFIRM
Your databse options in includes/config.php are
Server: $CONFIG_RAL_SERVER 
User: $CONFIG_RAL_USERNAME
Pasword: (omitted)
Databse: $CONFIG_RAL_DATABASE

CONFIRM;

$queries[] = <<<SQL
	CREATE DATABASE IF NOT EXISTS `$CONFIG_RAL_DATABASE`
SQL;

$queries[] = <<<SQL
	CREATE TABLE IF NOT EXISTS `Continuities` (
	`Name` varchar(16) NOT NULL,
	`Post Count` int(11) DEFAULT 0,
	`Description` varchar(32) DEFAULT NULL,
	PRIMARY KEY (`Name`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;
$queries[] = <<<SQL
	CREATE TABLE IF NOT EXISTS `Topics` (
	`Id` int(11) NOT NULL,
	`Created` datetime DEFAULT current_timestamp(),
	`Continuity` varchar(16) NOT NULL,
	`Content` text NOT NULL DEFAULT '',
	`Replies` int(11) DEFAULT 0,
	`Year` int(4) NOT NULL DEFAULT year(`Created`),
	`User` VARCHAR(64),
	`Deleted` bit DEFAULT 0 NOT NULL,
	PRIMARY KEY (`Continuity`,`Year`,`Id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;
$queries[] = <<<SQL
	CREATE TABLE IF NOT EXISTS `Replies` (
	`Id` int(11) NOT NULL,
	`Created` datetime DEFAULT current_timestamp(),
	`Continuity` varchar(16) NOT NULL,
	`Year` int(4) NOT NULL DEFAULT year(`Created`),
	`Topic` int(11) NOT NULL,
	`Content` text NOT NULL DEFAULT '',
	`ContentId` int(11) NOT NULL AUTO_INCREMENT,
	`User` VARCHAR(64) DEFAULT '',
	`UserIdentity` VARCHAR(64) DEFAULT '',
	`Deleted` TINYINT(1) DEFAULT 0 NOT NULL,
	`LearnedAsSpam` TINYINT(1) NOT NULL DEFAULT 0,
	`IsSpam` TINYINT(1) NOT NULL DEFAULT 0,
	`Visible` bit(1) GENERATED ALWAYS AS
		(`IsSpam` = 0 and `Deleted` = 0) VIRTUAL,
	PRIMARY KEY (`Continuity`,`Year`,`Topic`,`Id`),
	UNIQUE KEY `ContentId` (`ContentId`),
	FULLTEXT KEY `index_name` (`Content`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;
$queries[] = <<<SQL
	CREATE TABLE IF NOT EXISTS `Friends` (
	`Username` varchar(30) NOT NULL,
	`Password` varchar(255) NOT NULL,
	`Birthday` char(5) NOT NULL,
	`Consequence` varchar(20) NOT NULL DEFAULT 'Co-sysop',
	`Joined` datetime DEFAULT current_timestamp(),
	`Password Expiry` datetime DEFAULT (current_timestamp() + interval 1 year),
	PRIMARY KEY (`Username`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;
$queries[] = <<<SQL
	CREATE TABLE IF NOT EXISTS `Sessions` (
	`Id` varchar(64) NOT NULL,
	`Username` varchar(30) NOT NULL,
	PRIMARY KEY (`Id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;
$queries[] = <<<SQL
	CREATE TABLE IF NOT EXISTS `Bans` (
	`Id` VARCHAR(256) NOT NULL,
	`Type` VARCHAR(64) NOT NULL,
	`Date` DATETIME DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`Id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;
$queries[] = <<<SQL
	CREATE TABLE IF NOT EXISTS `News` (
		`Id` int(11) NOT NULL AUTO_INCREMENT,
		`Created` datetime DEFAULT current_timestamp(),
		`Author` varchar(40) NOT NULL,
		`Email` varchar(100) DEFAULT NULL,
		`TITLE` varchar(100) NOT NULL,
		`Content` varchar(5000) NOT NULL,
		PRIMARY KEY (`Id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;

// Set up the SQL schema
foreach ($queries as $q) {
	if (!$dbh->query($q)) {
		printf("MySQL Error: %s\n", $dbh->error);
		printf("Please resolve this before continuing!\n");
		// exit(1);
	}
}

// Download and install jBBCode to the location
$installfolder = realpath("{$ROOT}includes");
if (is_dir("{$installfolder}/jBBCode")) {
	print "It seems like you already have jBBCode installed!\n";
} else {
	print "Downloading and installing jBBCode-1.3.0\n";
	$zipfilepath = "jBBCode-1.3.0/JBBCode";
	$tmp = realpath("{$ROOT}tmp/");
	$relevpath = "{$tmp}/$zipfilepath";

	$installto = escapeshellarg("{$installfolder}/jBBCode");
	$tmpfile = escapeshellarg("{$tmp}/jbbcode-1.3.0.zip");
	$tmp = escapeshellarg($tmp);
	$cmd = <<<CMD
	curl https://web.archive.org/web/20160130150030/http://jbbcode.com/file/jbbcode-1.3.0.zip --output \ 
	$tmpfile
CMD; //This link is broken.
	system($cmd);
	$cmd = <<<CMD
	apt install unzip -y
	unzip -q $tmpfile -d $tmp ; \
	mv $relevpath $installto ; \
	rm $tmpfile ; \
	rm -r {$tmp}/jBBCode-1.3.0
CMD;
	system($cmd);
} if (is_dir("{$installfolder}/b8")) {
	print "It seems like you aleady have b8 installed!\n";
} else {
	print "Downloadng b8-0.6.2...\n";
	$zipfilepath = "b8-0.6.2/b8";
	$tmp = realpath("{$ROOT}tmp/");
	$relevpath = "{$tmp}/$zipfilepath";

	$installto = escapeshellarg("{$installfolder}/b8");
	$tmpfile = escapeshellarg("{$tmp}/b8-0.6.2.tar.xz");
	$tmp = escapeshellarg($tmp);
	$cmd = <<<CMD
	curl https://nasauber.de/opensource/b8/download/b8-0.6.2.tar.xz --output \ 
	$tmpfile
CMD; //This link is very broken.
	system($cmd);
	$cmd = <<<CMD
	tar xvf $tmpfile -C $tmp ; \
	mv $relevpath $installto ; \
	rm $tmpfile ; \
	rm -r {$tmp}/b8-0.6.2
	patch -p0 -d $installto < ${ROOT}patch/b8-abspath-fix.patch
	patch -p0 -d $installto < ${ROOT}patch/b8-sync.patch
CMD;
	system($cmd);
}
dba_close(dba_open(CONFIG_SPAM_DB, "c")); //This throws and error for some reason.

print <<<FIN
Finished!

FIN;
