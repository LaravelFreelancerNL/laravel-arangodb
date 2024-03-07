#!/usr/bin/env bash
echo "Fix coding style"
./vendor/bin/pint

echo "Run PHPMD"
./vendor/bin/phpmd src/ text phpmd-ruleset.xml

echo "Test package from within phpunit"
./vendor/bin/testbench migrate:fresh --path=tests/Setup/Database/Migrations --path=vendor/orchestra/testbench-core/laravel/migrations/ --realpath --seed
./vendor/bin/testbench package:test --coverage --min=80 tests

echo "Run PHPStan"
./vendor/bin/phpstan analyse -c phpstan.neon

