---
description: Install ProcessMaker Community edition internal beta 1.
---

# Install ProcessMaker Internal Beta 1

## Downloading the Internal Beta 1

Follow these steps to install ProcessMaker 4 Community edition internal beta 1:

1. Ensure that your server [meets requirements](prerequisites.md#software-requirements) and is [properly configured](prerequisites.md#web-server-configuration).
2. Create a directory on your web server in which to install ProcessMaker 4 Community edition internal beta 1.
3. [Download](https://github.com/ProcessMaker/bpm/releases/download/beta1/bpm-beta1.tar.gz) the `bpm-beta1.tar.gz` file.
4. Extract the `bpm-beta1.tar.gz` file to the directory in which to install ProcessMaker 4 Community edition internal beta 1.

## Install the Demo Database

Run the following script from the ProcessMaker 4 Community edition internal beta 1 directory to install the ProcessMaker database that contains pre-populated demo data:

{% code-tabs %}
{% code-tabs-item title="Install the ProcessMaker 4 Community edition internal 1 database with pre-populated demo data." %}
```text
Execute php artisan bpm:install in the folder you un tar the installer to start the ProcessMaker Installation
Specify localhost as your local database server
Specify 3306 as your local database port
Specify workflow as your local database name (or whatever name you have previously define for the DB, the DB must be empty)
Specify username as your local database username (choose your username, it needs to have access to the previously defined database)
Specify <password> as your local database password (set your password)
Specify https://localhost as your application url
Visit https://localhost in your browser to access the application
Login with the username of admin and password of admin
```
{% endcode-tabs-item %}
{% endcode-tabs %}

The batch script asks you for the following information to create the ProcessMaker database:

* Database credentials
* Database port number
* Database name
* Database username
* Database password
* Where to install the database \(`https://localhost` is the recommended default\)

After you provide the batch script with this information, the script installs the ProcessMaker database with pre-populated demo data.

## Run the Internal Beta 1

Run the following command from the ProcessMaker 4 Community edition internal beta 1 installation directory to run the internal beta 1:

{% code-tabs %}
{% code-tabs-item title="Run ProcessMaker 4 Community edition internal beta 1." %}
```text
php artisan serve
```
{% endcode-tabs-item %}
{% endcode-tabs %}

## Log in to ProcessMaker

After ProcessMaker is installed, go to [https://localhost](https://localhost). Use the following credentials to [log in](../using-processmaker/log-in.md):

* Username: `admin`
* Password: `admin`

## Related Topics

{% page-ref page="prerequisites.md" %}

{% page-ref page="../using-processmaker/log-in.md" %}

