#!/usr/bin/env bash
printf "\nRun tests\n"
./vendor/bin/testbench migrate:fresh --path=tests/Setup/Database/Migrations --realpath --seed --seeder=Tests\\Setup\\Database\\Seeds\\DatabaseSeeder
./vendor/bin/testbench package:test
