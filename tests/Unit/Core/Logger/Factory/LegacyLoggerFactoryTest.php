<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Core\Logger\Factory;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hooks\Capability\ICanCreateInstances;
use Friendica\Core\Logger\Capability\LogChannel;
use Friendica\Core\Logger\Factory\LegacyLoggerFactory;
use Friendica\Util\Profiler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LegacyLoggerFactoryTest extends TestCase
{
	public function testCreateLoggerReturnsPsrLogger(): void
	{
		$factory = new LegacyLoggerFactory(
			$this->createStub(ICanCreateInstances::class),
			$this->createStub(IManageConfigValues::class),
			$this->createStub(Profiler::class),
		);

		$this->assertInstanceOf(
			LoggerInterface::class,
			$factory->createLogger(LogLevel::DEBUG, LogChannel::DEFAULT)
		);
	}
}
