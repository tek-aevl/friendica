<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Core;

use Friendica\Core\Addon\AddonInfo;
use PHPUnit\Framework\TestCase;

class AddonInfoTest extends TestCase
{
	public function testFromArrayCreatesObject(): void
	{
		$data = [
			'id'          => '',
			'name'        => '',
			'description' => '',
			'author'      => [],
			'maintainer'  => [],
			'version'     => '',
			'status'      => '',
		];

		$this->assertInstanceOf(AddonInfo::class, AddonInfo::fromArray($data));
	}

	public function testGetterReturningCorrectValues(): void
	{
		$data = [
			'id'          => 'test',
			'name'        => 'Test-Addon',
			'description' => 'This is an addon for tests',
			'author'      => ['name' => 'Sam'],
			'maintainer'  => ['name' => 'Sam', 'link' => 'https://example.com'],
			'version'     => '0.1',
			'status'      => 'In Development',
		];

		$info = AddonInfo::fromArray($data);

		$this->assertSame($data['id'], $info->getId());
		$this->assertSame($data['name'], $info->getName());
		$this->assertSame($data['description'], $info->getDescription());
		$this->assertSame($data['description'], $info->getDescription());
		$this->assertSame($data['author'], $info->getAuthor());
		$this->assertSame($data['maintainer'], $info->getMaintainer());
		$this->assertSame($data['version'], $info->getVersion());
		$this->assertSame($data['status'], $info->getStatus());
	}
}
