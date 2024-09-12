# ProcessMaker 4 Documentation

# Overview

ProcessMaker is an open source, workflow management software suite, which includes tools to automate your workflow, design forms, create documents, assign roles and users, create routing rules, and map an individual process quickly and easily. It's relatively lightweight and doesn't require any kind of installation on the client computer. This file describes the requirements and installation steps for the server.

## Getting Started

If you are new to ProcessMaker 4 and would like to load the software locally, we recommend you download the Dockerized version from https://github.com/ProcessMaker/pm4core-docker

## System Requirements

* [Composer 2](https://getcomposer.org/)
* [Node.js 16.18.1](https://nodejs.org/en/)
* [NPM 8.9](https://www.npmjs.com/package/npm)
* [PHP 8.1](https://php.net)
* [PHP-FPM](https://www.php.net/manual/en/install.fpm.php)
* [PHP GD Extension](https://www.php.net/manual/en/image.installation.php)
* [PHP ImageMagick Extension](https://www.php.net/manual/en/book.imagick.php)
* [PHP IMAP Extension](https://www.php.net/manual/en/imap.setup.php)
* [Nginx](https://nginx.org/)
* [MySql 8.0](https://dev.mysql.com/downloads/mysql/8.0.html)
* [Redis](https://redis.io/)
* [Docker](https://docs.docker.com/get-docker/)


## Install

Before installing, Nginx needs to be configured to use php-fpm and point to the public folder

1. Download and unzip a version from the releases page https://github.com/ProcessMaker/processmaker/releases
1. Configure Nginx to use php-fpm and point to the public folder in the unzipped code. See https://laravel.com/docs/8.x/deployment#nginx
1. CD into the folder and run `composer install`
1. Run the installer `php artisan processmaker:install` and follow the instructions
1. Edit the .env file to update any server specific settings
1. Install javascript assets `npm install`
1. Compile javascript assets `npm run dev`
1. Configure [laravel echo server](https://github.com/tlaverdure/Laravel-Echo-Server) in a separate shell `npx laravel-echo-server init` using the following settings:
   1. Do you want to run this server in development mode? **Yes**
   1. Which port would you like to serve from? **6001**
   1. Which database would you like to use to store presence channel members? **redis**
   1. Enter the host of your Laravel authentication server. *Enter your instance's url*
   1. Will you be serving on http or https? **http**
   1. Do you want to generate a client ID/Key for HTTP API? **No**
   1. Do you want to setup cross domain access to the API? **No**
   1. What do you want this config to be saved as? **laravel-echo-server.json**
1. Then run [laravel echo server](https://github.com/tlaverdure/Laravel-Echo-Server) `npx laravel-echo-server start`
1. Run horizon in a separate shell `php artisan horizon`
1. If you change any settings, make sure to run `php artisan optimize:clear` and restart horizon

## Installing and upgrading an enterprise instance hosted on AWS

https://processmaker.atlassian.net/wiki/spaces/PM4/pages/480149598/Server+Deployment

## Using ProcessMaker 4

The online documentation for usage of ProcessMaker 4 can be found by clicking the link below.

https://docs.processmaker.com/

## Testing
All PRs for PM4 and it's packages should be accompanied by a test.

## CI/CD

### Automated Tests
When ever you open or update a PR, the test suite is run with all packages installed.

If your PR requires branches in other packges or core, you can specify the branch anywhere in the PR body with this tag:

`ci:< package name >:< branch name>`

For example, if you open a PR in core that requires a bugfix branch in connector-send-email, put this in your core PR body text:

`ci:connector-send-email:bugfix/FOUR-5059`

This works in package PRs as well. To specify a branch in core, use:

`ci:processmaker:my-branch-in-core`

If no branches are specified in the PR body, the develop branch of each package will be used.

### CI Server

A full working instance can be built by adding the tag `ci:deploy` to your PR description. A link will be posted in the PR comments when it's ready. Note that this currently takes 10 to 30 minutes before the instance is ready.

The instance will stay active until the PR is merged.

You can wipe the database on the CI Server by adding the tag `ci:db:clean`. Remember to remove the tag from your PR description or the DB will be wiped clean every time the PR is updated.

### Use `next` branch
To use the `next` branch instead of `develop` for all packages by default, use `ci:next` in your PR body.

### Environment Variables
You can add or overwrite environment variables on the deployed server using this syntax in your PR body
```
ci:MY_ENVIRONMENT_VARIABLE=value
```
Or with double quotes if the value has spaces
```
ci:MY_ENVIRONMENT_VARIABLE="custom value"
```

### PHPUnit Tests
We use PHPUnit for both integration and unit testing. Most of our PHPUnit tests are integration tests that use the framework and database.

Run the entire testsuite with `phpunit`

If phpunit is not in your $PATH, you can use `vendor/bin/phpunit ...`

To run the entire suite faster using parallel tests, run
```
PARALLEL_TEST_PROCESSES=6 vendor/bin/paratest -p 6
```
- The environment variable and the -p argument must be the same number of parallel processes.

To run an individual test, run
```
phpunit tests/path/to/testTest.php
```

- Running phpunit will populate a test database first, which is slow. After the first run, you can
skip populating the database with `POPULATE_DATABASE=0 phpunit ...` to run tests much faster.
- All test file names must end in Test.php

Package tests should be saved in the package repository but must be run from processmaker core:
```
phpunit vendor/processmaker/package-name/tests/...
```

*It is considered a best practice to write a failing test first.
Then, modify the code until the test passes*

## Development

#### System Requirements

You can develop ProcessMaker as well as ProcessMaker packages locally. In order to do so, you must have the following:

* [Virtualbox  5.2](https://www.virtualbox.org/) or above
* [Vagrant 2.2.0](https://www.vagrantup.com/) or above
* [PHP 8.1](https://php.net) or above
  * Windows users can install [XAMPP](https://www.apachefriends.org/index.html)
* [Composer 2](https://getcomposer.org/)
* [Node.js 16.18.1](https://nodejs.org/en/) or above

**Steps for Development Installation**

* Clone the repository into a directory
* Perform `composer install` to install required libraries. If you are on windows, you may need to run `composer install --ignore-platform-reqs` due to Horizon requiring the pcntl extension. You can safely ignore this as the application runs in the virtual machine which has the appropriate extensions installed.
* Perform `npm install` in the project directory
* Perform `npm run dev` to build the front-end assets
* Modify your local `/etc/hosts` add `192.168.10.10 processmaker.local.processmaker.com`. On Windows, this file is located at `C:\Windows\System32\Drivers\etc\hosts`.
  * If you need to change the ip address to something else to avoid conflicts on your network, modify the `Homestead.yaml` file accordingly. Do not commit this change to the repository.
* Execute `vagrant up` in the project directory to bring up the laravel homestead virtual machine
* Execute `vagrant ssh` to ssh into the newly created virtual machine
* Execute `php artisan processmaker:install` in `/home/vagrant/processmaker` to start the ProcessMaker Installation
  * Specify `localhost` as your local database server
  * Specify `3306` as your local database port
  * Specify `processmaker` as your local database name
  * Specify `homestead` as your local database username
  * Specify `secret` as your local database password
  * Specify `https://processmaker.local.processmaker.com` as your application url
* Check your .env file to ensure the `PROCESSMAKER_SCRIPTS_DOCKER` variable has the right Docker installation path, especially if you are under macOS (Docker on macOS installs under /usr/local/bin/docker).
* Visit `https://processmaker.local.processmaker.com` in your browser to access the application
  * Login with the username of `admin` and password of `admin`

When developing, make sure to turn on debugging in your `.env` so you can see the actual error instead of the Whoops page.

```text
APP_DEBUG=TRUE
```

Optionally, trust the self-signed certificate on your host machine so you don't get the "Not Secure" warnings in chrome and postman.

For macOS:

1. In `your-repository-root/storage/ssl`, double-click on `processmaker.local.processmaker.com.crt`
2. Click on "Add" to add it to your login keychain
3. In the Keychain Access window click on the Certificates category on the bottom left.
4. Double-click on the processmaker certificate
5. Open the Trust section. For `"When using this certificate"`, select `"always trust"`
6. Close the window. You will be asked for your password. Close and reopen the processmaker tab in chrome.

If you choose not to install the certificate, you should access the socket.io js file in your browser to allow unsafe connections from it. Otherwise, real-time notifications may not work in your development environment.

* [https://processmaker.local.processmaker.com:6001/socket.io/socket.io.js](https://processmaker.local.processmaker.com:6001/socket.io/socket.io.js)

#### Customize Logos

1. Add images to resources/img/
2. Add The following variables to the .env file

```text
MAIN_LOGO_PATH={{EXPANDED LOGO PATH HERE}}
ICON_PATH_PATH={{ICON LOGO PATH HERE}}
LOGIN_LOGO_PATH={{LOGIN PAGE LOGO PATH HERE}}
```

1. Run npm run dev

#### Scheduled tasks/events

To run time based BPMN events like Timer Start Events or Intermediate Timer Events, the laravel scheduler should be enabled. To do this open a console and:
1. Execute crontab -e
2. Add to the cron tab the following line \(replacing the upper cased text with the directory where your proyecto is located \):

```text
* * * * * cd YOUR_BPM_PROJECT && php artisan schedule:run >> /dev/null 2>&1
```

#### API

The ProcessMaker API is documented using OpenAPI 3.0 documentation and can be viewed at `/api/documentation`. The documention is generated by adding annotations to Models and Controllers.

You should add annotations to all models and controllers that you create or modify because it's how we generate the SDKs that are used when running scripts.

When developing, make sure to add this to your `.env` file so that any changes you make to the annotations are automatically turned into documentation when you reload the `/api/documentation` page:

```text
L5_SWAGGER_GENERATE_ALWAYS=TRUE
```

At the comment block at the top of the model, add an @OA annotation to describe the schema. See `ProcessMaker/Models/Process.php` for an example.

To keep things dry, you can define 2 schemas. One that inherits the other.

```php
/**
 * ...existing comments above...
 *
 * @OA\Schema(
 *   schema="ProcessEditable",
 *   @OA\Property(property="process_category_uuid", type="string", format="uuid"),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="description", type="string"),
 *   @OA\Property(property="status", type="string", enum={"ACTIVE", "INACTIVE"}),
 * ),
 * @OA\Schema(
 *   schema="Process",
 *   allOf={
 *       @OA\Schema(ref="#/components/schemas/ProcessEditable")
 *       @OA\Schema(
 *           type="object",
 *           @OA\Property(property="user_uuid", type="string", format="uuid"),
 *           @OA\Property(property="uuid", type="string", format="uuid"),
 *           @OA\Property(property="created_at", type="string", format="date-time"),
 *           @OA\Property(property="updated_at", type="string", format="date-time"),
 *       ),
 *   },
 *
 * )
 */
class Process extends ProcessMakerModel implements HasMedia
{
...
```

Now you can use the reference to the schema when annotating the controllers. See `ProcessMaker/Http/Controllers/Api/ProcessController.php` for an example.

```php
    /**
     * @OA\Get(
     *     path="/processes",
     *     summary="Returns all processes that the user has access to",
     *     operationId="getProcesses",
     *     tags={"Process"},
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *     @OA\Parameter(ref="#/components/parameters/"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of processes",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Process"),
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 allOf={@OA\Schema(ref="#/components/schemas/metadata")},
     *             ),
     *         ),
     *     ),
     * )
     */
    public function index(Request $request)
    {
    ...
```

And for a show method

```php
    /**
     * @OA\Get(
     *     path="/processes/{processUuid}",
     *     summary="Get single process by ID",
     *     operationId="getProcessByUuid",
     *     tags={"Process"},
     *     @OA\Parameter(
     *         description="ID of process to return",
     *         in="path",
     *         name="processUuid",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the process",
     *         @OA\JsonContent(ref="#/components/schemas/Process")
     *     ),
     */
    public function show(Request $request, Process $process)
    {
    ...
```

#### NAYRA

Please add/change the next configuration in .env file to define the message broker driver that is used for Nayra

# Message broker driver, possible values: rabbitmq, kafka, this is optional, if not exists or is empty, the Nayra will be work as normally with local execution
MESSAGE_BROKER_DRIVER=rabbitmq

# Rabbit MQ connection, only when you use RabbitMQ
RABBITMQ_HOST=127.0.0.1
RABBITMQ_PORT=30672
RABBITMQ_LOGIN=guest
RABBITMQ_PASSWORD=guest

# Kafka connection, only when you use Kafka
KAFKA_BROKERS=127.0.0.1:30092

**Notes**

`operationId` will be the method name of the generated code. It can be anything camel cased but should be named some intuitive.

**Testing with Swagger UI**

Reload the swagger UI at `api/documentation` page in your browser to see the results and debug any errors with the annotations.

By default, Swagger UI will use your processmaker app auth. So as long as you're logged into the app you should be able to run API Commands from Swagger UI as your logged in user.

You can also create a personal access token to see the API results as a specific user would.

```text
$user->createToken('Name it here')->accessToken;
```

Copy the token. In api/documentation, click on the Authenticate button on the top right and enter it in the `pm_api_bearer` value field.

**More Info**

Detailed examples can be found at [https://github.com/zircote/swagger-php/tree/master/Examples/petstore.swagger.io](https://github.com/zircote/swagger-php/tree/master/Examples/petstore.swagger.io)

Full OpenAPI 3.0 specification at [https://github.com/OAI/OpenAPI-Specification/blob/master/versions/3.0.1.md](https://github.com/OAI/OpenAPI-Specification/blob/master/versions/3.0.1.md)

**Testing with Laravel Dusk**

When testing in [Laravel Dusk](https://laravel.com/docs/6.x/dusk), make sure to turn off debugging mode in your `.env` so you can use the whole page and screens executing functional tests. Then, change app\_env value to `develop` in the same file:

```text
APP_DEBUG=FALSE
APP_ENV=develop
```

Execute `vagrant ssh` to ssh into the newly created virtual machine.

Execute `php artisan dusk` in `/home/vagrant/processmaker` to execute Laravel dusk test cases.

Execute `php artisan dusk:make newTest` to generate a new Dusk test. The generated test will be placed in the `tests/Browser` directory.

**More Info**

Detailed installation can be found at [https://laravel.com/docs/6.x/dusk#installation](https://laravel.com/docs/6.x/dusk#installation)

To interact with web elements [https://laravel.com/docs/6.x/dusk#interacting-with-elements](https://laravel.com/docs/6.x/dusk#interacting-with-elements)

List of available assertions [https://laravel.com/docs/6.x/dusk#available-assertions](https://laravel.com/docs/6.x/dusk#available-assertions)


# ICONS

Please follow the steps:
1. Execute the command in root processmaker
```text
npm install
```
2. Add the new svg icon file in the /processmaker/resources/icons
```text
/processmaker/resources/icons/my-new-icon.svg
```
3.Run the follow command
```text
npm run font
```
4.Run the follow command
```text
npm run dev
```
5.To use your new icon, in any template or component, add the icon as follows:
```text
<i class="fp-my-new-icon" />
```

### RECOMMENDATIONS ABOUT ICONS
1. We recommend using the file name with '-' for example: 
```text
"left-arrow.svg"
```
2. To use the icon, we should use the same name of the file,  for example: 
```text
File name: "my-jonas-custom-icon.svg"
How to use icon: <i class="fp-my-jonas-custom-icon" />
```
3. To check all the icons
```text
npm run dev-font
```


# Message broker driver, possible values: rabbitmq, kafka, this is optional, if not exists or is empty, the Nayra will be work as normally with local execution
MESSAGE_BROKER_DRIVER=rabbitmq


#### License

Distributed under the [AGPL Version 3](https://www.gnu.org/licenses/agpl-3.0.en.html)

ProcessMaker \(C\) 2002 - 2020 ProcessMaker Inc.

For further information visit: [http://www.processmaker.com/](http://www.processmaker.com/)
