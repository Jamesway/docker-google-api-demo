# Google App Engine development without putting secrets in app.yaml using Docker

## Background

### .env (dotenv)
In general, it's a bad idea to keep secrets in any file that will ber version controlled.  
https://blog.github.com/2013-01-25-secrets-in-the-code/

My preferred method is to use a .env file for secrets and .gitignore the .env file. Laravel does this and it works well. Django requires a module like [django-dotenv](https://github.com/jpadilla/django-dotenv).  

It's a good idea to have a version controlled .env.example file with sample key/values defined for others to see which vars the app needs. A new dev can then git branch, copy the .env.example to .env and add secrets accordingly.  

Also, docker-compose supports .env files.


### Service Credentials
When you run an app on Google App Engine (GAE), it automatically inherits service account credentials of the account GAE is running under.  
[providing_credentials_to_your_application](https://cloud.google.com/docs/authentication/production#providing_credentials_to_your_application
)

First, GoogleClient() checks for env vars, if not set, it uses the default service account. *(Remember this)*  

What this means is an app running in GAE can access Google APIs using the credentials for the account executing GAE. So, we can, for instance, connect to Google Cloud Datastore (Datastore) without providing credentials.  

Ok, but let's say you want to use Google APIs to access user Gmail data...  

### Client ID Secret/Credentials for User Data
Accessing user data requires a Client ID secret and permission from the user (OAuth2) in the form of authorized credentials (access/refresh tokens).  
https://github.com/jamesway/gae-credentials-php71

Once you get the Client ID secret and credentials, where do you put them? 

## The Problem
Unfortunately, unlike Heroku, GAE, doesn't allow you to set environment variables for your service. GAE requires you to add your secrets to the app.yaml file to get then into GAE as env vars :(  
[Python_app_yaml_Defining_environment_variables](https://cloud.google.com/appengine/docs/flexible/python/configuring-your-app-with-app-yaml#Python_app_yaml_Defining_environment_variables)  
[PHP_app_yaml_Defining_environment_variables](https://cloud.google.com/appengine/docs/flexible/php/configuring-your-app-with-app-yaml#PHP_app_yaml_Defining_environment_variables)  

So how do we get our secrets into GAE without exposing them to version control?

## A Solution

### Google Cloud Datastore
Since GoogleClient() can use service credentials from GAE, we can store secrets in Datastore without exposing them in app.yaml.  
**Note: Google Cloud Datastore encrypts data in transit and at rest making it a suitable secret store.** 

Ok. Execution on GAE is covered, but my local environment doesn't have service credentials baked in.  
How do I develop for GAE and use Datastore for secrets?

### Local Secrets
What we really want to do is access the secrets in Datastore and cache them locally for:
1. performance - we avoid the round-trip on the wire
2. cost savings - datastore queries cost money, fewer is cheaper

To create a local secrets cache, create a /.secrets directory in the project root and **.gitingore it**.
  
Since we develop outside of GAE, we to pass in service credentials to access Google APIs such as datastore.  

### Generate Service Credentials for Development
Generate Service Credentials TODO add link
  

### Home Stretch
Once we generate a service credentials json file and store it in /.secrets, we need to pass the **location** of the service credentials as an ENV to the app.
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
- [Client ID and Credentials for user Gmail access stored in Google Cloud Datastore](https://github.com/jamesway/gae-credentials-php71)

### Installation

- Download your service credentials json to ./secrets and update the "GOOGLE_APPLICATION_CREDENTIALS" path in the .env file. While you're in the .env, set the project id.  
- Create a "secrets" entity in Datastore and add "client_id" and "credentials" properties
- Set the values for the properties to the client_id and credentials json you have from authenticating gmail access. [Still don't have these?](https://github.com/jamesway/gae-credentials-php71)
- Update config.php with the kind and id from your secrets entity
- Install packages
```
docker-compose run --rm php-cli composer install
docker-compose run --rm php-cli composer dump-autoload
```

### Usage

#### Docker
```
docker-compose up -d
```

Open a browser to [http://192.168.99.100:8080] or whatever your docker machine ip is.


#### GAE (technically still Docker)
```
gcloud app deploy --project [project_id]

... wait ...

gcloud app browse
```

### Misc

#### Developing on an older mac?
I added a _docker-compose-override.yml that uses a docker-sync container.  
To use it, remove the underscore from the filename.  

More about [docker-sync](https://github.com/jamesway/docker-cheatsheet)

#### Gmail Messasge Encoding
Gmail uses base64url for the message body, but atob/btoa doesn't handle unicode  

See [Base64 encoding and decoding](https://developer.mozilla.org/en-US/docs/Web/API/WindowBase64/Base64_encoding_and_decoding) Unicode problem - Solution 2
https://github.com/beatgammit/base64-js
https://github.com/coolaj86/TextEncoderLite