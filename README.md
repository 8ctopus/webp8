web8p is a command line tool to convert images to webp format

# how to install

```sh
# download webp8
curl -LO https://github.com/8ctopus/webp8/releases/download/v0.1.5/webp8.phar

# check hash against the one published under releases
sha256sum webp8.phar

# make phar executable
chmod +x webp8.phar

# rename phar (optional)
mv webp8.phar webp8

# move phar to /usr/local/bin/ (optional)
mv webp8 /usr/local/bin/
```

# webp8 on Windows

```cmd
# download and extract libwebp
https://storage.googleapis.com/downloads.webmproject.org/releases/webp/libwebp-1.2.0-windows-x64.zip

# add libwebp to PATH (sysdm.cpl -> Advanced -> Environment Variables)

# download webp8
curl -LO https://github.com/8ctopus/webp8/releases/download/v0.1.5/webp8.phar

php webp8.phar
```

## convert images to webp

```sh
./webp8 convert [--multithreading] [-v] directory

[▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 15843/15843 (100%) -   1 hr/1 hr   - 6.0 MiB

[OK]

------- ----------- --------- ------------- ------- --------------- -----------
 total   converted   skipped   webp bigger   time    size original   size webp
------- ----------- --------- ------------- ------- --------------- -----------
 15843   15843       0         235           64:20   1.2 GB          150.3 MB
------- ----------- --------- ------------- ------- --------------- -----------
```

## delete existing webp images

```sh
./webp8 cleanup [--dry-run] [-v] directory
```

# htaccess code to show webp instead of png/jpg when browser supports

Code adapted from [webp-express](https://github.com/rosell-dk/webp-express)

```.htaccess
RewriteEngine On

# redirect images to webp when possible
# check if browser accepts webp
RewriteCond %{HTTP_ACCEPT} image/webp

# check if requested file is jpg or png
RewriteCond %{REQUEST_FILENAME} \.(jpe?g|png)$

# check if webp for image exists
RewriteCond %{REQUEST_FILENAME}\.webp -f

# serve webp image instead
RewriteRule . %{REQUEST_FILENAME}\.webp [T=image/webp,E=EXISTING:1,E=ADDVARY:1,L]

# make sure that browsers which do not support webp also get the Vary:Accept header
# when requesting images that would be redirected to existing webp on browsers that does.
SetEnvIf Request_URI "\.(jpe?g|png)$" ADDVARY

# Apache appends "REDIRECT_" in front of the environment variables defined in mod_rewrite, but LiteSpeed does not.
# So, the next lines are for Apache, in order to set environment variables without "REDIRECT_"
SetEnvIf REDIRECT_EXISTING 1 EXISTING=1
SetEnvIf REDIRECT_ADDVARY 1 ADDVARY=1

# Set Vary:Accept header for the image types handled by WebP Express.
# The purpose is to make proxies and CDNs aware that the response varies with the Accept header.
Header append "Vary" "Accept" env=ADDVARY
```

# build phar

```sh
php src/Compiler.php
```

# debug code
```sh
# install dependencies
composer install

# test webp8
php src/EntryPoint.php convert images
```

# improvement ideas

- check that image does not have size 0 after generation
