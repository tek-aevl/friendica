<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

use Dice\Dice;

$start_time = microtime(true);

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
	die('Vendor path not found. Please execute "bin/composer.phar --no-dev install" on the command line in the web root.');
}

require __DIR__ . '/vendor/autoload.php';

$request = \GuzzleHttp\Psr7\ServerRequest::fromGlobals();

$dice = (new Dice())->addRules(require(__DIR__ . '/static/dependencies.config.php'));

$container = \Friendica\Core\Container::fromDice($dice);
$app       = \Friendica\App::fromContainer($container);

$app->processRequest($request, $start_time);
