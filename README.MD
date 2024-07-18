# MSP Local Environment Setup using Docker

---

This guide will walk you through setting up your local development environment for MSP App using Docker.

### Prerequisites:
Before you begin, ensure you have the following installed on your system:
- Docker
- Docker Compose

### Installation:
1. Clone the MSP repository:

```
$ git clone <repository_url>
```
2. Navigate to the project directory:
``` 
$ cd <app_directory>
```
3. Copy the file `.env.example` to `.env` file

Make sure to replace your_database_name, your_database_user, and your_database_password with your preferred values.

4. Start the Docker containers by running the following command:
 
```
$ docker-compose up -d --build
```
This command will build the necessary Docker container and images and start the services defined in the docker-compose.yml file.

5. Ensure to access the PHP container and run `composer install`:
```
$ docker-compose exec php bash

$ composer install

$ php artisan migrate
```

6. Once the containers are up and running, you can access the App at http://localhost:8080.

### Installation (Frontend):
After running backend, check docker container for frontend (msp-node) and run `npm install`
```
$ docker-compose exec node bash

$ npm install
```

### Additional Notes:
You can customize ports and environment variables in the .env file to suit your preferences.
Make sure Docker is running and properly configured on your system before starting the containers.

### Troubleshooting
If you encounter any issues during setup or while running the application, please refer to the following troubleshooting steps:

- Check the Docker logs for any error messages.
- Ensure all required ports are not occupied by other applications.
- Verify that Docker Compose is properly installed and configured on your system.

If the issue persists, feel free to reach out to the development team for assistance.

