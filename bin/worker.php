#!/usr/bin/env php
<?php
/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Starts the background processing
 *
 * @deprecated 2025.02 use bin/console.php worker instead
 */

if (php_sapi_name() !== 'cli') {
	header($_SERVER["SERVER_PROTOCOL"] . ' 403 Forbidden');
	exit();
}

// Ensure that worker.php is executed from the base path of the installation
chdir(dirname(__DIR__));

require dirname(__DIR__) . '/vendor/autoload.php';

$argv = $_SERVER['argv'] ?? [];
array_splice($argv, 1, 0, "worker");

$container = \Friendica\Core\DiceContainer::fromBasePath(dirname(__DIR__));

$app = \Friendica\App::fromContainer($container);

$app->processConsole($argv);
