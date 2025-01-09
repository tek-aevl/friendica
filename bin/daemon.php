#!/usr/bin/env php
<?php
/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * @deprecated 2025.02 use bin/console.php daemon instead
 */

/**
 * Run the worker from a daemon.
 *
 * This script was taken from http://php.net/manual/en/function.pcntl-fork.php
 */
if (php_sapi_name() !== 'cli') {
	header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
	exit();
}

use Dice\Dice;

// Ensure that daemon.php is executed from the base path of the installation
chdir(dirname(__DIR__));

require dirname(__DIR__) . '/vendor/autoload.php';

$dice = (new Dice())->addRules(require(dirname(__DIR__) . '/static/dependencies.config.php'));

$argv = $_SERVER['argv'] ?? [];
array_splice($argv, 1, 0, "daemon");

$container = \Friendica\Core\Container::fromDice($dice);
\Friendica\Core\Console::create($container, $argv)->execute();
