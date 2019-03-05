---
description: Follow these guidelines to install ProcessMaker 4 Community Edition.
---

# Installation Guide

## Overview

The following ProcessMaker 4 installation guide assumes the reader is an IT administrator and understands how to install, manage, and configure web servers. Therefore, these guidelines do not provide specific command instructions to install and configure ProcessMaker 4 or its required components.

Please contact [support@processmaker.com](mailto:support@processmaker.com) or call +1-919-289-1377 if you need assistance or would like information how ProcessMaker would deploy your Enterprise cloud solution.

## Install ProcessMaker 4

Follow these guidelines to prepare the host web server and install ProcessMaker 4.

### Web Server Configuration

Configure the web server application you intend to use with ProcessMaker 4.

{% hint style="info" %}
Run your web server application as a dedicated ProcessMaker user.
{% endhint %}

{% tabs %}
{% tab title="Apache 2.4.x " %}
ProcessMaker includes a `public/.htaccess` file that provides URLs without the `index.php` front controller in the path. Before hosting ProcessMaker with Apache, ensure you enable the `mod_rewrite` module so that the `.htaccess` file will be honored by the server.

If the `.htaccess` file does not work with your Apache installation, try this alternative:

```text
Options +FollowSymLinks -Indexes
RewriteEngine On

RewriteCond %{HTTP:Authorization} .
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
```
{% endtab %}

{% tab title="NGINX 1.x or later" %}
The following directive in your site configuration will direct all requests to the `index.php` front controller:

```text
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```
{% endtab %}
{% endtabs %}

### Download the ProcessMaker 4 Installer

Redirect to the `/opt` directory, and then download the ProcessMaker installer from the following URL: [https://github.com/ProcessMaker/bpm/releases/download/beta2/bpm-beta4.tar.gz](https://github.com/ProcessMaker/bpm/releases/download/beta2/bpm-beta4.tar.gz).

### Extract the ProcessMaker 4 Installer

Extract the ProcessMaker 4 installer based on which web server application you intend to use with ProcessMaker 4.

### Run the ProcessMaker 4 Installer

Run the ProcessMaker 4 installer and follow the prompts to configure your ProcessMaker 4 installation.

## Related Topics

{% page-ref page="prerequisites.md" %}

{% page-ref page="../using-processmaker/log-in.md" %}

{% page-ref page="../processmaker-administration/add-users/manage-user-accounts/" %}

{% page-ref page="../processmaker-administration/assign-groups-to-users/manage-groups/" %}

