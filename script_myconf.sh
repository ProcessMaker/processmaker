# Mysql configuration

sudo echo "[mysqld]" > /etc/mysql/conf.d/my.cnf
sudo echo "back_log = 16000" >> /etc/mysql/conf.d/my.cnf
sudo echo "connect_timeout = 31536000" >> /etc/mysql/conf.d/my.cnf
sudo echo "explicit_defaults_for_timestamp = ON" >> /etc/mysql/conf.d/my.cnf
sudo echo "host_cache_size = 653" >> /etc/mysql/conf.d/my.cnf
sudo echo "innodb_adaptive_hash_index = OFF" >> /etc/mysql/conf.d/my.cnf
sudo echo "innodb_change_buffering = none" >> /etc/mysql/conf.d/my.cnf
sudo echo "innodb_checksum_algorithm = none" >> /etc/mysql/conf.d/my.cnf
sudo echo "innodb_checksums = OFF" >> /etc/mysql/conf.d/my.cnf
sudo echo "innodb_doublewrite = OFF" >> /etc/mysql/conf.d/my.cnf
sudo echo "innodb_flush_method = O_DIRECT" >> /etc/mysql/conf.d/my.cnf
sudo echo "innodb_open_files = 6000" >> /etc/mysql/conf.d/my.cnf
sudo echo "innodb_page_cleaners = 2" >> /etc/mysql/conf.d/my.cnf
sudo echo "innodb_purge_batch_size = 600" >> /etc/mysql/conf.d/my.cnf
sudo echo "innodb_purge_threads = 1" >> /etc/mysql/conf.d/my.cnf
sudo echo "innodb_stats_persistent_sample_pages = 128" >> /etc/mysql/conf.d/my.cnf
sudo echo "innodb_use_native_aio = OFF" >> /etc/mysql/conf.d/my.cnf
sudo echo "interactive_timeout = 31536000" >> /etc/mysql/conf.d/my.cnf
sudo echo "max_allowed_packet = 1073741824" >> /etc/mysql/conf.d/my.cnf
sudo echo "max_connections = 1000" >> /etc/mysql/conf.d/my.cnf
sudo echo "max_execution_time = 18446744073709551615" >> /etc/mysql/conf.d/my.cnf
sudo echo "myisam_recover_options = OFF" >> /etc/mysql/conf.d/my.cnf
sudo echo "open_files_limit = 65535" >> /etc/mysql/conf.d/my.cnf
sudo echo "performance_schema = OFF" >> /etc/mysql/conf.d/my.cnf
sudo echo "query_cache_size = 464717824" >> /etc/mysql/conf.d/my.cnf
sudo echo "query_cache_type = ON" >> /etc/mysql/conf.d/my.cnf
sudo echo "skip_name_resolve = ON" >> /etc/mysql/conf.d/my.cnf
sudo echo "table_definition_cache = 20000" >> /etc/mysql/conf.d/my.cnf
sudo echo "table_open_cache_instances = 4" >> /etc/mysql/conf.d/my.cnf

sudo service mysql restart