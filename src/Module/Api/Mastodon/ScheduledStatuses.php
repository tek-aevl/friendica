<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon;

use Friendica\App\Router;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/statuses/scheduled_statuses/
 */
class ScheduledStatuses extends BaseApi
{
	public function put(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$this->response->unsupported(Router::PUT, $request);
	}

	protected function delete(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		if (!DBA::exists('delayed-post', ['id' => $this->parameters['id'], 'uid' => $uid])) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		Post\Delayed::deleteById($this->parameters['id']);

		$this->jsonExit([]);
	}

	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function get(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		if (isset($this->parameters['id'])) {
			$this->jsonExit(DI::mstdnScheduledStatus()->createFromDelayedPostId($this->parameters['id'], $uid)->toArray());
		}

		$request = $this->getRequest([
			'limit'    => 20, // Max number of results to return. Defaults to 20.
			'max_id'   => 0,  // Return results older than ID
			'since_id' => 0,  // Return results newer than ID
			'min_id'   => 0,  // Return results immediately newer than ID
		], $request);

		$params = ['order' => ['id' => true], 'limit' => $request['limit']];

		$condition = ["`uid` = ? AND NOT `wid` IS NULL", $uid];

		if (!empty($request['max_id'])) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` < ?", $request['max_id']]);
		}

		if (!empty($request['since_id'])) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` > ?", $request['since_id']]);
		}

		if (!empty($request['min_id'])) {
			$condition       = DBA::mergeConditions($condition, ["`uri-id` > ?", $request['min_id']]);
			$params['order'] = ['uri-id'];
		}

		$posts = DBA::select('delayed-post', ['id'], $condition, $params);

		$statuses = [];
		while ($post = DBA::fetch($posts)) {
			self::setBoundaries($post['id']);
			$statuses[] = DI::mstdnScheduledStatus()->createFromDelayedPostId($post['id'], $uid);
		}
		DBA::close($posts);

		if (!empty($request['min_id'])) {
			$statuses = array_reverse($statuses);
		}

		self::setLinkHeader();
		$this->jsonExit($statuses);
	}
}
