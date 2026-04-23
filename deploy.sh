#!/bin/bash

echo "🚀 Deploying latest changes..."

cd /home/mkomigbo/public_html || exit

git pull origin main

if [ $? -ne 0 ]; then
  echo "❌ Git pull failed"
  exit 1
fi

echo "🔄 Clearing caches..."
rm -rf cache/* 2>/dev/null

echo "✅ Deployment complete"
