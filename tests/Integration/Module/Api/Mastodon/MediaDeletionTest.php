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
use Friendica\Model\Post;
use Friendica\Module\Api\Mastodon\Media;
use Friendica\Test\ApiTestCase;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;

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

	/** @return array<string, array{0: string, 1: bool, 2: bool, 3: bool, 4?: int, 5?: bool, 6?: bool, 7?: bool}> */
	public static function deletionCases(): array
	{
		return [
			'photo still used by another post'      => ['photo', true, true, false, 0, false, true, true],
			'attachment still used by another post' => ['attach', true, true, false, 0, false, true, true],
			'owned unused photo'                    => ['photo', true, false, false],
			'owned posted photo'                    => ['photo', true, true, false],
			'owned scaled photo'                    => ['photo', true, true, false, 1],
			'owned preview photo'                   => ['photo', true, true, false, 2, true],
			'owned image-post photo'                => ['photo', true, true, true],
			'foreign photo'                         => ['photo', false, false, false],
			'owned unused attachment'               => ['attach', true, false, false],
			'owned posted attachment'               => ['attach', true, true, false],
			'foreign attachment'                    => ['attach', false, false, false],
			'photo after post deletion'             => ['photo', true, true, false, 0, false, true],
			'attachment after post deletion'        => ['attach', true, true, false, 0, false, true],
		];
	}

	#[DataProvider('deletionCases')]
	public function testDeletionPreservesInUseAndForeignMedia(string $table, bool $owned, bool $inUse, bool $imagePost, int $scale = 0, bool $preview = false, bool $deletePost = false, bool $otherPost = false): void
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

		if ($deletePost) {
			if ($otherPost) {
				// Independent parent status from api.fixture.php, outside the deleted thread.
				self::assertTrue(DI::dba()->insert('post-media', [
					'uri-id'    => 3,
					'url'       => $url,
					'attach-id' => $table === 'attach' ? $id : null,
					'type'      => $table === 'attach' ? PostMedia::TYPE_DOCUMENT : PostMedia::TYPE_IMAGE,
				]));
			}
			$post = Post::selectFirst(['id'], ['uri-id' => self::POST_URI_ID, 'uid' => $uid]);
			self::assertIsArray($post);
			// Run the status endpoint's deletion operation without scheduling delivery workers.
			self::assertTrue(Item::markForDeletionById((int) $post['id'], 0));
			self::assertFalse(DI::dba()->exists('post-user', ['uri-id' => self::POST_URI_ID, 'deleted' => false]));
			self::assertTrue(DI::dba()->exists($table, ['id' => $id]));
			if ($otherPost) {
				self::assertTrue(DI::dba()->exists('post-user', ['uri-id' => 3, 'deleted' => false]));
			}
		}

		$response       = $this->deleteMedia($apiId);
		$shouldPreserve = !$owned || ($inUse && !$deletePost) || $otherPost;
		self::assertSame(!$owned ? 404 : ($shouldPreserve ? 422 : 200), $response->getStatusCode());

		self::assertSame($shouldPreserve, DI::dba()->exists($table, ['id' => $id]), 'In-use or foreign media must survive; unused owned media must be removed.');
		self::assertSame($shouldPreserve, DI::dba()->exists('storage', ['id' => $media['backend-ref']]), 'Apply the same protection to the stored bytes.');
		if ($shouldPreserve) {
			self::assertSame(self::CONTENT, DI::storage()->get($media['backend-ref']));
		} else {
			self::assertEquals(new \stdClass(), json_decode((string) $response->getBody(), false, 512, JSON_THROW_ON_ERROR));
		}

		if ($inUse && !$deletePost) {
			self::assertTrue(DI::dba()->exists('post-user', ['uri-id' => self::POST_URI_ID, 'uid' => $uid, 'deleted' => false]));
			self::assertTrue(DI::dba()->exists('post-media', ['uri-id' => self::POST_URI_ID, $preview ? 'preview' : 'url' => $url]));
		}
	}

	/** @return array<string, array{string, string}> */
	public static function missingMedia(): array
	{
		return ['photo' => ['photo', ''], 'attachment' => ['attach', 'attach:']];
	}

	#[DataProvider('missingMedia')]
	public function testMissingMediaReturnsNotFound(string $table, string $prefix): void
	{
		$id = '2147483647';
		self::assertFalse(DI::dba()->exists($table, ['id' => $id]));
		$response = $this->deleteMedia($prefix . $id);
		self::assertSame(404, $response->getStatusCode());
		self::assertObjectHasProperty('error', json_decode((string) $response->getBody(), false, 512, JSON_THROW_ON_ERROR));
	}

	private function deleteMedia(string $id): ResponseInterface
	{
		$module = new Media(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['id' => $id]);
		try {
			return $module->handleRequest(new ServerRequest(Router::DELETE, DI::baseUrl() . '/api/v1/media/' . $id));
		} catch (EarlyExitException $exception) {
			return $exception->getResponse();
		}
	}
}
