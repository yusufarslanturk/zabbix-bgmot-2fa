ALTER TABLE config ADD COLUMN twofa_type integer DEFAULT '0';
ALTER TABLE config ADD COLUMN twofa_duo_api_hostname varchar(64);
ALTER TABLE config ADD COLUMN twofa_duo_integration_key varchar(64);
ALTER TABLE config ADD COLUMN twofa_duo_secret_key varchar(64);
ALTER TABLE config ADD COLUMN twofa_duo_a_key varchar(64);

ALTER TABLE users ADD COLUMN ggl_secret varchar(100) DEFAULT '' NOT NULL;
ALTER TABLE users ADD COLUMN ggl_enrolled integer DEFAULT '0' NOT NULL;
