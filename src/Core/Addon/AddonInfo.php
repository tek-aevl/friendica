<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core\Addon;

/**
 * Information about an addon
 */
final class AddonInfo
{
	/**
	 * @internal Never create this object by yourself, use `Friendica\Core\Addon\AddonHelper::getAddonInfo()` instead.
	 *
	 * @see Friendica\Core\Addon\AddonHelper::getAddonInfo()
	 */
	public static function fromArray(array $info): self
	{
		$id          = array_key_exists('id', $info) ? (string) $info['id'] : '';
		$name        = array_key_exists('name', $info) ? (string) $info['name'] : '';
		$description = array_key_exists('description', $info) ? (string) $info['description'] : '';
		$author      = array_key_exists('author', $info) ? self::parseContributor($info['author']) : [];
		$maintainer  = array_key_exists('maintainer', $info) ? self::parseContributor($info['maintainer']) : [];
		$version     = array_key_exists('version', $info) ? (string) $info['version'] : '';
		$status      = array_key_exists('status', $info) ? (string) $info['status'] : '';

		return new self(
			$id,
			$name,
			$description,
			$author,
			$maintainer,
			$version,
			$status
		);
	}

	private static function parseContributor($entry): array
	{
		if (!is_array($entry)) {
			return [];
		}

		if (!array_key_exists('name', $entry)) {
			return [];
		}

		$contributor = [
			'name' => (string) $entry['name'],
		];

		if (array_key_exists('link', $entry)) {
			$contributor['link'] = (string) $entry['link'];
		}

		return $contributor;
	}

	private string $id = '';

	private string $name = '';

	private string $description = '';

	private array $author = [];

	private array $maintainer = [];

	private string $version = '';

	private string $status = '';

	private function __construct(
		string $id,
		string $name,
		string $description,
		array $author,
		array $maintainer,
		string $version,
		string $status
	) {
		$this->id          = $id;
		$this->name        = $name;
		$this->description = $description;
		$this->author      = $author;
		$this->maintainer  = $maintainer;
		$this->version     = $version;
		$this->status      = $status;
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function getAuthor(): array
	{
		return $this->author;
	}

	public function getMaintainer(): array
	{
		return $this->maintainer;
	}

	public function getVersion(): string
	{
		return $this->version;
	}

	public function getStatus(): string
	{
		return $this->status;
	}
}
