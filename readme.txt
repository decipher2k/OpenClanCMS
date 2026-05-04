OpenClanCMS is a fork of clansphere. Sadly it seems to be impossible to contact the devs, and the project seems to be abandoned.

Changelog:
-Fixed several dozen security flaws
  -SQL prepared statements
  -CSRF tokens
  -Password hashing using salted Argon2id
  -Added hCaptcha support
  -[...]
-Migrated to PHP 8.x
-Cookie banner
-Legal texts pages (empty)

The patch for clansphere_2011.4.4-r2 can be found in complete-security-audit.patch

*The ClanSphere Readme*


  _____ _              _____       _                   
 / ____| |            / ____|     | |                  
| |    | | __ _ _ __ | (___  _ __ | |__   ___ _ __ ___ 
| |    | |/ _` | '_ \ \___ \| '_ \| '_ \ / _ \ '__/ _ \
| |____| | (_| | | | |____) | |_) | | | |  __/ | |  __/
 \_____|_|\__,_|_| |_|_____/| .__/|_| |_|\___|_|  \___|
                            | |                        
                            |_|   Professional clan care starts here

-------------------------------------------------------------------------------

ClanSphere is a web portal software for clans, guilds, but also other groups.

It is developed in PHP and runs on Apache, Lighttpd, Microsoft IIS and Nginx.

Data storage requires one of MySQL, PostgreSQL, SQLite or Microsoft SQLSRV.

The current version is available under the terms of the 'New BSD License'.

Our project website is located at http://www.clansphere.net

Look into '/docs' and '/webserver' for further documentation.
