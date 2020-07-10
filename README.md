web8p is a command line tool to convert images to webp format

# how to install

    curl -L -o webp8.phar https://github.com/8ctopus/webp8/releases/download/v0.1.1/webp8.phar

## convert images to webp

    php webp8.phar convert [-v] directory

## delete existing webp images

    php webp8.phar cleanup [--dry-run] [-v] directory

# build phar

    php src/Compiler.php
