<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model;

use Friendica\Content\Item as ItemContent;

/**
 * A helper class for inserting an Item Model
 *
 * @see Item::insert()
 */
final class ItemInserter
{
	private ItemContent $itemContent;

	public function __construct(ItemContent $itemContent)
	{
		$this->itemContent = $itemContent;
	}

	public function prepareOriginPost(array $item): array
	{
		$item = $this->itemContent->initializePost($item);
		$item = $this->itemContent->finalizePost($item, false);

		return $item;
	}

	public function prepareItemData(array $item, bool $notify): array
	{
		$item['guid'] = Item::guid($item, $notify);
		$item['uri'] = substr(trim($item['uri'] ?? '') ?: Item::newURI($item['guid']), 0, 255);

		// Store URI data
		$item['uri-id'] = ItemURI::insert(['uri' => $item['uri'], 'guid' => $item['guid']]);

		// Backward compatibility: parent-uri used to be the direct parent uri.
		// If it is provided without a thr-parent, it probably is the old behavior.
		if (empty($item['thr-parent']) || empty($item['parent-uri'])) {
			$item['thr-parent'] = trim($item['thr-parent'] ?? $item['parent-uri'] ?? $item['uri']);
			$item['parent-uri'] = $item['thr-parent'];
		}

		$item['thr-parent-id'] = ItemURI::getIdByURI($item['thr-parent']);
		$item['parent-uri-id'] = ItemURI::getIdByURI($item['parent-uri']);

		return $item;
	}
}
