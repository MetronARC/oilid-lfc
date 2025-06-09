#!/bin/bash

# Default values
ENV_FILE=".env"
CONTAINER_NAME="oilid_app"

# Function to set an environment variable
set_env_var() {
    local key=$1
    local value=$2
    
    # Check if the key already exists in the file
    if grep -q "^${key}[[:space:]]*=" "$ENV_FILE"; then
        # Replace existing value
        sed -i "s|^${key}[[:space:]]*=.*|${key} = ${value}|" "$ENV_FILE"
    else
        # Add new key-value pair
        echo "${key} = ${value}" >> "$ENV_FILE"
    fi
}

# Example usage:
# ./set-env.sh CI_ENVIRONMENT development
# ./set-env.sh app.baseURL http://localhost:8081/
# ./set-env.sh database.default.hostname db

if [ "$#" -lt 2 ]; then
    echo "Usage: $0 KEY VALUE"
    echo "Example: $0 CI_ENVIRONMENT development"
    exit 1
fi

KEY=$1
VALUE=$2

# Set the environment variable
set_env_var "$KEY" "$VALUE"

echo "Environment variable set: $KEY = $VALUE"

# Restart the container to apply changes
echo "Restarting container to apply changes..."
docker restart $CONTAINER_NAME

echo "Done! Changes applied." 