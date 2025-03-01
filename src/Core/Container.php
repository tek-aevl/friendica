<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core;

/**
 * Dependency Injection Container
 *
 * @internal
 */
interface Container
{
	/**
	 * Returns a fully constructed object based on $name using $args and $share as constructor arguments if supplied
	 * @param string $name  name The name of the class to instantiate
	 * @param array  $args  An array with any additional arguments to be passed into the constructor upon instantiation
	 * @param array  $share a list of defined in shareInstances for objects higher up the object graph, should only be used internally
	 * @return object A fully constructed object based on the specified input arguments
	 *
	 * @see Dice::create()
	 */
	public function create(string $name, array $args = [], array $share = []): object;

	/**
	 * Add a rule $rule to the class $name
	 * @param string $name The name of the class to add the rule for
	 * @param array  $rule The container can be fully configured using rules provided by associative arrays. See {@link https://r.je/dice.html#example3} for a description of the rules.
	 *
	 * @see Dice::addRule()
	 */
	public function addRule(string $name, array $rule): void;
}
