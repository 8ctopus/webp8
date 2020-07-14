web8p is a command line tool to convert images to webp format

# how to install

    curl -L -o webp8.phar https://github.com/8ctopus/webp8/releases/download/v0.1.3/webp8.phar

    # check hash against the one published under releases
    sha256sum webp8.phar
    
    # make phar executable
    chmod +x webp8.phar
    
## convert images to webp

    ./webp8.phar convert [-v] directory

## delete existing webp images

    ./webp8.phar cleanup [--dry-run] [-v] directory

# build phar

    php src/Compiler.php
