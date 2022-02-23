CREATE TABLE tx_oauth2_beuser_provider_configuration (
  uid int(10) unsigned NOT NULL AUTO_INCREMENT,
  pid int(10) unsigned NOT NULL DEFAULT 0,
  tstamp int(10) unsigned NOT NULL DEFAULT 0,
  crdate int(10) unsigned NOT NULL DEFAULT 0,
  cruser_id int(10) unsigned NOT NULL DEFAULT 0,
  parentid int(10) DEFAULT 0 NOT NULL,
  provider varchar(255) DEFAULT '',
  identifier varchar(255) DEFAULT '',

  PRIMARY KEY (uid),
);

CREATE TABLE tx_oauth2_feuser_provider_configuration (
  uid int(10) unsigned NOT NULL AUTO_INCREMENT,
  pid int(10) unsigned NOT NULL DEFAULT 0,
  tstamp int(10) unsigned NOT NULL DEFAULT 0,
  crdate int(10) unsigned NOT NULL DEFAULT 0,
  cruser_id int(10) unsigned NOT NULL DEFAULT 0,
  parentid int(11) DEFAULT 0 NOT NULL,
  provider varchar(255) DEFAULT '',
  identifier varchar(255) DEFAULT '',

  PRIMARY KEY (uid),
);

CREATE TABLE be_users (
  tx_oauth2_client_configs int(10) DEFAULT '0' NOT NULL,
);

CREATE TABLE fe_users (
  tx_oauth2_client_configs int(10) DEFAULT '0' NOT NULL,
);
