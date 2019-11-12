## Why this project?

This pepository is a fork of official Zabbix repository https://github.com/zabbix/zabbix.git. It aims to address [LDAP authentication with groups support](https://support.zabbix.com/browse/ZBXNEXT-276).

## Implementation

1) LDAP authentication is selected as 'Default authentication' at Administration->Authentication.
2) Zabbix Administrator creates mappings 'AD group' to 'Zabbix User Group(s)' at Administration->AD Groups in WebUI. For every AD group a 'User type' is defined (User/Admin/Super Admin).
In my case I see membership information as an array of records with format 'CN=<cn_name>,OU=<ouX>,OU=<ouY>...etc'. We use only CN field to map groups.
3) If a user logs in and it does exist in internal Zabbix database (Administration->Users) then no change in behaviour - it is authenticated against LDAP server.
3) If a user logs in and does not exist in internal Zabbix dataase (Administration->Users) then:
3.1) Zabbix performs authentication against LDAP server (password verification).
3.2) Zabbix pulls the user's AD groups membership information from LDAP server.
3.3) Zabbix compares groups received in 3.2) to internal mappings created in 2) and compiles a list of internal Zabbix User Groups.
3.4) If no AD group found authentication fails.
3.5) A user is created belonging to Zabbix User Groups found in 3.3) with 'User type' defined for matched 'AD group'. If multiple AD Groups found then the highest level of 'User type' applied.

## Usage

To use the feature implemented by this Repository you must install Zabbix server from sources. Follow this [instructions]() but in step '1 Download the source archive' download from this link https://github.com/BGmot/zabbix/archive/release/4.2-bg.zip

## Issues

If you have any problems with or questions about this image, please contact us through a [GitHub issues](https://github.com/BGmot/zabbix/issues).

