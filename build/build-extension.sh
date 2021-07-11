#!/bin/bash

rm -Rf extension
mkdir extension
cp -R Classes extension/Classes
cp -R Configuration extension/Configuration
cp -R Resources extension/Resources
cp ext_* extension/.
cp README.md extension/README.md
cp LICENSE extension/LICENSE
cp build/release/composer.json extension/composer.json
cd extension && composer install --no-dev --optimize-autoloader
