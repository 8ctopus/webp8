# remove development dependencies from phar
composer install --no-dev

php src/BuildPhar.php

composer install
