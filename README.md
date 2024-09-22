# StoryTellerAI - an AI that tells stories!
This is a Laravel 11 web app having integrated Cloudflare Workers AI that write stories on any given topic. 

## Why Cloudflare Workers AI?
Main reason is, it offers unlimited API requests (at least while creating this app, but not sure about future) for any model that is in beta, and free 10K Neurons/month for stable models. So you can create and test as many apps without any worry about the request limits or tokens.

## Pre-requirements
- WAMP/LAMP/XAMP (You will love <a href="https://laragon.org/download/">Laragon</a>)
- PHP 8.3^ (Download <a href="https://www.php.net/downloads.php">here</a>)
- <a href="https://getcomposer.org/download/">Composer</a>
- Cloudflare Account ID (<a href="https://developers.cloudflare.com/fundamentals/setup/find-account-and-zone-ids/">learn more</a>)
- Cloudflare <a href="https://dash.cloudflare.com/profile/api-tokens">API Token</a> (Create one with Workers AI Template)
- AI Model ID (<a href="https://developers.cloudflare.com/workers-ai/models/">learn more</a>)

You can read complete Workers AI API documentation <a href="https://developers.cloudflare.com/workers-ai/get-started/rest-api/">here</a>

## Installation:
Clone the repo in your system
```
git clone https://github.com/souravdutt/StoryTellerAI.git
```
Go to installation directory
```
cd StoryTellerAI
```
Install dependencies
```
composer instasll
```
Create `.env` file
```
cp .env.example .env
```
Generate App Key
```
php artisan key:generate
```
Open project in <a href="https://code.visualstudio.com/download">VS Code</a>
```
code .
```
Change below environment variables accordingly in your `.env` file
```
WORKERS_MODEL_ID="@cf/meta/llama-3.1-8b-instruct"
WORKERS_API_TOKEN=""
WORKERS_ACCOUNT_ID=""
```
Migrate database
```
php artisan migrate
```
Serve project
```
php artisan serve
```
