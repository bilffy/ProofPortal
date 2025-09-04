#!/bin/bash

# Function to display usage
usage() {
  echo "Usage: $0 --staging | --production [--build] [--install]"
  exit 1
}

# Check if at least one argument is provided
if [ "$#" -lt 1 ]; then
  usage
fi

# Initialize variables
BUILD_OPTION=false
INSTALL_OPTION=false

# Process input arguments
for arg in "$@"
do
  case "$arg" in
    --staging)
      COMPOSE_FILE="docker-compose-staging.yml"
      ;;
    --v1)
      COMPOSE_FILE="docker-compose-staging-v1.yml"
      ;;  
    --production)
      COMPOSE_FILE="docker-compose-production.yml"
      ;;
    --build)
      BUILD_OPTION=true
      ;;
    --install)
      INSTALL_OPTION=true
      ;;
    *)
      usage
      ;;
  esac
done

# Check if environment variable is set
if [ -z "$COMPOSE_FILE" ]; then
  usage
fi

# Pull the latest changes from the main branch
echo "Pulling the latest changes from the main branch"
git pull origin main

# Check if containers are already running
RUNNING_CONTAINERS=$(docker-compose -f $COMPOSE_FILE ps -q)

if [ -z "$RUNNING_CONTAINERS" ]; then
  echo "No running containers detected. Starting fresh..."
  docker-compose -f $COMPOSE_FILE up -d
else
  echo "Containers are already running..."
fi

# Re-run the docker containers with or without the --build flag
if [ "$BUILD_OPTION" = true ]; then
  echo "Re-running Docker containers with --build option"
  docker-compose -f $COMPOSE_FILE up -d --build
  # Access the node container and execute the required commands
  echo "Accessing the node container to run npm install and npm run build"
  docker exec -it msp_node sh -c "npm install && npm run build && exit;"
  # Access the PHP container and execute the required commands
  echo "Accessing the PHP container to run composer install commands"
  docker exec -it msp_php sh -c "
    composer install &&
    exit;
  "
else
  
  # Access the node container and execute npm install if --install is specified
  if [ "$INSTALL_OPTION" = true ]; then
    echo "Running npm install in the msp_node container"
    docker exec -it msp_node sh -c "npm install && rm -f public/hot && exit;"
    echo "Running composer install in the msp_php container"
    docker exec -it msp_php sh -c "composer install && exit;"
  fi
  
  echo "Accessing the node container to npm run build"
  docker exec -it msp_node sh -c "npm run build && exit;"
  # Access the PHP container and execute the required commands
  echo "Accessing the PHP container to run various Artisan commands"
  docker exec -it msp_php sh -c "
    php artisan migrate &&
    php artisan route:clear &&
    php artisan config:clear &&
    php artisan view:clear &&
    php artisan cache:clear &&
    exit;
  "
fi

pm2 startOrRestart pm2.config.json