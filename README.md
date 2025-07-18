<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

## Welcome to laravel AfribaPAY payment integration

This repo houses all codes for you to generate token, make payment and check status of payment through [AfribaPAY](https://www.afribapay.com) Mobile Money Payment Solutions Gateway .

### Pre-reqs
- [Laravel](https://laravel.com/docs/10.x) version ^10.10 (or from v8 and +).
- [Composer](https://getcomposer.org) A Dependency Manager for PHP.

### Installation

After cloning the repository, Install dependencies:

```sh
composer install
```

After installing dependencies, follow the steps below

### 1. Set up Environment Variables

**Rename the following files:**
- `.env.sample` -> `.env` OR just create `.env` file and copy `.env.sample` content inside

**Key generation:**
```sh
php artisan key:generate
```
> Don't forhet to set up APP URL and DB Config inside your .env file

**Set up AfribaPay Config:**

Go to your .env file created, and add those details:

- ***`AFRIBAPAY_API_URL`*** -> `AfribaPay API Url`
- ***`AFRIBAPAY_API_USER`*** -> `AfribaPay User API code`
- ***`AFRIBAPAY_API_KEY`*** -> `AfribaPay API Key`
- ***`AFRIBAPAY_API_MARCHANDKEY`*** -> `AfribaPay Marchand Key`

> For testing pass the sandbox credentials and for live the prod ones

### 2. Migration 

Run the command below to migrate the database

```shell
php artisan migrate
```

### Start server

Now that you're all setup, start app and get to work!

```shell
php artisan serve
```
Runs the app in the development mode.\
Open [http://localhost:8000](http://localhost:8000) to view it in your browser.

Build something great!

## Deployment

TBD

## How to fix

Here are a list of common issue and how you can fix them:

TBD
