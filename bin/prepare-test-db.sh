#!/bin/bash

. .env

DB_DATABASE=${DB_DATABASE:-'aranguent__test'}
DB_ENDPOINT=${DB_ENDPOINT:-'http://localhost:8529'}

echo Creating database: $DB_DATABASE

curl -X POST -u root: --header 'accept: application/json' --data-binary @- --dump - $DB_ENDPOINT/_api/database \
<<EOF
{
  "name" : "$DB_DATABASE"
}
EOF

echo Creating migration repository: migrations
curl -X POST -u root: --header 'accept: application/json' --data-binary @- --dump - $DB_ENDPOINT/_db/$DB_DATABASE/_api/collection \
<<EOF
{
  "name" : "migrations"
}
EOF

exit 0

