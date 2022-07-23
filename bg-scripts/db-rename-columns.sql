ALTER TABLE config
RENAME COLUMN 2fa_type TO twofa_type,
RENAME COLUMN 2fa_duo_api_hostname TO twofa_duo_api_hostname,
RENAME COLUMN 2fa_duo_integration_key TO twofa_duo_integration_key,
RENAME COLUMN 2fa_duo_secret_key TO twofa_duo_secret_key,
RENAME COLUMN 2fa_duo_a_key TO twofa_duo_a_key;
