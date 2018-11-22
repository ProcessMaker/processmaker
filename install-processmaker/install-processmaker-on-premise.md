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
apt-get update && apt-get upgrade
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

[Download VirtualBox](https://www.virtualbox.org/wiki/Linux_Downloads) for Debian. It will download a file with the `.deb` extension.

![It is important that you download the correct version of VirtualBox for your OS](../.gitbook/assets/selection_281.png)

{% code-tabs %}
{% code-tabs-item title="Install the package of VirtualBox" %}
```text
dpkg -i [file name].deb
```
{% endcode-tabs-item %}
{% endcode-tabs %}

After the installation finishes, it possible that VirtualBox will not work, and it will ask you to install the following libraries 

{% code-tabs %}
{% code-tabs-item title="Install the libraries for gcc, make, perl, and the kernel \"header\"" %}
```text
apt-get install gcc make perl kernel-devel
```
{% endcode-tabs-item %}
{% endcode-tabs %}

> In Ubuntu 18.04 instead of `kernel-devel` use `linux-headers-generic`

Then you will need to run the following command to finish the installation of VirtualBox

{% code-tabs %}
{% code-tabs-item title="Recompile the kernel module" %}
```text
sudo /sbin/vboxconfig
```
{% endcode-tabs-item %}
{% endcode-tabs %}

Verify that VirtualBox works correctly.

### Install Vagrant 2.2.1

[Download Vagrant](https://www.vagrantup.com/downloads.html) for Debian. It will download a file with the `.deb` extension.

{% code-tabs %}
{% code-tabs-item title="Install Vagrant." %}
```text
dpkg -i [file name].deb
```
{% endcode-tabs-item %}
{% endcode-tabs %}

Verify if it is installed correctly

### Install PHP 7.2

Perform the following commands to install PHP 7.2:

{% code-tabs %}
{% code-tabs-item title="1. Install the program to add repositories" %}
```text
apt-get install software-properties-common
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="2. Add the repository." %}
```text
add-apt-repository ppa:ondrej/php
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="3. Update the packages." %}
```text
apt-get update
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="4. Install the PHP 7.2 version and its modules." %}
```text
apt-get install php7.2
apt-get install php-pear php7.2-curl php7.2-dev php7.2-gd php7.2-mbstring php7.2-zip php7.2-mysql php7.2-xml
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="5. Verify the installation." %}
```text
php -v
```
{% endcode-tabs-item %}
{% endcode-tabs %}

### Install Composer

Perform the following commands to install Composer:

{% code-tabs %}
{% code-tabs-item title="1. Download Composer and edit." %}
```text
wget https://getcomposer.org/composer.phar
mv composer.phar composer
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="2. Give permissions." %}
```text
chmod +x composer
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="3. Move the file to the local directory." %}
```text
mv composer /usr/local/bin
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="4. Verify the functionality." %}
```text
composer
```
{% endcode-tabs-item %}
{% endcode-tabs %}

### Install Node.js 10.13.0

Perform the following commands to install Node.js 10.13.0:

{% code-tabs %}
{% code-tabs-item title="1. Set \"Version\" and \"Distro\" variables." %}
```text
VERSION=v10.13.0
DISTRO=linux-x64
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="2. Download the program." %}
```text
cd /opt/
wget https://nodejs.org/download/release/v10.13.0/node-v10.13.0-linux-x64.tar.xz
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="3. Decompress the program." %}
```text
mkdir /usr/local/lib/nodejs
tar -xJvf node-$VERSION-$DISTRO.tar.xz -C /usr/local/lib/nodejs
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="4. Rename the directory." %}
```text
mv /usr/local/lib/nodejs/node-$VERSION-$DISTRO /usr/local/lib/nodejs/node-$VERSION
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="5. Configure the program." %}
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
{% code-tabs-item title="6. Verify the installation." %}
```text
node -v
```
{% endcode-tabs-item %}
{% endcode-tabs %}

## Install ProcessMaker

Perform the following commands to install ProcessMaker 4 internal beta:

{% code-tabs %}
{% code-tabs-item title="1. Install Git." %}
```text
apt-get install git
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="2. Clone the project." %}
```text
git clone https://github.com/ProcessMaker/bpm.git
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="3. Enter the project." %}
```text
cd bpm/
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="4. Run Composer." %}
```text
composer install
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="5. Install the package manager" %}
```text
apt-get install npm
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="6. Run the manager inside the project" %}
```text
npm install
npm run dev
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="7. Edit the file." %}
```text
nano /etc/hosts
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="8. Add the following line at the bottom of the file." %}
```text
192.168.10.10 bpm4.local.processmaker.com
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="9. Create a public key." %}
```text
ssh-keygen
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="10. Gets up the server" %}
```text
vagrant up
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="11. Enter the server" %}
```text
vagrant ssh
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="12. Go to the following path:" %}
```text
cd /home/vagrant/processmaker
```
{% endcode-tabs-item %}
{% endcode-tabs %}

{% code-tabs %}
{% code-tabs-item title="13. Start the isntallation and ask for parameters" %}
```text
php artisan bpm:install
```
{% endcode-tabs-item %}
{% endcode-tabs %}

Enter the following parameters:

* Specify `localhost` as your local database server.
* Specify `3306` as your local database port.
* Specify `workflow` as your local database name.
* Specify `homestead` as your local database username.
* Specify `secret` as your local database password.
* Specify `https://bpm4.local.processmaker.com` as your application URL.

In a browser go to [https://bpm4.local.processmaker.com](https://bpm4.local.processmaker.com) and use the following credentials to [log in](../using-processmaker/log-in.md):

* Username: `admin`
* Password: `admin`

## Related Topics

{% page-ref page="prerequisites.md" %}

