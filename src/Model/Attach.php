<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model;

use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Core\Storage\Exception\InvalidClassStorageException;
use Friendica\Core\Storage\Exception\ReferenceStorageException;
use Friendica\Object\Image;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Mimetype;
use Friendica\Security\Security;
use Friendica\Util\Network;

/**
 * Class to handle attach database table
 */
class Attach
{

	/**
	 * Return a list of fields that are associated with the attach table
	 *
	 * @return array field list
	 * @throws \Exception
	 */
	private static function getFields(): array
	{
		$allfields = DI::dbaDefinition()->getAll();
		$fields = array_keys($allfields['attach']['fields']);
		array_splice($fields, array_search('data', $fields), 1);
		return $fields;
	}

	/**
	 * Select rows from the attach table and return them as array
	 *
	 * @param array $fields     Array of selected fields, empty for all
	 * @param array $conditions Array of fields for conditions
	 * @param array $params     Array of several parameters
	 *
	 * @return array|bool
	 *
	 * @throws \Exception
	 * @see   \Friendica\Database\DBA::selectToArray
	 */
	public static function selectToArray(array $fields = [], array $conditions = [], array $params = [])
	{
		if (empty($fields)) {
			$fields = self::getFields();
		}

		return DBA::selectToArray('attach', $fields, $conditions, $params);
	}

	/**
	 * Retrieve a single record from the attach table
	 *
	 * @param array $fields     Array of selected fields, empty for all
	 * @param array $conditions Array of fields for conditions
	 * @param array $params     Array of several parameters
	 *
	 * @return bool|array
	 *
	 * @throws \Exception
	 * @see   \Friendica\Database\DBA::select
	 */
	public static function selectFirst(array $fields = [], array $conditions = [], array $params = [])
	{
		if (empty($fields)) {
			$fields = self::getFields();
		}

		return DBA::selectFirst('attach', $fields, $conditions, $params);
	}

	/**
	 * Check if attachment with given conditions exists
	 *
	 * @param array $conditions Array of extra conditions
	 *
	 * @return boolean
	 * @throws \Exception
	 */
	public static function exists(array $conditions): bool
	{
		return DBA::exists('attach', $conditions);
	}

	/**
	 * Retrieve a single record given the ID
	 *
	 * @param int $id  Row id of the record
	 * @param int $uid User-Id
	 *
	 * @return bool|array
	 *
	 * @throws \Exception
	 * @see   \Friendica\Database\DBA::select
	 */
	public static function getById(int $id, int $uid)
	{
		return self::selectFirst([], ['id' => $id, 'uid' => $uid]);
	}

	/**
	 * Retrieve a single record given the ID
	 *
	 * @param int $id Row id of the record
	 *
	 * @return bool|array
	 *
	 * @throws \Exception
	 * @see   \Friendica\Database\DBA::select
	 */
	public static function getByIdWithPermission(int $id)
	{
		$r = self::selectFirst(['uid'], ['id' => $id]);
		if ($r === false) {
			return false;
		}

		$sql_acl = Security::getPermissionsSQLByUserId($r['uid']);

		$conditions = [
			'`id` = ?' . $sql_acl,
			$id
		];

		$item = self::selectFirst([], $conditions);

		return $item;
	}

	/**
	 * Get file data for given row id. null if row id does not exist
	 *
	 * @param array $item Attachment data. Needs at least 'id', 'backend-class', 'backend-ref'
	 *
	 * @return string|null file data or null on failure
	 * @throws \Exception
	 */
	public static function getData(array $item)
	{
		if (!empty($item['data'])) {
			return $item['data'];
		}

		try {
			$backendClass = DI::storageManager()->getByName($item['backend-class'] ?? '');
			$backendRef   = $item['backend-ref'];
			return $backendClass->get($backendRef);
		} catch (InvalidClassStorageException $storageException) {
			// legacy data storage in 'data' column
			$i = self::selectFirst(['data'], ['id' => $item['id']]);
			if ($i === false) {
				return null;
			}
			return $i['data'];
		} catch (ReferenceStorageException $referenceStorageException) {
			DI::logger()->debug('No data found for item', ['item' => $item, 'exception' => $referenceStorageException]);
			return '';
		}
	}

	/**
	 * Store new file metadata in db and binary in default backend
	 *
	 * @param string  $data      Binary data
	 * @param integer $uid       User ID
	 * @param string  $filename  Filename
	 * @param string  $filetype  Mimetype. optional, default = ''
	 * @param integer $filesize  File size in bytes. optional, default = null
	 * @param string  $allow_cid Permissions, allowed contacts. optional, default = ''
	 * @param string  $allow_gid Permissions, allowed circles. optional, default = ''
	 * @param string  $deny_cid  Permissions, denied contacts. optional, default = ''
	 * @param string  $deny_gid  Permissions, denied circle. optional, default = ''
	 *
	 * @return boolean|integer Row id on success, False on errors
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function store(string $data, int $uid, string $filename, string $filetype = '', int $filesize = null, string $allow_cid = '', string $allow_gid = '', string $deny_cid = '', string $deny_gid = '')
	{
		if ($filetype === '') {
			$filetype = Mimetype::getContentType($filename);
		}

		if (is_null($filesize)) {
			$filesize = strlen($data);
		}

		$backend_ref = DI::storage()->put($data);
		$data = '';

		$hash = System::createGUID(64);
		$created = DateTimeFormat::utcNow();

		$fields = [
			'uid' => $uid,
			'hash' => $hash,
			'filename' => $filename,
			'filetype' => $filetype,
			'filesize' => $filesize,
			'data' => $data,
			'created' => $created,
			'edited' => $created,
			'allow_cid' => $allow_cid,
			'allow_gid' => $allow_gid,
			'deny_cid' => $deny_cid,
			'deny_gid' => $deny_gid,
			'backend-class' => (string)DI::storage(),
			'backend-ref' => $backend_ref
		];

		$r = DBA::insert('attach', $fields);
		if ($r === true) {
			return DBA::lastInsertId();
		}
		return $r;
	}

	/**
	 * Store new file metadata in db and binary in default backend from existing file
	 *
	 * @param string $src Source file name
	 * @param int    $uid User id
	 * @param string $filename Optional file name
	 * @param string $filetype Optional file type
	 * @param string $allow_cid
	 * @param string $allow_gid
	 * @param string $deny_cid
	 * @param string $deny_gid
	 * @return boolean|int Insert id or false on failure
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function storeFile(string $src, int $uid, string $filename = '', string $filetype = '', string $allow_cid = '', string $allow_gid = '', string $deny_cid = '', string $deny_gid = '')
	{
		if ($filename === '') {
			$filename = basename($src);
		}

		$data = @file_get_contents($src);

		return self::store($data, $uid, $filename, $filetype, null, $allow_cid, $allow_gid,  $deny_cid, $deny_gid);
	}


	/**
	 * Update an attached file
	 *
	 * @param array         $fields     Contains the fields that are updated
	 * @param array         $conditions Condition array with the key values
	 * @param Image         $img        Image data to update. Optional, default null.
	 * @param array|boolean $old_fields Array with the old field values that are about to be replaced (true = update on duplicate)
	 *
	 * @return boolean  Was the update successful?
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @see   \Friendica\Database\DBA::update
	 */
	public static function update(array $fields, array $conditions, Image $img = null, array $old_fields = []): bool
	{
		if (!is_null($img)) {
			// get items to update
			$items = self::selectToArray(['backend-class', 'backend-ref'], $conditions);

			foreach ($items as $item) {
				try {
					$backend_class         = DI::storageManager()->getWritableStorageByName($item['backend-class'] ?? '');
					$fields['backend-ref'] = $backend_class->put($img->asString(), $item['backend-ref'] ?? '');
				} catch (InvalidClassStorageException $storageException) {
					DI::logger()->debug('Storage class not found.', ['conditions' => $conditions, 'exception' => $storageException]);
				} catch (ReferenceStorageException $referenceStorageException) {
					DI::logger()->debug('Item doesn\'t exist.', ['conditions' => $conditions, 'exception' => $referenceStorageException]);
				}
			}
		}

		$fields['edited'] = DateTimeFormat::utcNow();

		return DBA::update('attach', $fields, $conditions, $old_fields);
	}


	/**
	 * Delete info from table and data from storage
	 *
	 * @param array $conditions Field condition(s)
	 * @param array $options    Options array, Optional
	 *
	 * @return boolean
	 *
	 * @throws \Exception
	 * @see   \Friendica\Database\DBA::delete
	 */
	public static function delete(array $conditions): bool
	{
		// get items to delete data info
		$items = self::selectToArray(['backend-class', 'backend-ref'], $conditions);

		foreach ($items as $item) {
			try {
				$backend_class = DI::storageManager()->getWritableStorageByName($item['backend-class'] ?? '');
				$backend_class->delete($item['backend-ref'] ?? '');
			} catch (InvalidClassStorageException $storageException) {
				DI::logger()->debug('Storage class not found.', ['conditions' => $conditions, 'exception' => $storageException]);
			} catch (ReferenceStorageException $referenceStorageException) {
				DI::logger()->debug('Item doesn\'t exist.', ['conditions' => $conditions, 'exception' => $referenceStorageException]);
			}
		}

		return DBA::delete('attach', $conditions);
	}

	public static function setPermissionFromBody(array $post)
	{
		preg_match_all("/\[attachment\](.*?)\[\/attachment\]/ism", $post['body'], $matches, PREG_SET_ORDER);
		foreach ($matches as $attachment) {
			if (DI::baseUrl()->isLocalUrl($attachment[1]) && preg_match('|.*?/attach/(\d+)|', $attachment[1], $match)) {
				$fields = [
					'allow_cid' => $post['allow_cid'], 'allow_gid' => $post['allow_gid'],
					'deny_cid' => $post['deny_cid'], 'deny_gid' => $post['deny_gid']
				];
				self::update($fields, ['id' => $match[1], 'uid' => $post['uid']]);
			}
		}
	}

	public static function setPermissionForId(int $id, int $uid, string $str_contact_allow, string $str_circle_allow, string $str_contact_deny, string $str_circle_deny)
	{
		$fields = [
			'allow_cid' => $str_contact_allow, 'allow_gid' => $str_circle_allow,
			'deny_cid' => $str_contact_deny, 'deny_gid' => $str_circle_deny,
		];

		self::update($fields, ['id' => $id, 'uid' => $uid]);
	}

	public static function addAttachmentToBody(string $body, int $uid): string
	{
		preg_match_all("/\[attachment\](.*?)\[\/attachment\]/ism", $body, $matches, PREG_SET_ORDER);
		foreach ($matches as $attachment) {
			if (DI::baseUrl()->isLocalUrl($attachment[1]) && preg_match('|.*?/attach/(\d+)|', $attachment[1], $match)) {
				$attach = self::getById($match[1], $uid);
				if (empty($attach)) {
					return $body;
				}
				$media = [
					'type'        => Post\Media::DOCUMENT,
					'url'         => $attachment[1],
					'size'        => $attach['filesize'],
					'mimetype'    => $attach['filetype'],
					'description' => $attach['filename']
				];
				$media = Post\Media::addType($media);
				$body = str_replace($attachment[0], Post\Media::addAttachmentToBody($media, ''), $body);
			}
		}
		return $body;
	}
}
