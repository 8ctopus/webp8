# webp8

[![license](http://poser.pugx.org/8ctopus/self-sign/license)](https://packagist.org/packages/8ctopus/self-sign)
![lines of code](https://raw.githubusercontent.com/8ctopus/self-sign/image-data/lines.svg)

webp8 is a command line tool to convert images to webp format

## how to install

```sh
# download webp8
curl -LO https://github.com/8ctopus/webp8/releases/download/1.0.0/webp8.phar

# check hash against the one published under releases
sha256sum webp8.phar

# make phar executable
chmod +x webp8.phar

# rename phar (optional)
mv webp8.phar webp8

# move phar to /usr/local/bin/ (optional)
mv webp8 /usr/local/bin/
```

### Windows only

- download and extract cwebp.exe from [libwebp](https://storage.googleapis.com/downloads.webmproject.org/releases/webp/libwebp-1.2.0-windows-x64.zip)

- add cwebp.exe to PATH (sysdm.cpl -> Advanced -> Environment Variables)

- download webp8, curl -LO https://github.com/8ctopus/webp8/releases/download/1.0.0/webp8.phar

- check hash against the one published under releases

## convert images to webp

```sh
./webp8 convert [--cwebp_m] [-cwebp_q] [--cwebp_z] [--multithreading] directory

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

## build phar

    ./build.sh

## debug code

    php src/EntryPoint.php convert -- images

    # cleanup test dir
    git clean -xfd images

# improvement ideas

- check that converted image does not have a size of 0
- add hint on how to install on windows
