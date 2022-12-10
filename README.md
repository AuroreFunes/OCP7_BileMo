# OCP7_BileMo
OpenClassrooms - Project 7 - BileMo

It is an API that allows customers to create and delete users, and consult the catalog of available phones.

## Prerequisites

You must have PHP 7.2.x and a database that you can manage freely. You must also have Composer installed and have a terminal to use the command lines.  
OpenSSL is required for JWT authentication.


## Installation procedure

First, copy all the project files.

### Dependencies

Go to the project directory to launch the installation of Symfony and its dependencies :
> composer install


### Environment settings

Rename the ".env.example" file to ".env" and complete it as needed.


### Symfony server

You have to make your database engine work.

Then, go to the project directory to start the Symfony server with the command :
> symfony server:start


### Database

* Edit the ".env" file to fill in the connection information to your database :
> DATABASE_URL=

* Create the database using this command :
> php bin/console doctrine:database:create

* Then create the database structure by launching the migrations :
> php bin/console doctrine:migrations:migrate

* Create the first data from the Fixtures :
> php bin/console doctrine:fixtures:load


### SSL keys

To be able to connect with JWT, you must create the SSL keys.  

First, create the "config/jwt" folder if it doesn't already exist.  

Then create the private and public keys with the following commands :  
> openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096  
> openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout  

The "passphrase" you choose must be filled in the ".env" file :
> JWT_PASSPHRASE=yourSecretPassphrase


## Tests

You can use tests with phpunit to make sure the application is working properly.  

* Create the test database using this command :
> symfony console doctrine:database:create --env=test

* Then create the database structure by launching the migrations :
> symfony console doctrine:migrations:migrate -n --env=test

* Create the data for testing from the Fixtures :
> symfony console doctrine:fixtures:load --env=test  

If the results are not as expected :  

* Replay the fixtures  

* Empty the caches with the command :
> php bin/console cache:clear --env=test


## Parameters

You can change the number of phones and users that will be loaded per page in the "config/services.yaml" settings file :  
> parameters:  
>     phones_per_page: 10  
>     users_per_page: 5  

Remember to empty the caches :  
> php bin/console cache:clear