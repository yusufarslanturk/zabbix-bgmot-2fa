ALTER TABLE config ADD COLUMN 2fa_type INT(11) DEFAULT 0;
ALTER TABLE config ADD COLUMN 2fa_duo_api_hostname VARCHAR(255) DEFAULT '';
ALTER TABLE config ADD COLUMN 2fa_duo_integration_key VARCHAR(255) DEFAULT '';
ALTER TABLE config ADD COLUMN 2fa_duo_secret_key VARCHAR(255) DEFAULT '';
ALTER TABLE config ADD COLUMN 2fa_duo_a_key CHAR(40) DEFAULT '';

CREATE TABLE `adusrgrp` (
        `adusrgrpid`             bigint unsigned                           NOT NULL,
        `name`                   varchar(64)     DEFAULT ''                NOT NULL,
        `user_type`              integer         DEFAULT '1'               NOT NULL,
        PRIMARY KEY (adusrgrpid)
) ENGINE=InnoDB;
CREATE UNIQUE INDEX `adusrgrp_1` ON `adusrgrp` (`name`);

CREATE TABLE `adgroups_groups` (
        `id`                     bigint unsigned                           NOT NULL,
        `usrgrpid`               bigint unsigned                           NOT NULL,
        `adusrgrpid`             bigint unsigned                           NOT NULL,
        PRIMARY KEY (id)
) ENGINE=InnoDB;
CREATE UNIQUE INDEX `adgroups_groups_1` ON `adgroups_groups` (`usrgrpid`,`adusrgrpid`);
CREATE INDEX `adgroups_groups_2` ON `adgroups_groups` (`adusrgrpid`);
ALTER TABLE `adgroups_groups` ADD CONSTRAINT `c_adgroups_groups_1` FOREIGN KEY (`usrgrpid`) REFERENCES `usrgrp` (`usrgrpid`) ON DELETE CASCADE;
ALTER TABLE `adgroups_groups` ADD CONSTRAINT `c_adgroups_groups_2` FOREIGN KEY (`adusrgrpid`) REFERENCES `adusrgrp` (`adusrgrpid`) ON DELETE CASCADE;
