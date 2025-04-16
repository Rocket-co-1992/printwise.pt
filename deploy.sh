#!/bin/bash

# PrintWise Deployment Script
echo "Starting PrintWise deployment..."

# Create destination directory if it doesn't exist
DEST_DIR="deploy_package"
mkdir -p $DEST_DIR

# Copy necessary files and directories
echo "Copying application files..."

# Core application files
cp -r app $DEST_DIR/
cp -r config $DEST_DIR/
cp -r public $DEST_DIR/
cp -r resources $DEST_DIR/
cp -r src $DEST_DIR/
cp -r vendor $DEST_DIR/

# Configuration files
cp composer.json $DEST_DIR/
cp composer.lock $DEST_DIR/

# Exclude development files
rm -rf $DEST_DIR/config/*.local.php
rm -rf $DEST_DIR/public/dev
rm -rf $DEST_DIR/public/test.php

# Create .env file
echo "APP_ENV=production" > $DEST_DIR/.env

# Create htaccess for root directory
echo "
# Prevent access to sensitive files
<FilesMatch \"^(\.env|composer\.json|composer\.lock)$\">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Redirect all requests to public folder
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
" > $DEST_DIR/.htaccess

echo "Creating ZIP archive..."
zip -r printwise_deploy.zip $DEST_DIR/*

echo "Cleaning up..."
rm -rf $DEST_DIR

echo "Deployment package created: printwise_deploy.zip"
echo "Upload this package to your web host and extract it to your web directory."
echo "Don't forget to update the config files with your production settings!"
