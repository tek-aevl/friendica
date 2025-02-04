<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core\Addon;

use Friendica\Core\Addon;

/**
 * Proxy to the Addon class
 *
 * @internal
 */
final class AddonProxy implements AddonHelper
{
	/**
	 * Returns the list of available addons with their current status and info.
	 * This list is made from scanning the addon/ folder.
	 * Unsupported addons are excluded unless they already are enabled or system.show_unsupported_addon is set.
	 *
	 * @return array<array<string|string|array>>
	 */
	public function getAvailableAddons(): array
	{
		return Addon::getAvailableList();
	}

	/**
	 * Checks if the provided addon is enabled
	 */
	public function isAddonEnabled(string $addonId): bool
	{
		return Addon::isEnabled($addonId);
	}

	/**
	 * Returns a list with the IDs of the enabled addons
	 *
	 * @return string[]
	 */
	public function getEnabledAddons(): array
	{
		return Addon::getEnabledList();
	}

	/**
	 * Returns a list with the IDs of the non-hidden enabled addons
	 *
	 * @return string[]
	 */
	public static function getVisibleEnabledAddons(): array
	{
		return Addon::getVisibleList();
	}
}
