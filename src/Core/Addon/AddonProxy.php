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
	 * Installs an addon.
	 *
	 * @param string $addonId name of the addon
	 *
	 * @return bool true on success or false on failure
	 */
	public function installAddon(string $addonId): bool
	{
		return Addon::install($addonId);
	}

	/**
	 * Uninstalls an addon.
	 *
	 * @param string $addonId name of the addon
	 */
	public function uninstallAddon(string $addonId): void
	{
		Addon::uninstall($addonId);
	}

	/**
	 * Load addons.
	 *
	 * @internal
	 */
	public function loadAddons(): void
	{
		Addon::loadAddons();
	}

	/**
	 * Reload (uninstall and install) all updated addons.
	 */
	public function reloadAddons(): void
	{
		Addon::reload();
	}

	/**
	 * Get the comment block of an addon as value object.
	 */
	public function getAddonInfo(string $addonId): AddonInfo
	{
		$data = Addon::getInfo($addonId);

		// add addon ID
		$data['id'] = $addonId;

		return AddonInfo::fromArray($data);
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
	public function getVisibleEnabledAddons(): array
	{
		return Addon::getVisibleList();
	}

	/**
	 * Returns a list with the IDs of the enabled addons that provides admin settings.
	 *
	 * @return string[]
	 */
	public function getEnabledAddonsWithAdminSettings(): array
	{
		return array_keys(Addon::getAdminList());
	}
}
