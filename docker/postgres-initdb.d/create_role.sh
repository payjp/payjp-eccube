#!/bin/bash
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" <<'HERE'
CREATE USER eccube ENCRYPTED PASSWORD 'eccube';
CREATE DATABASE eccube;
GRANT ALL PRIVILEGES ON DATABASE eccube TO eccube;
HERE
