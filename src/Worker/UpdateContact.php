<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Worker;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException\InternalServerErrorException;

class UpdateContact
{
	/**
	 * Update contact data via probe
	 *
	 * @param int $contact_id Contact ID
	 * @return void
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function execute(int $contact_id)
	{
		// Silently dropping the task if the contact is blocked
		if (Contact::isBlocked($contact_id)) {
			return;
		}

		$success = Contact::updateFromProbe($contact_id);

		DI::logger()->info('Updated from probe', ['id' => $contact_id, 'success' => $success]);
	}

	/**
	 * @param array|int $run_parameters Priority constant or array of options described in Worker::add
	 * @param int       $contact_id
	 * @return int
	 * @throws InternalServerErrorException
	 */
	public static function add($run_parameters, int $contact_id): int
	{
		if (!$contact_id) {
			throw new \InvalidArgumentException('Invalid value provided for contact_id');
		}

		// Dropping the task if the contact is blocked
		if (Contact::isBlocked($contact_id)) {
			return 0;
		}

		DI::logger()->debug('Update contact', ['id' => $contact_id]);
		return Worker::add($run_parameters, 'UpdateContact', $contact_id);
	}
}
