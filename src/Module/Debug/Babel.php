<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Debug;

use Friendica\BaseModule;
use Friendica\Content\PageInfo;
use Friendica\Content\Text;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Protocol\Activity;
use Friendica\Util\XML;

/**
 * Translates input text into different formats (HTML, BBCode, Markdown)
 */
class Babel extends BaseModule
{
	protected function post(array $request = [])
	{
		// @todo check if POST is really used here
		$this->content($request);
	}

	protected function content(array $request = []): string
	{
		$results = [];

		$visible_whitespace = function (string $s): string {
			return '<pre>' . htmlspecialchars($s) . '</pre>';
		};

		if (!empty($request['text'])) {
			self::checkFormSecurityTokenForbiddenOnError('babel');
			switch (($request['type'] ?? '') ?: 'bbcode') {
				case 'bbcode':
					$bbcode = $request['text'];
					$results[] = [
						'title'   => DI::l10n()->t('Source input'),
						'content' => $visible_whitespace($bbcode)
					];

					$plain = Text\BBCode::toPlaintext($bbcode, false);
					$results[] = [
						'title'   => DI::l10n()->t('BBCode::toPlaintext'),
						'content' => $visible_whitespace($plain)
					];

					$html = Text\BBCode::convertForUriId(0, $bbcode);
					$results[] = [
						'title'   => DI::l10n()->t('BBCode::convert (raw HTML)'),
						'content' => $visible_whitespace($html)
					];

					$results[] = [
						'title'   => DI::l10n()->t('BBCode::convert (hex)'),
						'content' => $visible_whitespace(bin2hex($html)),
					];

					$results[] = [
						'title'   => DI::l10n()->t('BBCode::convert'),
						'content' => $html
					];

					$bbcode2 = Text\HTML::toBBCode($html);
					$results[] = [
						'title'   => DI::l10n()->t('BBCode::convert => HTML::toBBCode'),
						'content' => $visible_whitespace($bbcode2)
					];

					$markdown = Text\BBCode::toMarkdown($bbcode);
					$results[] = [
						'title'   => DI::l10n()->t('BBCode::toMarkdown'),
						'content' => $visible_whitespace($markdown)
					];

					$html2 = Text\Markdown::convert($markdown);
					$results[] = [
						'title'   => DI::l10n()->t('BBCode::toMarkdown => Markdown::convert (raw HTML)'),
						'content' => $visible_whitespace($html2)
					];
					$results[] = [
						'title'   => DI::l10n()->t('BBCode::toMarkdown => Markdown::convert'),
						'content' => $html2
					];

					$bbcode3 = Text\Markdown::toBBCode($markdown);
					$results[] = [
						'title'   => DI::l10n()->t('BBCode::toMarkdown => Markdown::toBBCode'),
						'content' => $visible_whitespace($bbcode3)
					];

					$bbcode4 = Text\HTML::toBBCode($html2);
					$results[] = [
						'title'   => DI::l10n()->t('BBCode::toMarkdown =>  Markdown::convert => HTML::toBBCode'),
						'content' => $visible_whitespace($bbcode4)
					];

					$tags = Text\BBCode::getTags($bbcode);

					$body = Item::setHashtags($bbcode);
					$results[] = [
						'title'   => DI::l10n()->t('Item Body'),
						'content' => $visible_whitespace($body)
					];
					$results[] = [
						'title'   => DI::l10n()->t('Item Tags'),
						'content' => $visible_whitespace(var_export($tags, true)),
					];

					$body2 = PageInfo::searchAndAppendToBody($bbcode, true);
					$results[] = [
						'title'   => DI::l10n()->t('PageInfo::appendToBody'),
						'content' => $visible_whitespace($body2)
					];
					$html3 = Text\BBCode::convertForUriId(0, $body2);
					$results[] = [
						'title'   => DI::l10n()->t('PageInfo::appendToBody => BBCode::convert (raw HTML)'),
						'content' => $visible_whitespace($html3)
					];
					$results[] = [
						'title'   => DI::l10n()->t('PageInfo::appendToBody => BBCode::convert'),
						'content' => $html3
					];
					break;
				case 'diaspora':
					$diaspora = trim($request['text']);
					$results[] = [
						'title'   => DI::l10n()->t('Source input (Diaspora format)'),
						'content' => $visible_whitespace($diaspora),
					];

					$markdown = XML::unescape($diaspora);
				case 'markdown':
					$markdown = $markdown ?? trim($request['text']);

					$results[] = [
						'title'   => DI::l10n()->t('Source input (Markdown)'),
						'content' => $visible_whitespace($markdown),
					];

					$html = Text\Markdown::convert($markdown);
					$results[] = [
						'title'   => DI::l10n()->t('Markdown::convert (raw HTML)'),
						'content' => $visible_whitespace($html),
					];

					$results[] = [
						'title'   => DI::l10n()->t('Markdown::convert'),
						'content' => $html
					];

					$bbcode = Text\Markdown::toBBCode($markdown);
					$results[] = [
						'title'   => DI::l10n()->t('Markdown::toBBCode'),
						'content' => $visible_whitespace($bbcode),
					];
					break;
				case 'html' :
					$html = trim($request['text']);
					$results[] = [
						'title'   => DI::l10n()->t('Raw HTML input'),
						'content' => $visible_whitespace($html),
					];

					$results[] = [
						'title'   => DI::l10n()->t('HTML Input'),
						'content' => $html
					];

					$purified = Text\HTML::purify($html);

					$results[] = [
						'title'   => DI::l10n()->t('HTML Purified (raw)'),
						'content' => $visible_whitespace($purified),
					];

					$results[] = [
						'title'   => DI::l10n()->t('HTML Purified (hex)'),
						'content' => $visible_whitespace(bin2hex($purified)),
					];

					$results[] = [
						'title'   => DI::l10n()->t('HTML Purified'),
						'content' => $purified,
					];

					$bbcode = Text\HTML::toBBCode($html);
					$results[] = [
						'title'   => DI::l10n()->t('HTML::toBBCode'),
						'content' => $visible_whitespace($bbcode)
					];

					$html2 = Text\BBCode::convertForUriId(0, $bbcode);
					$results[] = [
						'title'   => DI::l10n()->t('HTML::toBBCode => BBCode::convert'),
						'content' => $html2
					];

					$results[] = [
						'title'   => DI::l10n()->t('HTML::toBBCode => BBCode::convert (raw HTML)'),
						'content' => htmlspecialchars($html2)
					];

					$bbcode2plain = Text\BBCode::toPlaintext($bbcode);
					$results[] = [
						'title'   => DI::l10n()->t('HTML::toBBCode => BBCode::toPlaintext'),
						'content' => $visible_whitespace($bbcode2plain),
					];

					$markdown = Text\HTML::toMarkdown($html);
					$results[] = [
						'title'   => DI::l10n()->t('HTML::toMarkdown'),
						'content' => $visible_whitespace($markdown)
					];

					$text = Text\HTML::toPlaintext($html, 0);
					$results[] = [
						'title'   => DI::l10n()->t('HTML::toPlaintext'),
						'content' => $visible_whitespace($text),
					];

					$text = Text\HTML::toPlaintext($html, 0, true);
					$results[] = [
						'title'   => DI::l10n()->t('HTML::toPlaintext (compact)'),
						'content' => $visible_whitespace($text),
					];
					break;
				case 'twitter':
					$json = trim($request['text']);

					$status = json_decode($json);

					$results[] = [
						'title'   => DI::l10n()->t('Decoded post'),
						'content' => $visible_whitespace(var_export($status, true)),
					];

					$postarray = [];
					$postarray['object-type'] = Activity\ObjectType::NOTE;

					if (!empty($status->full_text)) {
						$postarray['body'] = $status->full_text;
					} else {
						$postarray['body'] = $status->text;
					}

					// When the post contains links then use the correct object type
					if (count($status->entities->urls) > 0) {
						$postarray['object-type'] = Activity\ObjectType::BOOKMARK;
					}

					$results[] = [
						'title'   => DI::l10n()->t('Post array before expand entities'),
						'content' => $visible_whitespace(var_export($postarray, true)),
					];

					break;
			}
		}

		$tpl = Renderer::getMarkupTemplate('babel.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$title'         => DI::l10n()->t('Babel Diagnostic'),
			'$form_security_token' => self::getFormSecurityToken('babel'),
			'$text'          => ['text', DI::l10n()->t('Source text'), $request['text'] ?? '', ''],
			'$type_bbcode'   => ['type', DI::l10n()->t('BBCode'), 'bbcode', '', (($request['type'] ?? '') ?: 'bbcode') == 'bbcode'],
			'$type_diaspora' => ['type', DI::l10n()->t('Diaspora'), 'diaspora', '', (($request['type'] ?? '') ?: 'bbcode') == 'diaspora'],
			'$type_markdown' => ['type', DI::l10n()->t('Markdown'), 'markdown', '', (($request['type'] ?? '') ?: 'bbcode') == 'markdown'],
			'$type_html'     => ['type', DI::l10n()->t('HTML'), 'html', '', (($request['type'] ?? '') ?: 'bbcode') == 'html'],
			'$flag_twitter'  => file_exists('addon/twitter/twitter.php'),
			'$type_twitter'  => ['type', DI::l10n()->t('Twitter Source / Tweet URL (requires API key)'), 'twitter', '', (($request['type'] ?? '') ?: 'bbcode') == 'twitter'],
			'$results'       => $results,
			'$submit'        => DI::l10n()->t('Submit'),
		]);

		return $o;
	}
}
