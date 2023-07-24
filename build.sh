# remove development dependencies from phar
composer install --no-dev

#php src/BuildPhar.php
php box.phar compile

$(php -r "file_put_contents('bin/webp8.sha256', hash_file('sha256', 'bin/webp8.phar'));")

composer install
