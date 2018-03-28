## Using Docker to develop for Google App Engine without putting secrets in app.yaml

## Background
In general, it's a bad idea to keep secrets in any file that will ber version controlled.  
https://blog.github.com/2013-01-25-secrets-in-the-code/

My preferred method is to use an .env file for secrets and .gitignore the .env file. Laravel does this. It's a good idea to add a version controlled .env.example file with sample key/values defined for others to see how the .env should look. Another dev can copy the .env.example to .env and add secrets accordingly.  
  
Docker-compose can read the .env file and pass the values to the container's environment.

So far so good.  

Unlike Heroku however, Google App Engine (GAE), doesn't allow you to set environment variables for your service. You need to add your secrets to the app.yaml file to get then into GAE :(  
https://cloud.google.com/appengine/docs/flexible/python/configuring-your-app-with-app-yaml#Python_app_yaml_Defining_environment_variables  
https://cloud.google.com/appengine/docs/flexible/php/configuring-your-app-with-app-yaml#PHP_app_yaml_Defining_environment_variables  

So how do we get our secrets into GAE without exposing them to version control?

## Solution

### Service Credentials
When you run an app on GAE, it automatically inherits service account credentials of the account GAE is running under.
https://cloud.google.com/docs/authentication/production#providing_credentials_to_your_application

First, GoogleClient() checks for env vars, if not set, it uses the default service account. *(Remember this)*  

What this means is an app running on App Engine can access Google APIs using the credentials for the account the executing GAE. So we can, for instance, connect to cloud datastore without providing credentials.  

Ok, but let's say you want to use Google APIs to access user data such as Gmail...  

### Client ID secret/Credentials for user data
Accessing user data requires a Client ID secret and permission from the user (OAuth2) in the form of authorized credentials (access/refresh tokens).  

Once you get the Client ID secret and credentials: https://github.com/Jamesway/gae-credentials-php71  

Where do you put them?

### Google Cloud Datastore
Since GAE can access cloud datastore without us inputting credentials, we can store secrets in cloud datastore without exposing them in app.yaml.  
**Note: Google Cloud Datastore encrypts data in transit and at rest making it a suitable secret store.** 

That's great, but my local environment doesn't have service credentials baked in so how do I develop for GAE and use datastore for secrets.

### Local Secrets for Development
What we really want to do is access the secrets in datastore and cache them locally for performance, but also so we're not charged for bunch of unnecessary datastore queries - this helps for development and when deployed on GAE. 

Also, we still need service credentials to access Google APIs including datastore and so we need to generate a service credentials json file and store it locally. To do this, I create a /.secrets directory and .gitingore it. It contains the service credentials that allow us to connect to datastore and subsequently, the cached secrets from the datastore.  

### Home Stretch
I use the .env file to store the location of the service credentials and docker-compose to pass that env value to the container's environment where GoogleClient() looks for it.  
Remember... "First the GoogleClient() checks for env vars, if not set, it uses the default service account."  

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


## A Demo with Gmail

...

### Misc

https://developer.mozilla.org/en-US/docs/Web/API/WindowBase64/Base64_encoding_and_decoding
Unicode problem - solution 2

https://github.com/beatgammit/base64-js

https://github.com/coolaj86/TextEncoderLite