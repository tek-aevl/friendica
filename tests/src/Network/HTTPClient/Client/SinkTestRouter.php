<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// Router for the built-in PHP web server used by HTTPClientTest

switch (parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH)) {
	case '/redirect':
		header('Location: /final', true, 302);
		echo 'redirect body that is longer than the final body';
		break;

	case '/slow':
		// 20 MB in 10 seconds
		header('Content-Length: 20000000');
		for ($i = 0; $i < 20; $i++) {
			echo str_repeat('x', 1000000);
			flush();
			usleep(500000);
		}
		break;

	default:
		echo 'final';
}
