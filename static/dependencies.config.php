<?php

/* Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * The configuration defines "complex" dependencies inside Friendica
 * So this classes shouldn't be simple or their dependencies are already defined here.
 *
 * This kind of dependencies are NOT required to be defined here:
 *   - $a = new ClassA(new ClassB());
 *   - $a = new ClassA();
 *   - $a = new ClassA(Configuration $configuration);
 *
 * This kind of dependencies SHOULD be defined here:
 *   - $a = new ClassA();
 *     $b = $a->create();
 *
 *   - $a = new ClassA($creationPassedVariable);
 *
 */

use Dice\Dice;
use Friendica\Core\Hooks\Model\DiceInstanceManager;
use Friendica\Core\L10n;
use Friendica\Core\Lock;
use Friendica\Core\Session\Capability\IHandleSessions;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\Storage\Repository\StorageManager;
use Friendica\Database\Database;
use Friendica\Database\Definition\DbaDefinition;
use Friendica\Database\Definition\ViewDefinition;
use Friendica\Core\Storage\Capability\ICanWriteToStorage;
use Friendica\Model\User\Cookie;
use Friendica\Model\Log\ParsedLogIterator;
use Friendica\Network;
use Friendica\Util;

return (function(array $serverVars): array {
	/**
	 * @var string $basepath The base path of the Friendica installation without trailing slash
	 */
	$basepath = dirname(__FILE__, 2);

	return [
	'*'                             => [
		// marks all class result as shared for other creations, so there's just
		// one instance for the whole execution
		'shared' => true,
	],
	\Friendica\Core\Addon\Capability\ICanLoadAddons::class => [
		'instanceOf' => \Friendica\Core\Addon\Model\AddonLoader::class,
		'constructParams' => [
			$basepath,
			[Dice::INSTANCE => Dice::SELF],
		],
	],
	Util\BasePath::class         => [
		'constructParams' => [
			$basepath,
			$serverVars,
		]
	],
	DiceInstanceManager::class   => [
		'constructParams' => [
			[Dice::INSTANCE => Dice::SELF],
		]
	],
	\Friendica\Core\Hooks\Util\StrategiesFileManager::class => [
		'constructParams' => [
			$basepath,
		],
		'call' => [
			['loadConfig'],
		],
	],
	\Friendica\Core\Hooks\Capability\ICanRegisterStrategies::class => [
		'instanceOf' => DiceInstanceManager::class,
		'constructParams' => [
			[Dice::INSTANCE => Dice::SELF],
		],
	],
	\Friendica\AppHelper::class => [
		'instanceOf' => \Friendica\AppLegacy::class,
	],
	\Friendica\Core\Hooks\Capability\ICanCreateInstances::class   => [
		'instanceOf' => DiceInstanceManager::class,
		'constructParams' => [
			[Dice::INSTANCE => Dice::SELF],
		],
	],
	\Friendica\Core\Config\Util\ConfigFileManager::class => [
		'instanceOf' => \Friendica\Core\Config\Factory\Config::class,
		'call'       => [
			['createConfigFileManager', [
				$basepath,
				$serverVars,
			], Dice::CHAIN_CALL],
		],
	],
	\Friendica\Core\Config\ValueObject\Cache::class => [
		'instanceOf' => \Friendica\Core\Config\Factory\Config::class,
		'call'       => [
			['createCache', [], Dice::CHAIN_CALL],
		],
	],
	\Friendica\App\Mode::class              => [
		'call' => [
			['determineRunMode', [true, $serverVars], Dice::CHAIN_CALL],
			['determine', [
				$basepath,
			], Dice::CHAIN_CALL],
		],
	],
	\Friendica\Core\Config\Capability\IManageConfigValues::class => [
		'instanceOf' => \Friendica\Core\Config\Model\DatabaseConfig::class,
		'constructParams' => [
			$serverVars,
		],
	],
	\Friendica\Core\PConfig\Capability\IManagePersonalConfigValues::class => [
		'instanceOf' => \Friendica\Core\PConfig\Factory\PConfig::class,
		'call'       => [
			['create', [], Dice::CHAIN_CALL],
		]
	],
	DbaDefinition::class => [
		'constructParams' => [
			$basepath,
		],
		'call' => [
			['load', [false], Dice::CHAIN_CALL],
		],
	],
	ViewDefinition::class => [
		'constructParams' => [
			$basepath,
		],
		'call' => [
			['load', [false], Dice::CHAIN_CALL],
		],
	],
	Database::class                         => [
		'constructParams' => [
			[Dice::INSTANCE => \Friendica\Core\Config\Model\ReadOnlyFileConfig::class],
		],
	],
	/**
	 * Creates the \Friendica\App\BaseURL
	 *
	 * Same as:
	 *   $baseURL = new \Friendica\App\BaseURL($configuration, $_SERVER);
	 */
	\Friendica\App\BaseURL::class             => [
		'constructParams' => [
			$serverVars,
		],
	],
	'$hostname'                    => [
		'instanceOf' => \Friendica\App\BaseURL::class,
		'constructParams' => [
			$serverVars,
		],
		'call' => [
			['getHost', [], Dice::CHAIN_CALL],
		],
	],
	\Friendica\Core\Cache\Type\AbstractCache::class => [
		'constructParams' => [
			[Dice::INSTANCE => '$hostname'],
		],
	],
	\Friendica\App\Page::class => [
		'constructParams' => [
			$basepath,
		],
	],
	\Psr\Log\LoggerInterface::class                                    => [
		'instanceOf' => \Friendica\Core\Logger\Factory\Logger::class,
		'call'       => [
			['create', [], Dice::CHAIN_CALL],
		],
	],
	\Friendica\Core\Logger\Type\SyslogLogger::class                    => [
		'instanceOf' => \Friendica\Core\Logger\Factory\SyslogLogger::class,
		'call'       => [
			['create', [], Dice::CHAIN_CALL],
		],
	],
	\Friendica\Core\Logger\Type\StreamLogger::class                    => [
		'instanceOf' => \Friendica\Core\Logger\Factory\StreamLogger::class,
		'call'       => [
			['create', [], Dice::CHAIN_CALL],
		],
	],
	\Friendica\Core\Logger\Capability\IHaveCallIntrospections::class => [
		'instanceOf'      => \Friendica\Core\Logger\Util\Introspection::class,
		'constructParams' => [
			\Friendica\Core\Logger\Capability\IHaveCallIntrospections::IGNORE_CLASS_LIST,
		],
	],
	'$devLogger'                                                       => [
		'instanceOf' => \Friendica\Core\Logger\Factory\StreamLogger::class,
		'call'       => [
			['createDev', [], Dice::CHAIN_CALL],
		],
	],
	\Friendica\Core\Cache\Capability\ICanCache::class => [
		'instanceOf' => \Friendica\Core\Cache\Factory\Cache::class,
		'call'       => [
			['createLocal', [], Dice::CHAIN_CALL],
		],
	],
	\Friendica\Core\Cache\Capability\ICanCacheInMemory::class => [
		'instanceOf' => \Friendica\Core\Cache\Factory\Cache::class,
		'call'       => [
			['createLocal', [], Dice::CHAIN_CALL],
		],
	],
	Lock\Capability\ICanLock::class => [
		'instanceOf' => Lock\Factory\Lock::class,
		'call'       => [
			['create', [], Dice::CHAIN_CALL],
		],
	],
	\Friendica\App\Arguments::class => [
		'instanceOf' => \Friendica\App\Arguments::class,
		'call' => [
			['determine', [$serverVars, $_GET], Dice::CHAIN_CALL],
		],
	],
	\Friendica\Core\System::class => [
		'constructParams' => [
			$basepath,
		],
	],
	\Friendica\App\Router::class => [
		'constructParams' => [
			$serverVars,
			__DIR__ . '/routes.config.php',
			[Dice::INSTANCE => Dice::SELF],
			null
		],
	],
	L10n::class => [
		'constructParams' => [
			$serverVars, $_GET
		],
	],
	IHandleSessions::class => [
		'instanceOf' => \Friendica\Core\Session\Factory\Session::class,
		'call' => [
			['create', [$serverVars], Dice::CHAIN_CALL],
			['start', [], Dice::CHAIN_CALL],
		],
	],
	IHandleUserSessions::class => [
		'instanceOf' => \Friendica\Core\Session\Model\UserSession::class,
	],
	Cookie::class => [
		'constructParams' => [
			$_COOKIE
		],
	],
	ICanWriteToStorage::class => [
		'instanceOf' => StorageManager::class,
		'call' => [
			['getBackend', [], Dice::CHAIN_CALL],
		],
	],
	\Friendica\Core\KeyValueStorage\Capability\IManageKeyValuePairs::class => [
		'instanceOf' => \Friendica\Core\KeyValueStorage\Factory\KeyValueStorage::class,
		'call' => [
			['create', [], Dice::CHAIN_CALL],
		],
	],
	Network\HTTPClient\Capability\ICanSendHttpRequests::class => [
		'instanceOf' => Network\HTTPClient\Factory\HttpClient::class,
		'call'       => [
			['createClient', [], Dice::CHAIN_CALL],
		],
	],
	ParsedLogIterator::class => [
		'constructParams' => [
			[Dice::INSTANCE => Util\ReversedFileReader::class],
		]
	],
	\Friendica\Core\Worker\Repository\Process::class => [
		'constructParams' => [
			$serverVars
		],
	],
	\Friendica\App\Request::class => [
		'constructParams' => [
			$serverVars
		],
	],
	\Psr\Clock\ClockInterface::class => [
		'instanceOf' => Util\Clock\SystemClock::class
	],
	\Friendica\Module\Special\HTTPException::class => [
		'constructParams' => [
			$serverVars
		],
	],
	\Friendica\Module\Api\ApiResponse::class => [
		'constructParams' => [
			$serverVars,
			$_GET['callback'] ?? '',
		],
	],
	];
})($_SERVER);
