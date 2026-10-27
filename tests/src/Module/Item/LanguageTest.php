<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Item;

use Friendica\Content\Item as ContentItem;
use Friendica\Core\EarlyExitException;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\DI;
use Friendica\Module\Item\Language;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Test\FixtureTestCase;
use GuzzleHttp\Psr7\ServerRequest;
use Mockery\MockInterface;

class LanguageTest extends FixtureTestCase
{
	private function createModule(ContentItem $item, bool $authenticated): Language
	{
		/** @var MockInterface&IHandleUserSessions $session */
		$session = \Mockery::mock(IHandleUserSessions::class);
		$session->shouldReceive('isAuthenticated')->andReturn($authenticated);

		return new Language($item, $session, DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);
	}

	private function detect(ContentItem $item, string $body): array
	{
		$request = (new ServerRequest('POST', 'https://friendica.local/item/language'))->withParsedBody(['body' => $body]);

		try {
			$this->createModule($item, true)->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) {
			self::assertEquals(200, $e->getResponse()->getStatusCode());

			return json_decode((string) $e->getResponse()->getBody(), true);
		}
	}

	public function testDetectsLanguageOfBody(): void
	{
		/** @var MockInterface&ContentItem $item */
		$item = \Mockery::mock(ContentItem::class);
		$item->shouldReceive('getLanguageArray')->with('Das ist ein deutscher Text', 1)->andReturn(['de' => 0.9]);

		self::assertEquals(['lang' => 'de'], $this->detect($item, 'Das ist ein deutscher Text'));
	}

	public function testUndeterminedLanguageIsReturnedAsIs(): void
	{
		/** @var MockInterface&ContentItem $item */
		$item = \Mockery::mock(ContentItem::class);
		$item->shouldReceive('getLanguageArray')->andReturn(['un' => 1]);

		self::assertEquals(['lang' => 'un'], $this->detect($item, '???'));
	}

	public function testRequiresAuthentication(): void
	{
		$item = \Mockery::mock(ContentItem::class);

		$request = (new ServerRequest('POST', 'https://friendica.local/item/language'))->withParsedBody(['body' => 'Text']);

		$this->expectException(ForbiddenException::class);
		$this->createModule($item, false)->handleRequest($request);
	}
}
