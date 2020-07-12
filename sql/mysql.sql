CREATE TABLE xmobile_session (
session_id varchar(32) NOT NULL default '',
uid mediumint(8) unsigned NOT NULL default '0',
subscriber_id varchar(40) NOT NULL default '',
ip_address varchar(15) NOT NULL default '',
php_session_id varchar(32) NOT NULL default '',
last_access int(10) unsigned NOT NULL default '0',
user_agent varchar(255) NOT NULL default '',
PRIMARY KEY (session_id)
) TYPE=MyISAM;


CREATE TABLE xmobile_subscriber (
subscriber_id varchar(40) NOT NULL default '',
uid mediumint(8) unsigned NOT NULL default '0',
created int(10) unsigned NOT NULL default '0',
PRIMARY KEY (subscriber_id)
) TYPE=MyISAM;