# Junior PHP Developer (Symfony focus) Assignment 

Symfony 6 RESTful API to manage and blacklist IP address information using OpenAPI Specification.

## Functionality
1. Retrieve IP Information and add it to the database
2. Delete IP Information from the database
3. Add an IP address to the blacklist
4. Remove an IP address from the blacklist
   
## Getting Started
1. Make sure you have Docker and Docker Compose installed on your system
2. Clone this repository
3. Navigate to the repository directory
4. Run `docker-compose up -d`
5. Run `docker-compose exec php composer install`
6. Access the API at http://localhost:8080/api/doc

## Database migration
1. `php bin/console make:migration`
2. `php bin/console doctrine:migrations:migrate`

## IPStack
The project utilizes [ipstack-client](https://github.com/GitHubHubus/ipstack-client/tree/master) package to retrieve IP information from [Ipstack](https://ipstack.com/) API.

Requires an API Access Key to retrieve IP information.