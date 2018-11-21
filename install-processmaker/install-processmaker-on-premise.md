---
description: Install ProcessMaker on premises.
---

# Install ProcessMaker

{% hint style="warning" %}
The following installation instructions are for the ProcessMaker Community edition internal beta. The internal beta is a developer-oriented installation procedure.

The installation procedure described below installs prerequisite applications and then installs the internal beta for ProcessMaker 4 Community edition.

Ensure you meet [installation requirements](prerequisites.md) prior to starting this installation procedure.

The internal beta has been successfully installed on Debian Linux.
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

* [VirtualBox 5.2](install-processmaker-on-premise.md#install-virtualbox-5-2)
* [Vagrant 2.2.1](install-processmaker-on-premise.md#install-vagrant-2-2-1)
* [PHP 7.2](install-processmaker-on-premise.md#install-php-7-2)
* [Composer](install-processmaker-on-premise.md#install-composer)
* [Node.js 10.13.0](install-processmaker-on-premise.md#install-node-js-10-13-0)

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

### Install Composer

{% code-tabs %}
{% code-tabs-item title="Download Composer and edit." %}
```text
wget https://getcomposer.org/composer.phar
mv composer.phar composer
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="Give permissions." %}
```text
Chmod +x composer
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="Move the file to the local directory." %}
```text
mv composer /usr/local/bin
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="Verify the functionality." %}
```text
composer
```
{% endcode-tabs-item %}
{% endcode-tabs %}

### Install Node.js 10.13.0

{% code-tabs %}
{% code-tabs-item title="Set \"Version\" and \"Distro\" variables." %}
```text
VERSION=v10.13.0
DISTRO=linux-x64
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="Download the program." %}
```text
cd /opt/
wget https://nodejs.org/download/release/v10.13.0/node-v10.13.0-linux-x64.tar.xz
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="Decompress the program." %}
```text
mkdir /usr/local/lib/nodejs
tar -xJvf node-$VERSION-$DISTRO.tar.xz -C /usr/local/lib/nodejs
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="Rename the directory." %}
```text
mv /usr/local/lib/nodejs/node-$VERSION-$DISTRO /usr/local/lib/nodejs/node-$VERSION
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="Configure the program." %}
```text
echo "VERSION=v10.13.0" >> ~/.bashrc
echo "DISTRO=linux-x64" >> ~/.bashrc
echo 'export NODEJS_HOME=/usr/local/lib/nodejs/node-$VERSION/bin' >> ~/.bashrc
echo 'export PATH=$NODEJS_HOME:$PATH' >> ~/.bashrc
. ~/.bashrc
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="Verify the installation." %}
```text
node -v
```
{% endcode-tabs-item %}
{% endcode-tabs %}

## Install ProcessMaker



## Related Topics

{% page-ref page="prerequisites.md" %}

