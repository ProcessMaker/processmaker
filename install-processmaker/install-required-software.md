---
description: Install required software and ProcessMaker 4 Community edition.
---

# Install Required Software and ProcessMaker 4

The following instructions describe how to install required software and ProcessMaker on CentOS Linux 7.x. However, ProcessMaker 4 can be installed on any Linux distribution that supports PHP 7.2.

## Install MySQL Community Server Edition

### Remove MariaDB

{% hint style="info" %}
CentOS 7.x comes with MariaDB modules by default. Remove MariaDB to prevent conflicts with MySQL.
{% endhint %}

{% code-tabs %}
{% code-tabs-item title="Remove MariaDB." %}
```text
yum -y remove mariadb*
```
{% endcode-tabs-item %}
{% endcode-tabs %}

### Install MySQL Community Server

Install the program `yum-utils` to use repositories and add the following repository:

{% code-tabs %}
{% code-tabs-item title="1. Add the repository." %}
```text
yum install -y yum-utils
yum localinstall -y https://repo.mysql.com//mysql57-community-release-el7-11.noarch.rpm
```
{% endcode-tabs-item %}
{% endcode-tabs %}

After installing the repository, install the MySQL server using the following command:

{% code-tabs %}
{% code-tabs-item title="2. Install MySQL Community Server." %}
```text
yum install -y mysql-community-server
```
{% endcode-tabs-item %}
{% endcode-tabs %}

After the installation is complete, start and enable the server to be active:

{% code-tabs %}
{% code-tabs-item title="3. Start the MySQL service and set it to start when the server starts." %}
```text
systemctl start mysqld
systemctl enable mysqld
```
{% endcode-tabs-item %}
{% endcode-tabs %}

Sometimes MySQL comes with a default password already set. Use the following command to get the default password:

{% code-tabs %}
{% code-tabs-item title="4. Get the root password." %}
```text
grep "temporary password" /var/log/mysqld.log
```
{% endcode-tabs-item %}
{% endcode-tabs %}

Configure MySQL and complete the wizard below:

{% code-tabs %}
{% code-tabs-item title="5. Change the root password." %}
```text
mysql_secure_installation
```
{% endcode-tabs-item %}
{% endcode-tabs %}

After you run the previous command a wizard displays:

![MySQL wizard to configure the MySQL database](../.gitbook/assets/3.1centos68mysqlsecure0.png)

If there was a password set you need to enter that password. Otherwise press **Enter** to continue: 

![Enter the password or press \[Enter\] to continue](../.gitbook/assets/3.1centos68mysqlsecure01.png)

The wizard asks you if you want to change the current password. Choose yes and change the password:

![Change the current database password](../.gitbook/assets/3.1centos68mysqlsecure1.png)

{% hint style="warning" %}
ProcessMaker does NOT support special characters \(such as: `@ # $ % ^ & () /`\) in the root password.
{% endhint %}

The wizard asks you if you want to remove anonymous users. Choose yes.

![Remove anonymous users](../.gitbook/assets/3.1centos68mysqlsecure02.png)

The wizard asks you if you want to disable the root login. Choose yes.

![Disable the root login](../.gitbook/assets/3.1centos68mysqlsecure03.png)

{% hint style="info" %}
If MySQL is in another server, you must create a new user and give that user the permissions to access.
{% endhint %}

The wizard asks you if you want to remove the test database. Choose yes.

![Remove the test database](../.gitbook/assets/3.1centos68mysqlsecure04.png)

The wizard asks you if you want to reload the privilege tables. Choose yes.

![Reload the privilege tables](../.gitbook/assets/3.1centos68mysqlsecure06.png)

After you finish using the wizard restart the MySQL service.

{% code-tabs %}
{% code-tabs-item title="Restart the MySQL service." %}
```text
service mysql restart
```
{% endcode-tabs-item %}
{% endcode-tabs %}

## Install the Web Server, PHP and PHP-FPM

Install one of the following web server applications along with PHP and PHP-FPM.

{% tabs %}
{% tab title="Apache 2.4.x, PHP and PHP-FPM" %}
{% hint style="info" %}
Follow the commands below to install Apache web server along with PHP and PHP-FPM.

Click the **NGINX 1.x, PHP and PHP-FPM** tab for commands to install NGINX web server along with PHP and PHP-FPM.
{% endhint %}

### Install Apache 2.4.x

Install the default Apache version that ships with CentOS Linux. Furthermore, install the SSL module to run using HTTPS protocols:

{% code-tabs %}
{% code-tabs-item title="1. Install Apache 2.4.x and the SSL module using HTTPS protocols." %}
```text
yum -y install httpd mod_ssl
```
{% endcode-tabs-item %}
{% endcode-tabs %}

Set standard ProcessMaker configurations.

{% code-tabs %}
{% code-tabs-item title="2. Set standard ProcessMaker configurations." %}
```text
sed -i 's@#LoadModule expires_module modules/mod_expires.so@LoadModule expires_module modules/mod_expires.so@' /etc/httpd/conf/httpd.conf ;
sed -i 's@#LoadModule rewrite_module modules/mod_rewrite.so@LoadModule rewrite_module modules/mod_rewrite.so@' /etc/httpd/conf/httpd.conf ;
sed -i 's@#LoadModule deflate_module modules/mod_deflate.so@LoadModule deflate_module modules/mod_deflate.so@' /etc/httpd/conf/httpd.conf ;
sed -i 's@#LoadModule vhost_alias_module modules/mod_vhost_alias.so@LoadModule vhost_alias_module modules/mod_vhost_alias.so@' /etc/httpd/conf/httpd.conf ;
sed -i 's@#LoadModule filter_module modules/mod_filter.so@LoadModule filter_module modules/mod_filter.so@' /etc/httpd/conf/httpd.conf ;
```
{% endcode-tabs-item %}
{% endcode-tabs %}

Start the Apache service and set it to automatically start.

{% code-tabs %}
{% code-tabs-item title="3. Start the Apache service and set it to automatically start." %}
```text
systemctl start httpd
systemctl enable httpd
```
{% endcode-tabs-item %}
{% endcode-tabs %}

### Install PHP 7.2.x

Add the corresponding EPEL repository to download the required PHP version:

{% code-tabs %}
{% code-tabs-item title="1. Add the corresponding EPEL repository to download the required PHP version." %}
```text
rpm -Uvh https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
rpm -Uvh https://mirror.webtatic.com/yum/el7/webtatic-release.rpm
```
{% endcode-tabs-item %}
{% endcode-tabs %}

Install PHP and all the extensions that ProcessMaker needs:

{% code-tabs %}
{% code-tabs-item title="2. Install PHP and all the extensions that ProcessMaker needs." %}
```text
yum -y install php72w
yum -y install php72w-cli php72w-gd php72w-mysqlnd php72w-soap php72w-mbstring php72w-ldap php72w-mcrypt php72w-xml php72w-devel php72w-pecl-apcu php72w-fpm php72w-opcache
```
{% endcode-tabs-item %}
{% endcode-tabs %}

Set standard ProcessMaker configurations:

{% code-tabs %}
{% code-tabs-item title="3. Set standard ProcessMaker configurations." %}
```text
sed -i '/short_open_tag = Off/c\short_open_tag = On' /etc/php.ini
sed -i '/post_max_size = 8M/c\post_max_size = 24M' /etc/php.ini
sed -i '/upload_max_filesize = 2M/c\upload_max_filesize = 24M' /etc/php.ini
sed -i '/;date.timezone =/c\date.timezone = America/New_York' /etc/php.ini
sed -i '/expose_php = On/c\expose_php = Off' /etc/php.ini
```
{% endcode-tabs-item %}
{% endcode-tabs %}

### PHP-FPM Configuration File

Start the service and configuration:

{% code-tabs %}
{% code-tabs-item title="1. Start the service and configuration." %}
```text
systemctl start php-fpm
systemctl enable php-fpm
```
{% endcode-tabs-item %}
{% endcode-tabs %}

Create the configuration file.

{% code-tabs %}
{% code-tabs-item title="2. Create the configuration file." %}
```text
vi /etc/php-fpm.d/processmaker.conf
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="3. Insert the following configuration file content at /etc/php-fpm.d/processmaker.conf." %}
```text
[processmaker]
user = apache
group = apache
listen = /var/run/php-fpm/processmaker.sock
listen.mode = 0664
listen.owner = apache
listen.group = apache
pm = dynamic
pm.max_children = 100 
pm.start_servers = 20
pm.min_spare_servers = 20
pm.max_spare_servers = 50
pm.max_requests = 500
php_admin_value[error_log] = /var/log/php-fpm/processmaker-error.log
php_admin_flag[log_errors] = on
```
{% endcode-tabs-item %}
{% endcode-tabs %}

### OpCache Configuration

{% code-tabs %}
{% code-tabs-item title="Configure OpCache." %}
```text
sed -i '/;opcache.enable_cli=0/c\opcache.enable_cli=1' /etc/php.d/opcache.ini
sed -i '/opcache.max_accelerated_files=4000/c\opcache.max_accelerated_files=10000' /etc/php.d/opcache.ini
sed -i '/;opcache.max_wasted_percentage=5/c\opcache.max_wasted_percentage=5' /etc/php.d/opcache.ini
sed -i '/;opcache.use_cwd=1/c\opcache.use_cwd=1' /etc/php.d/opcache.ini
sed -i '/;opcache.validate_timestamps=1/c\opcache.validate_timestamps=1' /etc/php.d/opcache.ini
sed -i '/;opcache.fast_shutdown=0/c\opcache.fast_shutdown=1' /etc/php.d/opcache.ini
```
{% endcode-tabs-item %}
{% endcode-tabs %}
{% endtab %}

{% tab title="NGINX 1.x, PHP and PHP-FPM" %}
{% hint style="info" %}
Follow the commands below to install NGINX web server and PHP-FPM.

Click the **Apache 2.4.x, PHP and PHP-FPM** tab for commands to install Apache web server along with PHP and PHP-FPM.
{% endhint %}

### Install NGINX 1.0

Create the repository: 

{% code-tabs %}
{% code-tabs-item title="1. Add the NGINX repository file." %}
```text
vi /etc/yum.repos.d/nginx.repo
```
{% endcode-tabs-item %}
{% endcode-tabs %}

After creating the repository, add the following lines and save the file:

{% code-tabs %}
{% code-tabs-item title="2. Add the following lines in the /etc/yum.repos.d/nginx.repo file." %}
```text
[nginx]
name=nginx repo
#####rhel/6 should be changed to rhel/7 for RHEL/CentOS 7
baseurl=http://nginx.org/packages/rhel/7/$basearch/
gpgcheck=0
enabled=1
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% hint style="info" %}
Ensure that the repository file is in the path `/etc/yum.repos.d/`. Otherwise, you cannot install NGINX.
{% endhint %}

Install NGINX and start the service:

{% code-tabs %}
{% code-tabs-item title="3. Install NGINX and start the service." %}
```text
yum clean all && yum -y install nginx
systemctl start nginx
systemctl enable nginx
```
{% endcode-tabs-item %}
{% endcode-tabs %}

### Install PHP, PHP-FPM and OpCache

{% hint style="info" %}
If your Linux distribution has PHP 7.2, skip to the command 3 \(Start the service and configuration\).
{% endhint %}

Add the following repositories:

{% code-tabs %}
{% code-tabs-item title="1. Add the EPEL \(CentOS 7.x\) or Red Hat repositories to install PHP." %}
```text
rpm -Uvh https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
rpm -Uvh https://mirror.webtatic.com/yum/el7/webtatic-release.rpm
```
{% endcode-tabs-item %}
{% endcode-tabs %}

After the repositories are added, install PHP and its modules:

{% code-tabs %}
{% code-tabs-item title="2. Install PHP and its modules." %}
```text
yum -y install php72w php72w-cli php72w-opcache php72w-fpm php72w-gd php72w-mysqlnd php72w-soap php72w-mbstring php72w-ldap php72w-mcrypt php72w-xml
```
{% endcode-tabs-item %}
{% endcode-tabs %}

Start the PHP-FPM module:

{% code-tabs %}
{% code-tabs-item title="3. Start the service and configuration." %}
```text
systemctl start php-fpm
systemctl enable php-fpm
```
{% endcode-tabs-item %}
{% endcode-tabs %}

Set PHP ready for the ProcessMaker installation:

{% code-tabs %}
{% code-tabs-item title="4. Configure PHP-FPM using standard ProcessMaker settings for validation." %}
```text
sed -i '/short_open_tag = Off/c\short_open_tag = On' /etc/php.ini
sed -i '/post_max_size = 8M/c\post_max_size = 24M' /etc/php.ini
sed -i '/upload_max_filesize = 2M/c\upload_max_filesize = 24M' /etc/php.ini
sed -i '/;date.timezone =/c\date.timezone = America/New_York' /etc/php.ini
sed -i '/expose_php = On/c\expose_php = Off' /etc/php.ini
```
{% endcode-tabs-item %}
{% endcode-tabs %}

### OpCache Configuration

{% code-tabs %}
{% code-tabs-item title="Configure OpCache." %}
```text
sed -i '/;opcache.enable_cli=0/c\opcache.enable_cli=1' /etc/php.d/opcache.ini
sed -i '/opcache.max_accelerated_files=4000/c\opcache.max_accelerated_files=10000' /etc/php.d/opcache.ini
sed -i '/;opcache.max_wasted_percentage=5/c\opcache.max_wasted_percentage=5' /etc/php.d/opcache.ini
sed -i '/;opcache.use_cwd=1/c\opcache.use_cwd=1' /etc/php.d/opcache.ini
sed -i '/;opcache.validate_timestamps=1/c\opcache.validate_timestamps=1' /etc/php.d/opcache.ini
sed -i '/;opcache.fast_shutdown=0/c\opcache.fast_shutdown=1' /etc/php.d/opcache.ini
```
{% endcode-tabs-item %}
{% endcode-tabs %}

### PHP-FPM Configuration File

{% code-tabs %}
{% code-tabs-item title="1. Create the configuration file for /etc/php-fpm.d/processmaker.conf." %}
```text
vi /etc/php-fpm.d/processmaker.conf
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="2. Insert the following configuration file content at /etc/php-fpm.d/processmaker.conf." %}
```text
[processmaker]
user = nginx
group = nginx
listen = /var/run/php-fpm/processmaker.sock
listen.mode = 0664
listen.owner = nginx
listen.group = nginx
pm = dynamic
pm.max_children = 100 
pm.start_servers = 20
pm.min_spare_servers = 20
pm.max_spare_servers = 50
pm.max_requests = 500
php_admin_value[error_log] = /var/log/php-fpm/processmaker-error.log
php_admin_flag[log_errors] = on
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="3. Create the NGINX configuration to work with ProcessMaker." %}
```text
mv /etc/nginx/nginx.conf /etc/nginx/nginx.conf.bk
vi /etc/nginx/nginx.conf
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="4. Below is the contents of the configuration file for /etc/nginx/nginx.conf." %}
```text
user nginx;
 
worker_processes auto;
 
error_log  /var/log/nginx/error.log warn;
 
pid       /var/run/nginx.pid;
 
# Load dynamic modules. See /usr/share/nginx/README.dynamic.
 
include /usr/share/nginx/modules/*.conf;
 
events {
  worker_connections 1024;
}
 
http {
  include        /etc/nginx/mime.types;
  default_type   application/octet-stream;
  log_format     main '$remote_addr - $remote_user [$time_local] "$request" '
                      '$status $body_bytes_sent "$http_referer" '
                      '"$http_user_agent" "$http_x_forwarded_for"';
  access_log  /var/log/nginx/access.log  main;
  log_format combined_ssl '$remote_addr - $remote_user [$time_local] '
                          '$ssl_protocol/$ssl_cipher '
                          '"$request" $status $body_bytes_sent '
                          '"$http_referer" "$http_user_agent"';
  sendfile            on;
  tcp_nopush          on;
  tcp_nodelay         on;
  keepalive_timeout   120;
  keepalive_requests  100;
  types_hash_max_size 2048;
 
  #Enable Compression
  gzip on;
  gzip_disable "msie6";
  gzip_vary on;
  gzip_proxied any;
  gzip_comp_level 6;
  gzip_buffers 16 8k;
  gzip_http_version 1.1;
  gzip_types text/css text/plain text/xml text/x-component text/javascript application/x-javascript application/javascript application/json application/xml application/xhtml+xml application/x-font-ttf application/x-font-opentype application/x-font-truetype image/svg+xml image/x-icon image/vnd.microsoft.icon font/ttf font/eot font/otf font/opentype;
 
  include /etc/nginx/conf.d/*.conf;
  
 
#Comment out ServerTokens OS
  server_tokens off;
 
  #Prevent ClickJacking Attacks
  add_header X-Frame-Options SAMEORIGIN;
 
  #Load Balancer/Reverse Proxy Header
  real_ip_header X-Forwarded-For;
  set_real_ip_from 0.0.0.0/0;
}
```
{% endcode-tabs-item %}
{% endcode-tabs %}
{% endtab %}
{% endtabs %}

## Install Docker Community Edition \(CE\)

Ensure that you meet [Docker CE requirements](https://docs.docker.com/install/linux/docker-ce/centos/#os-requirements) before installing Docker CE.

Install Docker.

{% code-tabs %}
{% code-tabs-item title="1. Install Docker." %}
```text
yum -y install docker
```
{% endcode-tabs-item %}
{% endcode-tabs %}

Start the service and make it persistent.

{% code-tabs %}
{% code-tabs-item title="2. Start the service and make it persistent." %}
```text
systemctl start docker
systemctl enable docker
```
{% endcode-tabs-item %}
{% endcode-tabs %}

## Install Composer

{% code-tabs %}
{% code-tabs-item title="Install Composer." %}
```text
yum -y install composer
```
{% endcode-tabs-item %}
{% endcode-tabs %}

## Disable SELinux

{% hint style="info" %}
Disable SELinux if you intend to install ProcessMaker at `/opt`. If you intend to install ProcessMaker at `/etc/www/` then skip to [Install the Firewall](install-required-software.md#install-the-firewall).

{% code-tabs %}
{% code-tabs-item title="Disable SELinux." %}
```text
echo "SELINUX=disabled" > /etc/selinux/config
echo "SELINUXTYPE=targeted" >> /etc/selinux/config
```
{% endcode-tabs-item %}
{% endcode-tabs %}
{% endhint %}

## Install the Firewall

{% hint style="info" %}
CentOS Linux 7 requires a firewall.
{% endhint %}

{% code-tabs %}
{% code-tabs-item title="1. Install the firewall." %}
```text
yum -y install firewalld
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="2. Set the firewall to automatically start." %}
```text
systemctl start firewalld
systemctl enable firewalld
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="3. Open the port through which ProcessMaker will run. By default use port 80." %}
```text
firewall-cmd --zone=public --add-port=6001/tcp --permanent
firewall-cmd --zone=public --add-port=80/tcp --permanent
firewall-cmd --reload
```
{% endcode-tabs-item %}
{% endcode-tabs %}

## Virtual Host Configuration

Create the virtual host that corresponds with the [web server application you installed](install-required-software.md#install-the-web-server-php-and-php-fpm).

{% tabs %}
{% tab title="Apache 2.4.x" %}
{% hint style="info" %}
Create the following virtual host only if you installed the Apache web server.

Click the **NGINX 1.x** tab if you installed NGINX web server.
{% endhint %}

{% code-tabs %}
{% code-tabs-item title="1. Create the virtual host." %}
```text
vi /etc/httpd/conf.d/processmaker.conf
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="2. Insert the following configuration file content at /etc/httpd/conf.d/processmaker.conf." %}
```text
<VirtualHost *:80>
    ServerName 172.16.0.72
 
    DocumentRoot /opt/processmaker/public
    DirectoryIndex index.php index.html
 
    <Directory /opt/processmaker/public>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Order allow,deny
        Allow from all
        Require all granted
 
        ExpiresActive On
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^(.*)/$ /$1 [L,R=301]
            RewriteCond %{HTTP:Authorization} .
            RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteRule ^ index.php [L]
        </IfModule>
    </Directory>
    #PHP-FPM
    <FilesMatch "\.php">
            SetHandler "proxy:unix:/var/run/php-fpm/processmaker.sock|fcgi://localhost"
    </FilesMatch>
 
</VirtualHost>
```
{% endcode-tabs-item %}
{% endcode-tabs %}
{% endtab %}

{% tab title="NGINX 1.x" %}
{% hint style="info" %}
Create the following virtual host only if you installed the NGINX web server.

Click the **Apache 2.4.x** tab if you installed Apache web server.
{% endhint %}

{% code-tabs %}
{% code-tabs-item title="1. Create the virtual host." %}
```text
vi /etc/nginx/conf.d/processmaker.conf
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="2. Insert the following configuration file content at /etc/nginx/conf.d/processmaker.conf." %}
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
{% endtab %}
{% endtabs %}

## Download the ProcessMaker 4 Installer

{% code-tabs %}
{% code-tabs-item title="1. Download the ProcessMaker 4 installer inside /opt/." %}
```text
wget https://github.com/ProcessMaker/bpm/releases/download/beta1/bpm-beta1.tar.gz
```
{% endcode-tabs-item %}
{% endcode-tabs %}

## Untar the ProcessMaker 4 Installer

Follow the procedure to untar the ProcessMaker 4 installer based on which [web server application you installed](install-required-software.md#install-the-web-server-php-and-php-fpm).

{% tabs %}
{% tab title="Apache 2.4.x" %}
{% hint style="info" %}
Follow the commands below to untar the ProcessMaker 4 installer only if you installed the Apache web server.

Click the **NGINX 1.x** tab if you installed NGINX web server.
{% endhint %}

{% code-tabs %}
{% code-tabs-item title="Untar the ProcessMaker 4 installer." %}
```text
tar -xzvf bpm4_version.tar.gz
#change the folder name to processmaker
mv bpm_version processmaker
#then change the ownership to apache
chown -R apache:apache processmaker
```
{% endcode-tabs-item %}
{% endcode-tabs %}
{% endtab %}

{% tab title="NGINX 1.x" %}
{% hint style="info" %}
Follow the commands below to untar the ProcessMaker 4 installer only if you installed the NGINX web server.

Click the **Apache 2.4.x** tab if you installed Apache web server.
{% endhint %}

{% code-tabs %}
{% code-tabs-item title="Untar the ProcessMaker 4 installer." %}
```text
tar -xzvf bpm4_version.tar.gz
#change the folder name to processmaker
mv bpm_version processmaker
#then change the ownership to nginx
chown -R nginx:nginx processmaker
```
{% endcode-tabs-item %}
{% endcode-tabs %}
{% endtab %}
{% endtabs %}

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

After you provide the batch script with your database information, the script installs the ProcessMaker database. An example of the script output is below.

{% code-tabs %}
{% code-tabs-item title="Batch script output that installs the ProcessMaker database with your database information." %}
```text
ProcessMaker Installer
This application installs a new version of ProcessMaker.
You must have your database credentials available in order to continue.
 
 Are you ready to begin? (yes/no) [no]:
 > y
 
Dependencies Check
+---------------------+--------+
| PHP Version         | 7.2.12 |
| OpenSSL Extension   | 7.2.12 |
| PDO Extension       | 7.2.12 |
| PDO MySQL Extension | 7.2.12 |
| mbstring Extension  | 7.2.12 |
| Tokenizer Extension | 7.2.12 |
| XML Extension       | 7.2.12 |
| CType Extension     | 7.2.12 |
| JSON Extension      | 1.6.0  |
| GD Extension        | 7.2.12 |
| SOAP Extension      | 7.2.12 |
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

## Reboot the Server

After installing the above software successfully, reboot the server.

## Log In for the First Time

After rebooting the server, go to [https://localhost](https://localhost) or the IP address/domain name you specified. Use the following credentials to [log in](../using-processmaker/log-in.md#log-in):

* Username: `admin`
* Password: `admin`

After you log in for the first time, you may begin managing [user accounts](../processmaker-administration/add-users/manage-user-accounts/) and [groups](../processmaker-administration/assign-groups-to-users/manage-groups/).

## Related Topics

{% page-ref page="prerequisites.md" %}

{% page-ref page="../using-processmaker/log-in.md" %}

{% page-ref page="../processmaker-administration/add-users/manage-user-accounts/" %}

{% page-ref page="../processmaker-administration/assign-groups-to-users/manage-groups/" %}

