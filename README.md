# Google App Engine development without putting secrets in app.yaml with Docker's help

## Background

### .env (dotenv)
In general, it's a bad idea to keep secrets in any file that will ber version controlled.  
https://blog.github.com/2013-01-25-secrets-in-the-code/

My preferred method is to use a .env file for secrets and .gitignore the .env file. Laravel does this and it works well. Django requires a module like django-dotenv: https://github.com/jpadilla/django-dotenv  

It's a good idea to have a version controlled .env.example file with sample key/values defined for others to see which vars the app needs. A new dev can then git branch, copy the .env.example to .env and add secrets accordingly.  

Also, docker-compose supports .env files.


### Service Credentials
When you run an app on Google App Engine (GAE), it automatically inherits service account credentials of the account GAE is running under.
https://cloud.google.com/docs/authentication/production#providing_credentials_to_your_application

First, GoogleClient() checks for env vars, if not set, it uses the default service account. *(Remember this)*  

What this means is an app running in GAE can access Google APIs using the credentials for the account executing GAE. So, we can, for instance, connect to Google Cloud Datastore (Datastore) without providing credentials.  

Ok, but let's say you want to use Google APIs to access user data such as Gmail...  

### Client ID Secret/Credentials for User Data
Accessing user data requires a Client ID secret and permission from the user (OAuth2) in the form of authorized credentials (access/refresh tokens).  
https://github.com/Jamesway/gae-credentials-php71

Once you get the Client ID secret and credentials, where do you put them? 

## Problem
Unfortunately, unlike Heroku, GAE, doesn't allow you to set environment variables for your service. GAE requires you to add your secrets to the app.yaml file to get then into GAE as env vars :(  
https://cloud.google.com/appengine/docs/flexible/python/configuring-your-app-with-app-yaml#Python_app_yaml_Defining_environment_variables  
https://cloud.google.com/appengine/docs/flexible/php/configuring-your-app-with-app-yaml#PHP_app_yaml_Defining_environment_variables  

So how do we get our secrets into GAE without exposing them to version control?

## Solution

### Google Cloud Datastore
Since GoogleClient() can use service credentials from GAE, we can store secrets in Datastore without exposing them in app.yaml.  
**Note: Google Cloud Datastore encrypts data in transit and at rest making it a suitable secret store.** 

That's great for execution on GAE, but my local environment doesn't have service credentials baked in.  

How do I develop for GAE and use Datastore for secrets?

### Local Secrets
What we really want to do is access the secrets in Datastore and cache them locally for:
1. performance - we avoid the round-trip on the wire
2. cost savings - datastore queries cost money, fewer is cheaper

To create a local secrets cache, I create a /.secrets directory in the project root and .gitingore it.

But what about the service credentials?  

In GAE, service credentials are taken care of auto-magically.  
Outside of GAE, we to pass in service credentials to access Google APIs such as datastore.  

### Generate Service Credentials for Development
Generate Service Credentials TODO link
  

### Home Stretch
Once we generate a service credentials json file and store it in /.secrets, we need to pass the location of the service credentials as an env to the app.
To do this, I put the service credentials path in the .env file and docker-compose picks up the value and passes it to the container's environment.  
  
Remember... "First the GoogleClient() checks for env vars, if not set, it uses the default service account."  

Done!

### Summary
Pros
- no secrets are version controlled - app.yaml is clean
- secrets are securely stored/retrieved from Google Cloud Datastore
- retrieved secrets are cached for performance and to minimize queries to Google Cloud Datastore
- source code is unmodified
- docker compatibility

Cons
- need to download service credentials to .gitignored /.secret directory
- need to generate and store secrets in Google Cloud Datastore 


## JMail - A Quick Demo with Gmail
I used PHP 7.1 for this app (maybe I'll add a Python version at somepoint).  
The technique is the same with any stack.  

This is a light, sans framework demo that includes:
- FastRoute - router
- PHP-DI - depedency injection container
- twig template engine
- jquery
- bootstrap 4
- font awesome 4
- base64js.js (see misc)
- text-encoder-lite.js (see misc)

### Requirements
- Google Cloud Account with an active billing account
- service_credentials.json
- Client ID and Credentials for user Gmail access stored in Google Cloud Datastore

### Installation

Put your service credentials json in ./secrets and adjust the path/filename in .env  

Create a "config" entity in Datastore and add the entity type and id to the constants in SecretStore.php

```
docker-compose run --rm php-cli composer install
docker-compose run --rm php-cli composer dump-autoload
```

### Usage
```
docker-compose up -d
```
**Note: developing on an older mac? You might need docker-sync: TODO link** 


### Misc

Gmail uses base64url for the message body, but atob/btoa doesn't handle unicode  
https://developer.mozilla.org/en-US/docs/Web/API/WindowBase64/Base64_encoding_and_decoding

See Unicode problem - Solution 2

https://github.com/beatgammit/base64-js
https://github.com/coolaj86/TextEncoderLite