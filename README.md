web8p is a command line tool to convert images to webp format

# how to install

    curl -L -o webp8.phar https://github.com/8ctopus/webp8/releases/download/v0.1.4/webp8.phar

    # check hash against the one published under releases
    sha256sum webp8.phar
    
    # make phar executable
    chmod +x webp8.phar
    
    # rename phar (optional)
    mv webp8.phar webp8

    # move phar to /usr/local/bin/ (optional)
    mv webp8 /usr/local/bin/
    
## convert images to webp

    ./webp8 convert [-v] directory

    [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 15843/15843 (100%) -   1 hr/1 hr   - 6.0 MiB

    [OK]

    ------- ----------- --------- ------------- ------- --------------- -----------
     total   converted   skipped   webp bigger   time    size original   size webp
    ------- ----------- --------- ------------- ------- --------------- -----------
     15843   15843       0         235           64:20   1.2 GB          150.3 MB
    ------- ----------- --------- ------------- ------- --------------- -----------

## delete existing webp images

    ./webp8 cleanup [--dry-run] [-v] directory

# build phar

    php src/Compiler.php
