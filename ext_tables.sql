CREATE TABLE tx_oauth2_client_configs (
	parentid   int(11)      DEFAULT '0' NOT NULL,
	parenttable   VARCHAR(255) DEFAULT '',
	provider   VARCHAR(255) DEFAULT '',
	identifier VARCHAR(255) DEFAULT '',
);

CREATE TABLE be_users (
	tx_oauth2_client_configs int(11) DEFAULT '0' NOT NULL,
);

CREATE TABLE fe_users (
	tx_oauth2_client_configs int(11) DEFAULT '0' NOT NULL,
);
