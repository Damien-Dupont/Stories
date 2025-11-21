#!/bin/bash
# Setup de la base de test

docker exec -it story_postgres psql -U story_user -d postgres -c \
"DROP DATABASE IF EXISTS story_app_test;"

docker exec -it story_postgres psql -U story_user -d postgres -c \
"CREATE DATABASE story_app_test OWNER story_user;"

docker exec -it story_postgres psql -U story_user -d story_app_test < ../database/init.sql

# Appliquer migrations
DB_NAME=story_app_test docker exec -it story_php php /var/www/scripts/migrate.php up

echo "✓ Base de test prête"