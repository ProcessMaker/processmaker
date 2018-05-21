#!/bin/bash
composer install
npm install
npm run dev
npm run test
./vendor/phpunit/phpunit/phpunit
