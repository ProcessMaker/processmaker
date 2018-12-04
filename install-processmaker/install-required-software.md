---
description: Install required software before installing ProcessMaker 4 Community edition.
---

# Install Required Software

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

{% code-tabs %}
{% code-tabs-item title="Install MySQL Community Server." %}
```text
yum install -y yum-utils
yum localinstall -y https://repo.mysql.com//mysql57-community-release-el7-11.noarch.rpm 
yum install -y mysql-community-server
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="Start the MySQL service and set it to start when the server starts." %}
```text
systemctl start mysqld
systemctl enable mysqld
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="Get the root password." %}
```text

grep "temporary password" /var/log/mysqld.log
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="Change the root password." %}
```text
mysql_secure_installation
```
{% endcode-tabs-item %}
{% endcode-tabs %}

Document how to set up the password that will be used in the ProcessMaker installation.

## Install the Web Server Application

Install one of the following web server applications:

* Apache
* [NGINX + PHP-FPM](install-required-software.md#install-nginx-php-fpm)

### Apache



### Install NGINX + PHP-FPM

#### Install NGINX

{% code-tabs %}
{% code-tabs-item title="Add the NGINX repository file." %}
```text
vi /etc/yum.repos.d/nginx.repo
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="Add the following lines in the /etc.yum.repos.d/nginx.repo file." %}
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

{% code-tabs %}
{% code-tabs-item title="Install NGINX and start the service." %}
```text
yum clean all && yum -y install nginx
systemctl start nginx
systemctl enable nginx
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="Add the EPEL \(CentOS 7.x\) or Red Hat repositories to install php. The following commands add EPEL repos." %}
```text
rpm -Uvh https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
rpm -Uvh https://mirror.webtatic.com/yum/el7/webtatic-release.rpm
yum -y install php72w php72w-cli php72w-opcache php72w-fpm php72w-gd php72w-mysqlnd php72w-soap php72w-mbstring php72w-ldap php72w-mcrypt php72w-xml
```
{% endcode-tabs-item %}
{% endcode-tabs %}

#### Install PHP-FPM

{% code-tabs %}
{% code-tabs-item title="Start the service and configuration." %}
```text
systemctl start php-fpm
systemctl enable php-fpm
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="Configure PHP-FPM using standard ProcessMaker settings for validation." %}
```text
sed -i '/short_open_tag = Off/c\short_open_tag = On' /etc/php.ini
sed -i '/post_max_size = 8M/c\post_max_size = 24M' /etc/php.ini
sed -i '/upload_max_filesize = 2M/c\upload_max_filesize = 24M' /etc/php.ini
sed -i '/;date.timezone =/c\date.timezone = America/New_York' /etc/php.ini
sed -i '/expose_php = On/c\expose_php = Off' /etc/php.ini
```
{% endcode-tabs-item %}
{% endcode-tabs %}

#### OpCache

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

{% hint style="info" %}
If you use the Enhanced Login plugin, set the `session.save_path` variable in the `/etc/php.ini` file:

`session.save_path = /var/lib/php/7.1/session`
{% endhint %}

#### PHP-FPM Configuration File



## Related Topics

{% page-ref page="prerequisites.md" %}

