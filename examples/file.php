<?php

require dirname(__DIR__) . '/vendor/autoload.php';

// php file.php ./assets/example.txt

$cmd = new CommandoX\Command();
$cmd->argument()->expectsFile();

var_dump($cmd->getArgumentValues());
