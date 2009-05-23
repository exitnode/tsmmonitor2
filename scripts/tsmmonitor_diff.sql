use tsmmonitor;
# Make name consistent with other _24h queries
UPDATE cfg_queries SET name='archives_24h' WHERE name='archives_24';
# Allow longer confvals e.g. file paths
ALTER TABLE cfg_config MODIFY confval varchar(255) collate utf8_unicode_ci NOT NULL;  
# Add description
UPDATE cfg_config SET description='TSM Monitor version' WHERE confkey='version';
# Add new configuration entries
INSERT INTO cfg_config (confkey,confval,description) VALUES ('path_tmlog','','TSM Monitor Logfile Path');
INSERT INTO cfg_config (confkey,confval,description) VALUES ('path_polldlog','','PollD Logfile Path');
INSERT INTO cfg_config (confkey,confval,description) VALUES ('loglevel_tm','INFO','TSM Monitor Log Level');
INSERT INTO cfg_config (confkey,confval,description) VALUES ('loglevel_polld','INFO','PollD Log Level');

