---
description: Install ProcessMaker 4 Community edition on-premises.
---

# Install ProcessMaker

Ensure that you have reviewed ProcessMaker [requirements](prerequisites.md) and [installed the required software](install-required-software.md) before installing ProcessMaker.

## Virtual Host Configuration

Create the virtual host that corresponds with the [web server application you installed](install-required-software.md#install-the-web-server-application):

* [Apache](install-processmaker.md#virtual-host-configuration-for-apache)
* [NGINX + PHP-FPM](install-processmaker.md#virtual-host-configuration-for-nginx-php-fpm)

### Virtual Host Configuration for Apache

{% hint style="info" %}
Create the following virtual host only if you [installed the Apache web server](install-required-software.md#apache).
{% endhint %}



### Virtual Host Configuration for NGINX + PHP-FPM

{% hint style="info" %}
Create the following virtual host only if you [installed the NGINX + PHP-FPM web server](install-required-software.md#install-nginx-php-fpm).
{% endhint %}

{% code-tabs %}
{% code-tabs-item title="Create the virtual host." %}
```text
vi /etc/nginx/conf.d/processmaker.conf
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="Below is the content for /etc/nginx/conf.d/processmaker.conf" %}
```text
server {
    listen 80;
  #  listen 443 ssl http2;
    server_name 172.16.0.65; #use your server name or IP address
    root "/opt/processmaker/public"; #where processmkaker is installed if not in www SELINUX needs to be disabled
 
    index index.html index.htm index.php;
 
    charset utf-8;
 
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
 
 
 
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
 
    access_log off;
    error_log  /var/log/nginx/pm4-error.log error;
 
    sendfile off;
 
    client_max_body_size 100m;
 
    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php-fpm/processmaker.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
 
 
        fastcgi_intercept_errors off;
        fastcgi_buffer_size 16k;
        fastcgi_buffers 4 16k;
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
        fastcgi_read_timeout 300;
    }
    location ~ /\.ht {
        deny all;
    }
 
 #   ssl_certificate     /etc/nginx/ssl/bpm4.local.processmaker.com.crt;
 #   ssl_certificate_key /etc/nginx/ssl/bpm4.local.processmaker.com.key;
}
```
{% endcode-tabs-item %}
{% endcode-tabs %}

## Download and Untar the ProcessMaker 4 Installer

{% code-tabs %}
{% code-tabs-item title="Download the ProcessMaker 4 installer inside /opt/." %}
```text
wget https://github.com/ProcessMaker/bpm/releases/download/beta1/bpm-beta1.tar.gz
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="Untar the ProcessMaker 4 installer." %}
```text
tar -xvf bpm4_version.tar.gz
#change the folder name to processmaker
mv bpm_version processmaker
#then change the ownership to nginx
chown -R nginx:nginx processmaker
```
{% endcode-tabs-item %}
{% endcode-tabs %}

## Install the ProcessMaker Database

{% hint style="info" %}
Ensure the following prior to performing the following command:

* You have [MySQL Community Edition installed](install-required-software.md#install-mysql-community-server-edition).
* You have your database credentials available.
{% endhint %}

{% code-tabs %}
{% code-tabs-item title="Install the ProcessMaker database." %}
```text
#inside processmaker execute
php artisan bpm:install
```
{% endcode-tabs-item %}
{% endcode-tabs %}

The batch script asks you for the following information to create the ProcessMaker database:

* Database credentials
* Database port number
* Database name
* Database username
* Database password
* IP address or domain name to install the database \(`https://localhost` is the recommended default\)

After you provide the batch script with your database information, the script installs the ProcessMaker database. The script is below.

{% code-tabs %}
{% code-tabs-item title="Batch script that installs the ProcessMaker database with your database information." %}
```text
ProcessMaker Installer
This application installs a new version of ProcessMaker.
You must have your database credentials available in order to continue.
 
 Are you ready to begin? (yes/no) [no]:
 > y
 
Dependencies Check
+---------------------+--------+
| PHP Version         | 7.1.24 |
| OpenSSL Extension   | 7.1.24 |
| PDO Extension       | 7.1.24 |
| PDO MySQL Extension | 7.1.24 |
| mbstring Extension  | 7.1.24 |
| Tokenizer Extension | 7.1.24 |
| XML Extension       | 7.1.24 |
| CType Extension     | 7.1.24 |
| JSON Extension      | 1.5.0  |
| GD Extension        | 7.1.24 |
| SOAP Extension      | 7.1.24 |
+---------------------+--------+
ProcessMaker requires a MySQL database created with appropriate credentials configured.
Refer to the Installation Guide for more information on database best practices.
 
 Enter your MySQL host:
 > localhost
 
 Enter your MySQL port (Usually 3306):
 > 3306
 
 Enter your MySQL Database name:
 > pm4
 
 Enter your MySQL Username:
 > root
 
 Enter your MySQL Password (Input hidden):
 >
 
 What is the url of this ProcessMaker Installation? (Ex: https://pm.example.com, no trailing slash):
 > http://<ip address or domain name>
 
Installing ProcessMaker Database, OAuth SSL Keys and configuration file
Dropped all tables successfully.
Migration table created successfully.
Migrating: 2014_10_12_000000_create_users_table
Migrated:  2014_10_12_000000_create_users_table
Migrating: 2014_10_12_100000_create_password_resets_table
Migrated:  2014_10_12_100000_create_password_resets_table
Migrating: 2016_06_01_000001_create_oauth_auth_codes_table
Migrated:  2016_06_01_000001_create_oauth_auth_codes_table
Migrating: 2016_06_01_000002_create_oauth_access_tokens_table
Migrated:  2016_06_01_000002_create_oauth_access_tokens_table
Migrating: 2016_06_01_000003_create_oauth_refresh_tokens_table
Migrated:  2016_06_01_000003_create_oauth_refresh_tokens_table
Migrating: 2016_06_01_000004_create_oauth_clients_table
Migrated:  2016_06_01_000004_create_oauth_clients_table
Migrating: 2016_06_01_000005_create_oauth_personal_access_clients_table
Migrated:  2016_06_01_000005_create_oauth_personal_access_clients_table
Migrating: 2018_08_21_174540_create_environment_variables_table
Migrated:  2018_08_21_174540_create_environment_variables_table
Migrating: 2018_09_07_154851_create_media_table
Migrated:  2018_09_07_154851_create_media_table
Migrating: 2018_09_07_161956_create_process_categories_table
Migrated:  2018_09_07_161956_create_process_categories_table
Migrating: 2018_09_07_170019_create_process_table
Migrated:  2018_09_07_170019_create_process_table
Migrating: 2018_09_07_171508_create_screens_table
Migrated:  2018_09_07_171508_create_screens_table
Migrating: 2018_09_07_173703_create_process_collaborations_table
Migrated:  2018_09_07_173703_create_process_collaborations_table
Migrating: 2018_09_07_173804_create_screen_versions_table
Migrated:  2018_09_07_173804_create_screen_versions_table
Migrating: 2018_09_07_174154_create_process_requests_table
Migrated:  2018_09_07_174154_create_process_requests_table
Migrating: 2018_09_07_174216_create_scripts_table
Migrated:  2018_09_07_174216_create_scripts_table
Migrating: 2018_09_07_174703_create_script_versions_table
Migrated:  2018_09_07_174703_create_script_versions_table
Migrating: 2018_09_07_175156_create_process_versions_table
Migrated:  2018_09_07_175156_create_process_versions_table
Migrating: 2018_09_07_180640_create_process_request_tokens
Migrated:  2018_09_07_180640_create_process_request_tokens
Migrating: 2018_09_07_180801_create_groups_table
Migrated:  2018_09_07_180801_create_groups_table
Migrating: 2018_09_07_180830_create_process_task_assignments
Migrated:  2018_09_07_180830_create_process_task_assignments
Migrating: 2018_09_07_180903_create_group_members_table
Migrated:  2018_09_07_180903_create_group_members_table
Migrating: 2018_09_10_170636_create_permissions_table
Migrated:  2018_09_10_170636_create_permissions_table
Migrating: 2018_09_10_204130_create_permission_assignments_table
Migrated:  2018_09_10_204130_create_permission_assignments_table
Migrating: 2018_10_24_201610_create_notifications_table
Migrated:  2018_10_24_201610_create_notifications_table
Migrating: 2018_10_24_231951_create_screen_categories_table
Migrated:  2018_10_24_231951_create_screen_categories_table
Migrating: 2018_11_22_231951_create_process_permissions_table
Migrated:  2018_11_22_231951_create_process_permissions_table
Seeding: UserSeeder
Seeding: ProcessSeeder
Creating: /opt/processmaker/database/processes/LeaveAbsenceRequest.bpmn
Seeding: PermissionSeeder
Database seeding completed successfully.
Encryption keys generated successfully.
Personal access client created successfully.
Client ID: 2
Client Secret: ltf87IQEPTaxMHXOacSKDaY9LiAu6ALGNntlochy
Password grant client created successfully.
Client ID: 3
Client Secret: 40iqFM7yd6nPFK6GojqcjqgGZCoUTVUFxTBKhGwT
ProcessMaker installation is complete. Please visit the url in your browser to continue.
```
{% endcode-tabs-item %}
{% endcode-tabs %}

## Log In to ProcessMaker

After ProcessMaker is installed, go to [https://localhost](https://localhost) or the IP address you specified. Use the following credentials to [log in](../using-processmaker/log-in.md):

* Username: `admin`
* Password: `admin`

## Related Topics

{% page-ref page="prerequisites.md" %}

{% page-ref page="install-required-software.md" %}

{% page-ref page="../using-processmaker/log-in.md" %}

