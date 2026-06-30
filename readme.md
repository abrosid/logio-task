# About The Project


This project is a simple PHP [Nette](https://nette.org) web application  that demonstrates how to set up Xdebug with Docker and PHPStorm or VS Code for debugging purposes. It includes a basic controller that retrieves product information from either ElasticSearch or MySQL, caches the results, and tracks the number of queries made for each product.
See the [task.md](task.md) file for detailed instructions on how to implement the controller and caching mechanism.

## Prerequisites

- Docker and Docker Compose installed on your machine
- PHPStorm or VS Code installed for debugging
- Basic knowledge of PHP and Nette framework

## Installation

1. Clone the repository:
2. Navigate to the project directory:
    ```bash
    cd logio-task
    ```
3. Copy [.env.sample](.env.sample) to [.env](.env) and update the environment variables as needed:
    ```bash
    cp .env.sample .env
    ```
4. Build and start the Docker containers:
    ```bash
    docker compose up -d --build
    ```
5. Install PHP dependencies using Composer:
    ```bash
    docker exec php composer install
    ```
6. Run the following command to give executable permissions to the composer scripts:
    ```bash
    docker exec php composer post-setup
    ```
7. Access the application in your browser at [http://localhost:8080](http://localhost:8080).
8. Navigate to the endpoint.  [http://localhost:8080/propduct/detail/123](http://localhost:8080/product/detail/123).
Each next request to the same endpoint will return the cached result and increment the query count for that product..
9. Change the mySQLAdapter to elasticSearchAdapter in the [services.neon](nette/config/services.neon) file to switch the data source from MySQL to ElasticSearch. Clear the product cache from [data](data) folder and repeat step 8 to see the storage changes.

# Debugging with Xdebug

## Prerequisites

- [VS Code](https://code.visualstudio.com/) or [PHPStorm](https://www.jetbrains.com/phpstorm/) installed
- [Docker](https://www.docker.com/) & [Docker Desktop](https://www.docker.com/products/docker-desktop/) running
- The [Xdebug](https://xdebug.org/) extension properly configured in your Docker image


#### VSCode Xdebug Setup
For detailed instructions on setting up Xdebug with VS Code, refer to the [VSCODE_XDEBUG_SETUP.md](docker/php/VSCODE_XDEBUG_SETUP.md) file.
#### PHPStorm Xdebug Setup
For detailed instructions on setting up Xdebug with PHPStorm, refer to the [PHPSTORM_XDEBUG_SETUP.md](docker/php/PHPSTORM_XDEBUG_SETUP.md) file.



Troubleshooting
=

**Composer scripts - permission issues**

Run the following command to give executable permissions to the composer scripts:
```
 docker exec php composer post-setup
 ```
