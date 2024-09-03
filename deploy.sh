#!/bin/bash

# Function to display usage
usage() {
  echo "Usage: $0 --staging | --production"
  exit 1
}

# Check if the correct number of arguments is provided
if [ "$#" -ne 1 ]; then
  usage
fi

# Determine the environment and set the Docker Compose file
case "$1" in
  --staging)
    COMPOSE_FILE="docker-compose-staging.yml"
    ;;
  --production)
    COMPOSE_FILE="docker-compose-production.yml"
    ;;
  *)
    usage
    ;;
esac

# Pull the latest changes from the main branch
git pull origin main

# Stop any running services from the selected Docker Compose file
docker-compose -f $COMPOSE_FILE stop

# Re-run the docker containers
docker-compose -f $COMPOSE_FILE up -d

# Access the node container and execute the required commands  
docker exec -it msp_node sh -c "composer install && npm run build"

# Access the PHP container and execute the required commands
docker exec -it msp_php sh -c "
  composer install &&
  php artisan migrate &&
  php artisan route:clear &&
  php artisan config:clear &&
  php artisan view:clear &&
  php artisan cache:clear &&
  php artisan queue:work
"