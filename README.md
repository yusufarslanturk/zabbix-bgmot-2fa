## Zabbix repository from BGmot

**This README is an addition to official [README from Zabbix](README.orig)**

### What it is and why?
This repository is a fork of official [Zabbix repository](https://github.com/zabbix/zabbix.git)
The code in this repository is always based on official code from Zabbix with two features aded:
- two factor authentication (2FA) using DUO provider
- LDAP authentication with AD groups - an attempt to address [ZBXNEXT-276](https://support.zabbix.com/browse/ZBXNEXT-276)

Detailed HowTos:
- https://bgmot.com/zabbix_twofa_duo
- https://bgmot.com/zabbix_ldap_groups

### 2FA
With 2FA enabled when you login into WebUI after 'in-stock' authentication (internal, LDAP or whatever is configured in your case) is successfully done the user is presented with DUO's interface to perform authentication via an SMS, DUO app push, or a phone call. For details please go to [DUO website](https://duo.com), you may also want to read about [details of implementation](https://duo.com/docs/duoweb).
To enable 2FA go to Administration -> Authentication -> Two factor authentication, toggle DUO and fill in all the fields with data you get from DUO when you register an account. To fill in '40 characters long custom key' field you need to generate this custiom key, in python use following code:
```
import os, hashlib
print hashlib.sha1(os.urandom(32)).hexdigest()
```

In case you lockout yourself (enable 2FA but for some reason cannot go through DUO's authentication) you need to manually turn off 2FA.  As a Zabbix server you should have access to database so execute following code:
```
echo 'update config set 2fa_type=0;' | mysql -u <db_zabbix_user> -p<db_zabbix_password> zabbix
```

### LDAP authentication with LDAP(ActiveDirectory) groups
Problem: with large installation a request to give access to Zabbix to all members of some LDAP group is quite common but currently you need to manually (or via API) add every user.
My solution: create Zabbix internal User Groups and just map LDAP groups to these internal groups.
1) LDAP authentication is selected as 'Default authentication' at Administration -> Authentication.
2) Zabbix Administrator creates mappings 'LDAP group' to 'Zabbix User Group(s)' at Administration -> LDAP Groups in WebUI. For every LDAP group a 'User type' is defined (User/Admin/Super Admin).
In my case I see membership information as an array of records with format 'CN=<cn_name>,OU=<ouX>,OU=<ouY>...etc'. We use only CN field to map groups.
3) If a user logs in and it does exist in internal Zabbix database (Administration -> Users) then no change in behaviour - it is authenticated against LDAP server.
3) If a user logs in and does not exist in internal Zabbix dataase (Administration -> Users) then:
3.1) Zabbix performs authentication against LDAP server (password verification).
3.2) Zabbix pulls the user's LDAP groups membership information from LDAP server.
3.3) Zabbix compares groups received in 3.2) to internal mappings created in 2) and compiles a list of internal Zabbix User Groups.
3.4) If no LDAP group found then authentication fails.
3.5) A user is created belonging to Zabbix User Groups found in 3.3) with 'User type' defined for matched 'LDAP group'. If multiple LDAP Groups found then the highest level of 'User type' applied.

### Installation
Very quick way to see this code in action is to deploy a Docker container using this image:
- [zabbix-appliance-ubuntu](https://hub.docker.com/repository/docker/bgmot42/zabbix-appliance-ubuntu)

There are two ways to install this code:
1. fresh install from sources using this repository, follow [this guide](https://www.zabbix.com/documentation/current/manual/installation/install)
2. if you already have Zabbix server installed (WebUI and MySQL DB on the same host) then login into the Zabbix server as `root`, download this script (pay attention that version of your Zabbix Server - 5.2.5 in this example - matches this repository version/tag - 5.2.6-bg):
```
curl -L -o bg-features-install.sh https://github.com/BGmot/zabbix/raw/5.2.6-bg/bg-scripts/bg-features-install.sh
```
Modify the script to provide proper values for DB_HOST, DB_USERNAME, DB_PASSWORD and ZABBIX_INSTALL_PATH (where all your php files located) and run the script:
```
sudo bash bg-features-install.sh
```
