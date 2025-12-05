#php src/BuildPhar.php
php box-4.6.7.phar compile

$(php -r "file_put_contents('bin/webp8.sha256', hash_file('sha256', 'bin/webp8.phar'));")
