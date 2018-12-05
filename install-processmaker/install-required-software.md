---
description: Install required software before installing ProcessMaker 4 Community edition.
---

# Install Required Software

The following instructions describe how to install ProcessMaker required software on CentOS Linux 7.x. However, ProcessMaker 4 can be installed on any Linux distribution that supports PHP 7.2.

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

After the installation is complete, start and enable the server to be active in any moment:

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

Run the following command to configure MySQL and complete the requests:

{% code-tabs %}
{% code-tabs-item title="5. Change the root password." %}
```text
mysql_secure_installation
```
{% endcode-tabs-item %}
{% endcode-tabs %}

~~Document how to set up the password that will be used in the ProcessMaker installation.~~

## Install the Web Server Application

Install one of the following web server applications.

{% tabs %}
{% tab title="Apache" %}

{% endtab %}

{% tab title="NGINX + PHP-FPM" %}
### Install NGINX

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

## Install Docker Community Edition

Follow Docker's guide to [install Docker Community Edition \(CE\) for CentOS](https://docs.docker.com/install/linux/docker-ce/centos/). Ensure that you meet [Docker CE requirements](https://docs.docker.com/install/linux/docker-ce/centos/#os-requirements).

{% hint style="info" %}
## Disable SELinux

Disable SELinux if you intend to install ProcessMaker at `/opt`. If you intend to install ProcessMaker at `/etc/www/` then skip to [Install Firewall](install-required-software.md#install-firewall).

{% code-tabs %}
{% code-tabs-item title="Disable SELinux." %}
```text
nano /etc/selinux/config
 
#### Change the value
 
SELINUX=enforcing to disabled
 
#then reboot the whole server
 
reboot -h 0
```
{% endcode-tabs-item %}
{% endcode-tabs %}
{% endhint %}

## Install Firewall

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
{% code-tabs-item title="2. Set the firewall to auto-start." %}
```text
systemctl start firewalld
systemctl enable firewalld
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="3. Open the port through which ProcessMaker will run. By default use port 80." %}
```text
firewall-cmd --zone=public --add-port=3306/tcp --permanent
firewall-cmd --zone=public --add-port=6001/tcp --permanent
firewall-cmd --zone=public --add-port=80/tcp --permanent
firewall-cmd --reload
```
{% endcode-tabs-item %}
{% endcode-tabs %}

## Reboot the Server

After installing the above software successfully, reboot the server.

Continue to [Install ProcessMaker](install-processmaker.md).

## Related Topics

{% page-ref page="prerequisites.md" %}

{% page-ref page="install-processmaker.md" %}



