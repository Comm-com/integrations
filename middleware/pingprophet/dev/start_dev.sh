#!/bin/bash

#not used in production.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd $SCRIPT_DIR && cd ../
cp .env.example .env
docker-compose up -d --build
