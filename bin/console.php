#!/usr/bin/env php
<?php
/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 */

if (php_sapi_name() !== 'cli') {
	header($_SERVER["SERVER_PROTOCOL"] . ' 403 Forbidden');
	exit();
}

use Dice\Dice;

require dirname(__DIR__) . '/vendor/autoload.php';

$dice = (new Dice())->addRules(require(dirname(__DIR__) . '/static/dependencies.config.php'));

$container = \Friendica\Core\Container::fromDice($dice);
\Friendica\Core\Console::create($container, $_SERVER['argv'] ?? [])->execute();
