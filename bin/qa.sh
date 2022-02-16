#!/usr/bin/env bash
echo "Fix coding style"
./vendor/bin/phpcbf

echo "Check for remaining coding style errors"
./vendor/bin/phpcs -p --ignore=tests

echo "Run PHPMD"
./vendor/bin/phpmd src/ text phpmd-ruleset.xml

echo "Run PHPStan"
./vendor/bin/phpstan analyse -c phpstan.neon

echo "Test package from within phpunit"
./vendor/bin/testbench migrate:fresh --path=tests/Setup/Database/Migrations --realpath --seed --seeder=Tests\\Setup\\Database\\Seeds\\DatabaseSeeder
./vendor/bin/testbench package:test
