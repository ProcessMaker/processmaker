---
description: Install ProcessMaker on premises.
---

# Install ProcessMaker

{% hint style="warning" %}
The following installation instructions are for the ProcessMaker Community edition internal beta. The internal beta is a developer-oriented installation procedure.

The installation procedure described below installs prerequisite applications and then installs the internal beta for ProcessMaker 4 Community edition.

Ensure you meet [installation requirements](../prerequisites.md) prior to starting this installation procedure.
{% endhint %}

{% hint style="info" %}
The installation instructions below apply to Debian Linux.
{% endhint %}

## Install Prerequisites

### Update Packages

{% code-tabs %}
{% code-tabs-item title="Update your packages system." %}
```text
dpks -i [file name].deb
```
{% endcode-tabs-item %}
{% endcode-tabs %}

In order to install ProcessMaker 4 in Debian you will need the following:

* VirtualBox 5.2
* Vagrant 2.2.1
* PHP 7.2
* Composer
* Node.js 10.13.0

### **Install VirtualBox 5.2**

Download VirtualBox for Debian from [https://www.virtualbox.org/wiki/Linux\_Downloads](https://www.virtualbox.org/wiki/Linux_Downloads). It will download a file with the `.deb` extension.

{% code-tabs %}
{% code-tabs-item title="Install VirtualBox" %}
```text
dpks -i [file name].deb
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="After the installation finishes, install the libraries for gcc, make, perl, and the kernel \"header\" for VirtualBox." %}
```text
apt-get install gcc make perl kernel-devel
```
{% endcode-tabs-item %}
{% endcode-tabs %}

Verify that VirtualBox works correctly.

### Install Vagrant 2.2.1

Download Vagrant for Debian from [https://www.vagrantup.com/downloads.html](https://www.vagrantup.com/downloads.html). It will download a file with the `.deb` extension.

{% code-tabs %}
{% code-tabs-item title="Install Vagrant." %}
```text
dpks -i [file name].deb
```
{% endcode-tabs-item %}
{% endcode-tabs %}

### Install PHP 7.2

Follow these steps to install PHP 7.2:

{% code-tabs %}
{% code-tabs-item title="Add repositories." %}
```text
apt-get install software-properties-common
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="Add the repository." %}
```text
add-apt-repository ppa:ondrej/php
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="Update the packages." %}
```text
apt-get update
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="Install the PHP 7.2 version and its modules." %}
```text
apt-get install php7.2
apt-get install php-pear php7.2-curl php7.2-dev php7.2-gd php7.2-mbstring php7.2-zip php7.2-mysql php7.2-xml
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="Verify the installation." %}
```text
php -v
```
{% endcode-tabs-item %}
{% endcode-tabs %}

