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
}