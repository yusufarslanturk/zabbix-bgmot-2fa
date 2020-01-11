#!/bin/bash

set -x
set -e

DB_USERNAME=zabbix
DB_PASSWORD=zabbix
TMPDIR=/tmp/`date +%s`
VERSION=4.4.4
BGVERSION=4.4.4-bg-2fa
ZABBIX_INSTALL_PATH=/usr/share/zabbix
ZABBIX_INSTALLED_VERSION=`grep ZABBIX_VERSION ${ZABBIX_INSTALL_PATH}/include/defines.inc.php | cut -d\' -f 4`

if [ "$VERSION" != "$ZABBIX_INSTALLED_VERSION" ]
then
  echo "Need Zabbix version ${VERSION} to be able to patch, found ${ZABBIX_INSTALLED_VERSION}"
  exit -1
fi

mkdir $TMPDIR && cd $TMPDIR
curl -L -o zabbix-${BGVERSION}.zip https://github.com/BGmot/zabbix/archive/${BGVERSION}.zip
unzip zabbix-${BGVERSION}.zip
cd zabbix-${BGVERSION}

# Check if DB is already patched
echo 'show tables' | mysql -u $DB_USERNAME -p${DB_PASSWORD} zabbix | grep adusrgrp
if [ "$?" -eq "0" ]
then
  echo 'Database already patched. No changes to DB will be made'
else
  echo 'Patching DB...'
  mysql -u $DB_USERNAME -p${DB_PASSWORD} zabbix < bg-scripts/db-update.sql
  if [ "$?" -eq "0" ]
  then
    echo 'Database was successfully patched.'
  else
    echo 'Failure to patch Database. Aborting...'
    exit -1
  fi
fi

# Copy new files
echo 'Patching WebUI files...'
cd frontends/php
cp 2fa.php ${ZABBIX_INSTALL_PATH}/
cp duo.php ${ZABBIX_INSTALL_PATH}/
cp adusergrps.php ${ZABBIX_INSTALL_PATH}/
cp assets/styles/Duo-Frame.css ${ZABBIX_INSTALL_PATH}/assets/styles/
cp js/Duo-Web-v2.js ${ZABBIX_INSTALL_PATH}/js
cp ${ZABBIX_INSTALL_PATH}/index.php  ${ZABBIX_INSTALL_PATH}/index.php-`date +%s`.bak
cp index.php ${ZABBIX_INSTALL_PATH}/
cd include
cp ${ZABBIX_INSTALL_PATH}/include/audit.inc.php  ${ZABBIX_INSTALL_PATH}/include/audit.inc.php-`date +%s`.bak
cp audit.inc.php ${ZABBIX_INSTALL_PATH}/include/
cp ${ZABBIX_INSTALL_PATH}/include/defines.inc.php ${ZABBIX_INSTALL_PATH}/include/defines.inc.php-`date +%s`.bak
cp defines.inc.php ${ZABBIX_INSTALL_PATH}/include/
cp ${ZABBIX_INSTALL_PATH}/include/menu.inc.php ${ZABBIX_INSTALL_PATH}/include/menu.inc.php-`date +%s`.bak
cp menu.inc.php ${ZABBIX_INSTALL_PATH}/include
cp ${ZABBIX_INSTALL_PATH}/include/perm.inc.php ${ZABBIX_INSTALL_PATH}/include/perm.inc.php-`date +%s`.bak
cp perm.inc.php ${ZABBIX_INSTALL_PATH}/include
cp ${ZABBIX_INSTALL_PATH}/include/schema.inc.php ${ZABBIX_INSTALL_PATH}/include/schema.inc.php-`date +%s`.bak
cp schema.inc.php ${ZABBIX_INSTALL_PATH}/include
cd classes/api
cp ${ZABBIX_INSTALL_PATH}/include/classes/api/API.php ${ZABBIX_INSTALL_PATH}/include/classes/api/API.php-`date +%s`.bak
cp API.php ${ZABBIX_INSTALL_PATH}/include/classes/api/
cp ${ZABBIX_INSTALL_PATH}/include/classes/api/CApiServiceFactory.php ${ZABBIX_INSTALL_PATH}/include/classes/api/CApiServiceFactory.php-`date +%s`.bak
cp CApiServiceFactory.php ${ZABBIX_INSTALL_PATH}/include/classes/api/
cp ${ZABBIX_INSTALL_PATH}/include/classes/api/CAudit.php ${ZABBIX_INSTALL_PATH}/include/classes/api/CAudit.php-`date +%s`.bak
cp CAudit.php ${ZABBIX_INSTALL_PATH}/include/classes/api/
cd services
cp CAdUserGroup.php ${ZABBIX_INSTALL_PATH}/include/classes/api/services/
cp ${ZABBIX_INSTALL_PATH}/include/classes/api/services/CUser.php ${ZABBIX_INSTALL_PATH}/include/classes/api/services/CUser.php-`date +%s`.bak
cp CUser.php ${ZABBIX_INSTALL_PATH}/include/classes/api/services/
cp ${ZABBIX_INSTALL_PATH}/include/classes/api/services/CUserGroup.php ${ZABBIX_INSTALL_PATH}/include/classes/api/services/CUserGroup.php-`date +%s`.bak
cp CUserGroup.php ${ZABBIX_INSTALL_PATH}/include/classes/api/services/
cd ../../
mkdir ${ZABBIX_INSTALL_PATH}/include/classes/duo/ && chmod a+rx ${ZABBIX_INSTALL_PATH}/include/classes/duo/
cp duo/CDuoWeb.php ${ZABBIX_INSTALL_PATH}/include/classes/duo/
cp ${ZABBIX_INSTALL_PATH}/include/classes/ldap/CLdap.php ${ZABBIX_INSTALL_PATH}/include/classes/ldap/CLdap.php-`date +%s`.bak
cp ldap/CLdap.php ${ZABBIX_INSTALL_PATH}/include/classes/ldap/
cp ${ZABBIX_INSTALL_PATH}/include/classes/validators/CLdapAuthValidator.php ${ZABBIX_INSTALL_PATH}/include/classes/validators/CLdapAuthValidator.php-`date +%s`.bak
cp validators/CLdapAuthValidator.php ${ZABBIX_INSTALL_PATH}/include/classes/validators/
cd ../views/
cp -r administration.adusergroups.*  ${ZABBIX_INSTALL_PATH}/include/views/
cp administration.twofa.edit.php ${ZABBIX_INSTALL_PATH}/include/views/
cp general.duo.php ${ZABBIX_INSTALL_PATH}/include/views/
echo 'Done! Reload your browser to see changes.'
