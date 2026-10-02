CREATE ROLE comptoir WITH LOGIN PASSWORD 'comptoir' CREATEDB;
CREATE DATABASE comptoir OWNER comptoir;
CREATE DATABASE comptoir_test OWNER comptoir;
CREATE DATABASE comptoir_e2e OWNER comptoir;
