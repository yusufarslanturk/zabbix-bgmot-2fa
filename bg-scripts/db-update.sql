ALTER TABLE config ADD COLUMN twofa_type INT(11) DEFAULT 0;
ALTER TABLE config ADD COLUMN twofa_duo_api_hostname TINYTEXT;
ALTER TABLE config ADD COLUMN twofa_duo_integration_key TINYTEXT;
ALTER TABLE config ADD COLUMN twofa_duo_secret_key TINYTEXT;
ALTER TABLE config ADD COLUMN twofa_duo_a_key TINYTEXT;

ALTER TABLE users ADD COLUMN ggl_secret VARCHAR(100) DEFAULT '' NOT NULL;
ALTER TABLE users ADD COLUMN ggl_enrolled INT DEFAULT 0 NOT NULL;
