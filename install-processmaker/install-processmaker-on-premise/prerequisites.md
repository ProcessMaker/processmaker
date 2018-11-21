# Prerequisites

## Start here

Before start you must update your packages system running the following command:

```text
apt-get update && apt-get upgrade
```

In order to install ProcessMaker 4 in Debian you will need:

* VirtualBox 5.2
* Vagrant 2.2.1
* PHP 7.2
* Composer
* Node.js 10.13.0

## **Install VirtualBox 5.2**

To install the virtual machine you need to follows these steps:

**1.**Go to the page [https://www.virtualbox.org/wiki/Linux\_Downloads](https://www.virtualbox.org/wiki/Linux_Downloads) and download

**2.**It will download a file with the .deb extension, in order to install the program use the command:

```text
dpks -i [file name].deb
```

**3.**After finish installing you would probably need to install the libraries for gcc, make, perl and the kernel “header” for virtualbox, to do so run the commands:

```text
apt-get install gcc make perl kernel-devel
```

**4.**Verify if virtualbox works

## **Install Vagrant 2.2.0**

To install vagrant go to the page [https://www.vagrantup.com/downloads.html](https://www.vagrantup.com/downloads.html) and download. It will download a file with the .deb extension, in order to install the program use the command:  

```text
dpks -i [file name].deb
```

## **Install PHP 7.2**

To install PHP follow these steps:

**1.**Run the command to add repositories:

```text
apt-get install software-properties-common
```

**2.**Add the repository:

```text
add-apt-repository ppa:ondrej/php
```

**3.**Update the packages:

```text
apt-get update
```

**4.**Install the php 7.2 version and its modules:

```text
apt-get install php7.2
apt-get install php-pear php7.2-curl php7.2-dev php7.2-gd php7.2-mbstring php7.2-zip php7.2-mysql php7.2-xml
```

**5.**Verify the installation:

```text
php -v
```

## **Install Composer**

To install composer follow these steps:

**1.**Run the command to download the file and edit:

```text
wget https://getcomposer.org/composer.phar
mv composer.phar composer
```

**2.**Run the command to give permissions:

```text
Chmod +x composer
```

**3.**Move the file to the local directory:

```text
mv composer /usr/local/bin
```

**4.**Verify the functionality:

```text
composer
```

## **Install Node.js 10.13.0**

To install node.js follow these commands:

**1.**Set these variables:

```text
VERSION=v10.13.0
DISTRO=linux-x64
```

**2.**Download the program:

```text
cd /opt/
wget https://nodejs.org/download/release/v10.13.0/node-v10.13.0-linux-x64.tar.xz
```

**3.**Decompres the program:

```text
mkdir /usr/local/lib/nodejs
tar -xJvf node-$VERSION-$DISTRO.tar.xz -C /usr/local/lib/nodejs
```

**4.**Rename the directory:

```text
mv /usr/local/lib/nodejs/node-$VERSION-$DISTRO /usr/local/lib/nodejs/node-$VERSION
```

**5.**Configure the program:

```text
echo "VERSION=v10.13.0" >> ~/.bashrc
echo "DISTRO=linux-x64" >> ~/.bashrc
echo 'export NODEJS_HOME=/usr/local/lib/nodejs/node-$VERSION/bin' >> ~/.bashrc
echo 'export PATH=$NODEJS_HOME:$PATH' >> ~/.bashrc
. ~/.bashrc
```

**6.**Verify the installation:

```text
node -v
```

{% page-ref page="installation-procedure.md" %}



