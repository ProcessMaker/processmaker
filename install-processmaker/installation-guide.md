---
description: Follow these guidelines to install ProcessMaker Spark on-premises.
---

# On-Premises Install Guide

## Overview

Follow these guidelines to install ProcessMaker Spark on-premises. Since ProcessMaker Spark is primarily an Enterprise cloud solution, these instructions do not apply to ProcessMaker Enterprise customers.

The following ProcessMaker Spark install guide assumes the reader is an IT administrator and understands how to install, manage, and configure web servers and databases. Therefore, these guidelines do not provide specific command instructions to install and configure ProcessMaker Spark or its required components.

Please contact [support@processmaker.com](mailto:support@processmaker.com) or call +1-919-289-1377 if you need assistance or would like information about how ProcessMaker can deploy your Enterprise cloud solution.

## Install ProcessMaker Spark

Follow these guidelines to prepare the host web server and install ProcessMaker Spark.

### Web Server Configuration

Configure the web server application you intend to use with ProcessMaker Spark.

{% hint style="info" %}
Run your web server application as a dedicated ProcessMaker user.
{% endhint %}

{% tabs %}
{% tab title="Apache 2.4.x " %}
ProcessMaker Spark includes a `public/.htaccess` file that provides URLs without the `index.php` front controller in the path. Before installing ProcessMaker Spark with your Apache web server, ensure you enable the `mod_rewrite` module so that the `.htaccess` file will be honored by the server.

If the `.htaccess` file does not work with your Apache web server, try the following alternative:

{% code-tabs %}
{% code-tabs-item title="Direct Apache web server requests to the index.php front controller." %}
```text
Options +FollowSymLinks -Indexes
RewriteEngine On

RewriteCond %{HTTP:Authorization} .
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
```
{% endcode-tabs-item %}
{% endcode-tabs %}
{% endtab %}

{% tab title="NGINX 1.x" %}
The following directive in your site configuration will direct all requests to the `index.php` front controller:

{% code-tabs %}
{% code-tabs-item title="Direct NGINX web server requests to the index.php front controller." %}
```text
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```
{% endcode-tabs-item %}
{% endcode-tabs %}
{% endtab %}
{% endtabs %}

### Download the ProcessMaker Spark Installer

Redirect to the `/opt` directory, and then download the ProcessMaker installer from the following URL: [https://github.com/ProcessMaker/bpm/releases/download/beta2/bpm-beta4.tar.gz](https://github.com/ProcessMaker/bpm/releases/download/beta2/bpm-beta4.tar.gz).

### Uncompress the ProcessMaker Spark Installer

Uncompress the installer archive into the folder you intend to install ProcessMaker based on which web server application you intend to use with ProcessMaker Spark. Change the ownership to the dedicated ProcessMaker user you created when you [configured the web server](installation-guide.md#web-server-configuration).

### Run the ProcessMaker Spark Installer

Run the ProcessMaker Spark installer and follow the prompts to configure your ProcessMaker Spark installation.

### Install ProcessMaker Spark

Run the following command to install ProcessMaker Spark:

{% code-tabs %}
{% code-tabs-item title="Install ProcessMaker Spark." %}
```text
php artisan bpm:install
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% hint style="info" %}
If the `.env` file is found during the installation procedure, stop the installation, remove the `.env` file, and then start the installation procedure again. The `.env` file stores ProcessMaker  environment configuration settings.
{% endhint %}

## Monitor the Queue Management Service

Monitor the `horizon` process that runs the [Queue Management service](../processmaker-administration/queue-management/what-is-queue-management.md) for the following reasons:

* If the Queue Management service quits unexpectedly, the process monitor can restart the service.
* If you intend to deploy a ProcessMaker Spark update, terminate the Queue Management service prior to installing the ProcessMaker Spark update. Restart the service after you have installed the update.

Use the following configuration file if you use the Supervisor process monitor to monitor the`horizon` process:

{% code-tabs %}
{% code-tabs-item title="Supervisor process monitor configuration file to monitor the horizon process." %}
```text
[program:horizon]
process_name=%(program_name)s
command=php /home/forge/app.com/artisan horizon
autostart=true
autorestart=true
user=forge
redirect_stderr=true
stdout_logfile=/home/forge/app.com/horizon.log
```
{% endcode-tabs-item %}
{% endcode-tabs %}

## Configure Real-Time Broadcasting

ProcessMaker Spark broadcasts real-time events. ProcessMaker's event broadcasting configuration is stored in the `config/broadcasting.php` configuration file. ProcessMaker Spark supports Pusher and Redis broadcast drivers as well as a `log` driver for local development and debugging. Furthermore, a `null` driver is included which allows you to disable all broadcasting. See the configuration example for each of these drivers in the `config/broadcasting.php` configuration file.

## Start the Scheduler Service

You only need to add the following Cron entry to your server:

```text
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

This Cron calls the scheduler service every minute. When the `schedule:run`command runs, ProcessMaker evaluates your scheduled tasks and runs the tasks that are due.

## Log In for the First Time

Go to [https://localhost](https://localhost) or the IP address/domain name you specified. Use the following credentials to [log in](../using-processmaker/log-in.md#log-in):

* Username: `admin`
* Password: `admin`

After you log in for the first time, you may begin managing [user accounts](../processmaker-administration/add-users/manage-user-accounts/) and [groups](../processmaker-administration/assign-groups-to-users/manage-groups/).

## Related Topics

{% page-ref page="prerequisites.md" %}

{% page-ref page="../processmaker-administration/queue-management/what-is-queue-management.md" %}

{% page-ref page="../using-processmaker/log-in.md" %}

{% page-ref page="../processmaker-administration/add-users/manage-user-accounts/" %}

{% page-ref page="../processmaker-administration/assign-groups-to-users/manage-groups/" %}

