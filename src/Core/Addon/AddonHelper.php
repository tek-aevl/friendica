<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core\Addon;

/**
 * Some functions to handle addons
 */
interface AddonHelper
{
	/**
	 * Checks if the provided addon is enabled
	 */
	public function isEnabled(string $addonId): bool;

	/**
	 * Returns a list with the IDs of the enabled addons
	 *
	 * @return string[]
	 */
	public function getEnabledList(): array;

	/**
	 * Returns the list of the IDs of the non-hidden enabled addons
	 *
	 * @return string[]
	 */
	public static function getVisibleList(): array;
}
