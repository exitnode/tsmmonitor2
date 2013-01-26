TSM Monitor 2
=============
  
About
-----
  
TSM Monitor 2 is a rewrite of the first version which differs in how it collects it's data. While [[projects:tsmmonitor1:tsmmonitor1|TSM Monitor 1]] polls all needed data live when you navigate through the GUI, this application polls the data all the time, stores it into a MySQL database and queries it from there. Advantages of this are:
  
* you can browse back in time to get all information about your server at any time
* faster querying
* offline access to your data
  
  
:!: WARNING: This Version is still extremely **beta** - you really should use [[TSMMonitor1|TSM Monitor 1]] for now! :!:
  
Screenshots
-----------
  
<gallery>

File:4576df88c0a4270389a7ce9df3d2ac9d.media.900x617.jpg|Overview
File:8249d8fea06b3e4544789fbb8951393b.media.900x652.jpg|Timetable
File:ca3e1a6448d9aa86ead34d41b4696d4c.media.899x692.jpg|Paths
</gallery>
  
Documentation
-------------
  
### Requirements ###
  
#### Unix and Linux ####
  
* PHP5 (Webserver module, CLI, MySQL module) or newer
* Apache 2.x or newer with PHP5-Module enabled
* MySQL 4 or 5 - doesn't really matter
* dsmadmc with all servers listed in your dsm.sys.
* TSM Servers <= v5.5.x (v6.1 is completely untested)
  
#### Windows ####
  
* XAMPP (unless you **really** know how to put together a Apache, PHP and MySQL stack) with the following parts:
** PHP5 (Webserver module, CLI, MySQL module) or newer
** Apache 2.x or newer with PHP5-Module enabled
** MySQL 4 or 5 - doesn't really matter
* dsmadmc with all servers listed in your dsm.opt.
* TSM Servers <= v5.5.x (v6.1 is completely untested)
  
### Installation/Update (Unix) ###
  
#### New Installation ####
  
* Download the newest version of TSM monitor 2
* Extract the package to your htdocs folder: <pre>shell> tar zxvf tsmmonitor-version.tar.gz</pre>
* Make the tsmmonitor files accessible by your Apache/www-User (e.g. 'www-data' user for the Debian Apache2 package): <pre>shell> chown -R www-data:root tsmmonitor/</pre>
* Make your dsmerror.log file writetable to the Apache/www-User(e.g. 'www-data' user for the Debian Apache2 package): <pre>shell> chown www-data:root <path-to-dsmerror.log>/dsmerror.log</pre>
* Create the database (e.g. with MySQL):<pre>shell> mysqladmin --user=root --password create tsmmonitor</pre> Verify the database was successfully created (e.g. with MySQL):
  
<pre>
shell> mysql --user=root --password
mysql> SHOW DATABASES;
+--------------------+
| Database           |
+--------------------+
| information_schema |
| mysql              |
| tsmmonitor         |
+--------------------+
3 rows in set (0.00 sec)
mysql> quit</pre>
  
* Import the default tsmmonitor database (e.g. with MySQL):<pre>shell> mysql --user=root --password tsmmonitor < scripts/tsmmonitor.sql</pre>  
  
* Verify the database was successfully imported (e.g. with MySQL):
  
<pre>shell> mysql --user=root --password tsmmonitor
mysql> SHOW TABLES;
+-------------------------------------------+
| Tables_in_tsmmonitor                      |
+-------------------------------------------+
| cfg_colors                                |
| cfg_config                                |
| cfg_groups                                |
| cfg_mainmenu                              |
| cfg_overviewboxes                         |
| cfg_overviewqueries                       |
| cfg_queries                               |
| cfg_servers                               |
| cfg_users                                 |
| log_hashes                                |
| log_polldstat                             |
+-------------------------------------------+
11 rows in set (0.00 sec)
mysql> quit</pre>
  
* Create a database user and set a password (e.g. with MySQL):
  
<pre>shell> mysql --user=root --password mysql
mysql> CREATE USER 'tsmmonitor'@'localhost' IDENTIFIED BY 'somepassword';
mysql> GRANT ALL PRIVILEGES ON `tsmmonitor`.* TO 'tsmmonitor'@'localhost';
mysql> flush privileges;
mysql> quit</pre> 
  
* Verify the user was successfully created and has the appropriate permissions (e.g. with MySQL):
  
<pre>shell> mysql --user=root --password mysql
mysql> SHOW GRANTS FOR 'tsmmonitor'@'localhost';
+---------------------------------------------------------------------------------------------+
| Grants for tsmmonitor@localhost                                                             |
+---------------------------------------------------------------------------------------------+
| GRANT USAGE ON *.* TO 'tsmmonitor'@'localhost' IDENTIFIED BY PASSWORD '<somepasswordhash>'  |
| GRANT ALL PRIVILEGES ON `tsmmonitor`.* TO 'tsmmonitor'@'localhost'                          |
+---------------------------------------------------------------------------------------------+
2 rows in set (0.00 sec)
mysql> quit
shell> mysql --user=tsmmonitor --password tsmmonitor</pre>
  
* Modify ''includes/config.php'' and specify the database type, name, host, user and password for your tsmmonitor configuration.
<pre>$db_type = 'mysql';             // Name of the DBMS hosting the tsmmonitor database
$db_name = 'tsmmonitor';        // Name of the tsmmonitor database
$db_user = 'tsmmonitor';        // Username used to connect to the tsmmonitor database
$db_password = 'tsmmonitor';    // Password used to connect to the tsmmonitor database
$db_host = 'localhost';         // Hostname or IP address the DBMS is listening on
$db_port = '3306';              // Port number the DBMS is listening on</pre>
  
  
* Point your web browser to http://yourserver/path_to_tsm_monitor/install.php
* Accept the GPL license agreement.
* Choose between a new install and an update from a previous version. Updating is only available from TSM Monitor 2 later than v0.0.1 - **you cannot update from TSM Monitor 1.0 and below!**
* Enter a password for the ''admin'' user. The ''admin'' user is the only initial user for the TSM Monitor application and has full administrative rights.
* Check and if necessary adjust the paths to the PHP and dsmadmc binaries and the logfiles. Select ''Refresh'' to check your input again. The logfile paths can remain empty, in which case the error logging will be to stdout.
* Enter at least one TSM server to query. By clicking ''Add'' the connection to the server will be tested. Select ''Next'' if you have successfully connected to at least one TSM server.
* Review the PHP (CLI and webserver) memory settings. The more data is processed by TSM Monitor, the more memory is needed by PHP. The PHP memory limit should not be below 64MB, 128MB or more are recommended. If you experience PHP out of memory errors from within TSM Monitor adjust your PHP memory limit settings (in php.ini) and restart PHP.
* Done!
      
  
#### Updating ####
  
Updating is only available from TSM Monitor 2 later than v0.0.1 - **you cannot update from TSM Monitor 1.0 and below!**
  
* Optional: Backup your previous TSM Monitor installation and database.
* Follow steps 1 -- 4 of the "New Installation" section.
* Point your browser to http://yourserver/path_to_tsm_monitor/install.php
* Follow steps 10 -- 15 of the "New Installation" section.
* Done!
  
  
### Installation/Update (Windows) ###


#### Preface ####
  
Both TSM Monitor versions are currently developed in Unix/Linux environments. Be aware, that there are probably some features that will emerge first for those platforms. Other features (like the multi-process PollD) will - due to the restrictions of the Windows environment - probably never be available on Windows!
  
TSM Monitor has some requirements for additional software it uses. See '''requirements''' for a complete list. If you have trouble setting up those requirements, we **strongly** advise you to use the XAMPP package available [http://www.apachefriends.org/en/xampp.html here].
  
#### XAMPP Installation (optional) ####
  
* Download the latest version of XAMPP (''xampp-win32-1.7.1-installer.exe'' in this example)
* Run ''xampp-win32-1.7.1-installer.exe''.
* Click ''Next''.
* Choose a install destination. The default destination ''c:\xampp'' is kept in this example. Click ''Next''.
* Select ''Install Apache as a service'' and ''Install MySQL as a service''. Click ''Install''.
* Click ''Finish''.
* The Apache service should now be started. Check the XAMPP control panel and point your browser to ''http:%%//%%<your-ip-address>/''. The XAMPP welcome page should appear.
* Add ''C:\xampp\php'' and ''C:\xampp\mysql\bin'' to the PATH environment variable.
* Open a CMD window and run: <pre>cd \
php -v</pre> Check if the PHP command was found and some version information was displayed. Run <pre>mysql -V</pre> Check if the MySQL command was found and some version information was displayed.
* Set a password for the MySQL database: <pre>mysqladmin --user=root password <your-password></pre>
  
#### TSM Client Installation ####
  
* Download the latest version of the TSM client supported for the TSM servers you want to query.
* Follow the install wizard.
* Choose a install destination. The default destination ''C:\Program Files\Tivoli\TSM\'' is kept in this example. Click ''Next''.
* Select the ''Custom'' setup type. Click ''Next''.
* Select the ''Administrative Client Command Line Files'' option. Click ''Next''.
* Click ''Install''.
* Create a config file for the TSM client in ''C:\Program Files\Tivoli\TSM\baclient\dsm.opt''. Put all the TSM servers you want to query into the dsm.opt. The exact configuration needed depends largely on your environment and is beyond the scope of this document.
* Add ''C:\Program Files\Tivoli\TSM\baclient'' to the PATH environment variable.
* Open a CMD window and run: <pre>cd \
dsmadmc</pre> Check if the ''dsmadmc'' command was found.
* Add the following environment variables to the system: <pre>DSM_DIR       C:\Program Files\Tivoli\TSM\baclient
DSM_CONFIG    C:\Program Files\Tivoli\TSM\baclient\dsm.opt</pre>
* Open a CMD window and type ''set''. Make sure the two environment variables set above show up.
  
#### New Installation ####
  
* Download the newest version of TSM monitor 2
* Extract the package to your htdocs folder: C:\xampp\htdocs</pre> Rename the folder ''tsmmonitor2-<version>'' to ''tsmmonitor''.
* Make the tsmmonitor files accessible by your Apache-User
* Make your dsmerror.log file writetable to the Apache-User
* Create the database (e.g. with MySQL):<pre>mysqladmin --user=root --password create tsmmonitor</pre> Verify the database was successfully created (e.g. with MySQL):
  
<pre>mysql --user=root --password
mysql> SHOW DATABASES;
+--------------------+
| Database           |
+--------------------+
| information_schema |
| cdcol              |
| mysql              |
| phpmyadmin         |
| test               |
| tsmmonitor         |  <--- !!!
| webauth            |
+--------------------+
7 rows in set (0.00 sec)
mysql> quit</pre>
  
  
* Import the default tsmmonitor database (e.g. with MySQL):<pre>mysql --user=root --password tsmmonitor < c:\xampp\htdocs\tsmmonitor\scripts\tsmmonitor.sql</pre> Verify the database was successfully imported (e.g. with MySQL):
<pre>mysql --user=root --password tsmmonitor
mysql> SHOW TABLES;
+-------------------------------------------+
| Tables_in_tsmmonitor                      |
+-------------------------------------------+
| cfg_colors                                |
| cfg_config                                |
| cfg_groups                                |
| cfg_mainmenu                              |
| cfg_overviewboxes                         |
| cfg_overviewqueries                       |
| cfg_queries                               |
| cfg_servers                               |
| cfg_users                                 |
| log_hashes                                |
| log_polldstat                             |
+-------------------------------------------+
11 rows in set (0.00 sec)
mysql> quit</pre>
  
* Create a database user and set a password (e.g. with MySQL):
  
<pre>mysql --user=root --password mysql
mysql> CREATE USER 'tsmmonitor'@'localhost' IDENTIFIED BY 'somepassword';
mysql> GRANT ALL PRIVILEGES ON `tsmmonitor`.* TO 'tsmmonitor'@'localhost';
mysql> flush privileges;
mysql> quit</pre> 
  
* Verify the user was successfully created and has the appropriate permissions (e.g. with MySQL):
  
<pre>mysql --user=root --password mysql
mysql> SHOW GRANTS FOR 'tsmmonitor'@'localhost';
+---------------------------------------------------------------------------------------------+
| Grants for tsmmonitor@localhost                                                             |
+---------------------------------------------------------------------------------------------+
| GRANT USAGE ON *.* TO 'tsmmonitor'@'localhost' IDENTIFIED BY PASSWORD '<somepasswordhash>'  |
| GRANT ALL PRIVILEGES ON `tsmmonitor`.* TO 'tsmmonitor'@'localhost'                          |
+---------------------------------------------------------------------------------------------+
2 rows in set (0.00 sec)
mysql> quit
mysql --user=tsmmonitor --password tsmmonitor
mysql> quit</pre>
  
* Modify ''includes/config.php'' and specify the database type, name, host, user and password for your tsmmonitor configuration.
  
<pre>$db_type = 'mysql';             // Name of the DBMS hosting the tsmmonitor database
$db_name = 'tsmmonitor';        // Name of the tsmmonitor database
$db_user = 'tsmmonitor';        // Username used to connect to the tsmmonitor database
$db_password = 'tsmmonitor';    // Password used to connect to the tsmmonitor database
$db_host = 'localhost';         // Hostname or IP address the DBMS is listening on
$db_port = '3306';              // Port number the DBMS is listening on
</pre>
  
  
* Point your web browser to http://yourserver/path_to_tsm_monitor/install.php
* Accept the GPL license agreement.
* Choose between a new install and an update from a previous version. Updating is only available from TSM Monitor 2 later than v0.0.1 - **you cannot update from TSM Monitor 1.0 and below!**
* Enter a password for the ''admin'' user. The ''admin'' user is the only initial user for the TSM Monitor application and has full administrative rights.
* Check and if necessary adjust the paths to the PHP and dsmadmc binaries and the logfiles.\\ **Attention:** Due to a bug in TSM Monitor 2 v0.0.1 you need to append the program suffix for the PHP and dsmadmc binaries (e.g. ''C:\Program Files\Tivoli\TSM\baclient\dsmadmc**.exe**'')! Sorry for the inconvenience, this will be fixed in v0.0.2 and later versions.\\ Select ''Refresh'' to check your input again. The logfile paths can remain empty, in which case the error logging will be to stdout.
* Enter at least one TSM server to query. By clicking ''Add'' the connection to the server will be tested. Select ''Next'' if you have successfully connected to at least one TSM server.
* Review the PHP (CLI and webserver) memory settings. The more data is processed by TSM Monitor, the more memory is needed by PHP. The PHP memory limit should not be below 64MB, 128MB or more are recommended. If you experience PHP out of memory errors from within TSM Monitor adjust your PHP memory limit settings (in php.ini) and restart PHP.
* Done!
      

#### Updating ####
  
Updating is only available from TSM Monitor 2 later than v0.0.1 - **you cannot update from TSM Monitor 1.0 and below!**

* Optional: Backup your previous TSM Monitor installation and database.
* Follow steps 1 -- 4 of the "New Installation" section.
* Point your browser to http://yourserver/path_to_tsm_monitor/install.php
* Follow steps 10 -- 15 of the "New Installation" section.
* Done!
  
  

### Configuration ###

#### Admin backend ####
  
* Point your browser to http://yourserver/path_to_tsm_monitor/admin.php
* Login with your ''admin'' user and password.
* Add some servers and users FIXME
  
### Collecting Data ###


Since TSM Monitor 2, data is no more queried directly by TSM Monitor (the web application) from your servers' TSM databases. Collecting is now a job of PollD, the TSM Monitor Polling Daemon. This little PHP command line programm runs 24/7, queries the TSM databases and stores the results into it's own MySQL database. The web application itself only speaks with this MySQL database.
  
#### Configuration ####

PollD has no configuration of it's own, it uses the global includes/config.php


#### Usage ####

PollD is located in polld/tmonpolld.php and should be executed like this: FIXME
  
* Linux: ''nohup php tmonpolld &''\\
* Windows: no clue
  
You can check the output by consulting the automatically created nohup.out file
  

#### Frontend ####

  http://yourserver/path_to_tsm_monitor/index.php
  
This is the main application. Here you can access the [[projects:TSMMonitor2:CollectingData|collected data]] and check health statuses.
  
#### Backend ####
  
  http://yourserver/path_to_tsm_monitor/admin.php
  
