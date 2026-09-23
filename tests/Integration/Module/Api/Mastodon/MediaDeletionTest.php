<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Mastodon;

use Friendica\App\Router;
use Friendica\Content\Post\Entity\PostMedia;
use Friendica\Core\EarlyExitException;
use Friendica\Core\Storage\Type\Database;
use Friendica\DI;
use Friendica\Model\Attach;
use Friendica\Model\Item;
use Friendica\Module\Api\Mastodon\Media;
use Friendica\Network\HTTPException;
use Friendica\Test\ApiTestCase;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;

/** Verifies deletion against real media references and database-backed storage. */
class MediaDeletionTest extends ApiTestCase
{
	private const POST_URI_ID       = 1; // Live parent status from api.fixture.php.
	private const PHOTO_RESOURCE_ID = '0123456789abcdef0123456789abcdef';
	private const CONTENT           = 'Media deletion regression fixture';

	protected function setUp(): void
	{
		parent::setUp();
		$this->useHttpMethod(Router::DELETE);
		DI::config()->set('storage', 'name', Database::NAME);
	}

	/** @return array<string, array{0: string, 1: bool, 2: bool, 3: bool, 4?: int, 5?: bool}> */
	public static function deletionCases(): array
	{
		return [
			'owned unused photo'      => ['photo', true, false, false],
			'owned posted photo'      => ['photo', true, true, false],
			'owned scaled photo'      => ['photo', true, true, false, 1],
			'owned preview photo'     => ['photo', true, true, false, 2, true],
			'owned image-post photo'  => ['photo', true, true, true],
			'foreign photo'           => ['photo', false, false, false],
			'owned unused attachment' => ['attach', true, false, false],
			'owned posted attachment' => ['attach', true, true, false],
			'foreign attachment'      => ['attach', false, false, false],
		];
	}

	#[DataProvider('deletionCases')]
	public function testDeletionPreservesInUseAndForeignMedia(string $table, bool $owned, bool $inUse, bool $imagePost, int $scale = 0, bool $preview = false): void
	{
		$uid = $owned ? self::SELF_USER['id'] : self::OTHER_USER['id'];
		if (!$owned) {
			self::assertTrue(DI::dba()->insert('user', ['uid' => $uid, 'nickname' => 'media-owner']));
		}

		if ($table === 'attach') {
			$id = Attach::store(self::CONTENT, $uid, 'fixture.txt', 'text/plain');
			self::assertIsInt($id);
			$apiId = 'attach:' . $id;
			$url   = DI::baseUrl() . '/attach/' . $id;
		} else {
			$reference = DI::storage()->put(self::CONTENT);
			self::assertTrue(DI::dba()->insert('photo', [
				'uid'           => $uid,
				'resource-id'   => self::PHOTO_RESOURCE_ID,
				'filename'      => 'fixture.png',
				'type'          => 'image/png',
				'scale'         => $scale,
				'data'          => '',
				'backend-class' => Database::NAME,
				'backend-ref'   => $reference,
			]));
			$id    = (int) DI::dba()->lastInsertId();
			$apiId = (string) $id;
			$url   = DI::baseUrl() . '/photo/' . self::PHOTO_RESOURCE_ID . '-' . $scale . '.png';
		}

		$media = DI::dba()->selectFirst($table, ['backend-ref'], ['id' => $id]);
		self::assertIsArray($media);
		self::assertSame(self::CONTENT, DI::storage()->get($media['backend-ref']));

		if ($inUse) {
			if ($imagePost) {
				self::assertTrue(DI::dba()->update('post', ['post-type' => Item::PT_IMAGE], ['uri-id' => self::POST_URI_ID]));
				self::assertTrue(DI::dba()->update('post-user', ['post-type' => Item::PT_IMAGE], ['uri-id' => self::POST_URI_ID, 'uid' => $uid]));
				self::assertTrue(DI::dba()->update('post-content', ['resource-id' => self::PHOTO_RESOURCE_ID], ['uri-id' => self::POST_URI_ID]));
			}
			self::assertTrue(DI::dba()->exists('post-user', ['uri-id' => self::POST_URI_ID, 'uid' => $uid, 'deleted' => false]));
			self::assertTrue(DI::dba()->insert('post-media', [
				'uri-id'    => self::POST_URI_ID,
				'url'       => $preview ? 'https://example.com/article' : $url,
				'preview'   => $preview ? $url : null,
				'attach-id' => $table === 'attach' ? $id : null,
				'type'      => $table === 'attach' ? PostMedia::TYPE_DOCUMENT : PostMedia::TYPE_IMAGE,
			]));
		}

		$status         = $this->deleteMedia($apiId);
		$shouldPreserve = !$owned || $inUse;

		self::assertSame($shouldPreserve, DI::dba()->exists($table, ['id' => $id]), 'In-use or foreign media must survive; unused owned media must be removed.');
		self::assertSame($shouldPreserve, DI::dba()->exists('storage', ['id' => $media['backend-ref']]), 'Apply the same protection to the stored bytes.');
		if ($shouldPreserve) {
			self::assertSame(self::CONTENT, DI::storage()->get($media['backend-ref']));
			self::assertGreaterThanOrEqual(400, $status);
			self::assertLessThan(500, $status);
		} else {
			self::assertGreaterThanOrEqual(200, $status);
			self::assertLessThan(300, $status);
		}

		if ($inUse) {
			self::assertTrue(DI::dba()->exists('post-user', ['uri-id' => self::POST_URI_ID, 'uid' => $uid, 'deleted' => false]));
			self::assertTrue(DI::dba()->exists('post-media', ['uri-id' => self::POST_URI_ID, $preview ? 'preview' : 'url' => $url]));
		}
	}

	private function deleteMedia(string $id): int
	{
		$module = new Media(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['id' => $id]);
		try {
			return $module->handleRequest(new ServerRequest(Router::DELETE, DI::baseUrl() . '/api/v1/media/' . $id))->getStatusCode();
		} catch (EarlyExitException $exception) {
			return $exception->getResponse()->getStatusCode();
		} catch (HTTPException $exception) {
			return $exception->getCode();
		}
	}
}
