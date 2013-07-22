<?php

/**
 * ApiGen 3.0dev - API documentation generator for PHP 5.3+
 *
 * Custom hackish markdown parser by Rafi (https://github.com/rafi)
 *
 * Copyright (c) 2010-2011 David Grudl (http://davidgrudl.com)
 * Copyright (c) 2011-2012 Jaroslav Hanslík (https://github.com/kukulich)
 * Copyright (c) 2011-2012 Ondřej Nešpor (https://github.com/Andrewsville)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace ApiGen;

use Michelf;

class MarkdownMarkup implements IMarkup
{
	private $markdown;
	private $highlighter;

	/**
	 * CTOR
	 *
	 * @param Config\Configuration   $allowedHtml
	 * @param ISourceCodeHighlighter $highlighter
	 */
	public function __construct(Config\Configuration $allowedHtml, ISourceCodeHighlighter $highlighter)
	{
		$this->markdown = new Michelf\MarkdownExtra();

		$this->highlighter = $highlighter;
	}

	/**
	 * Spew a line
	 * TODO Not really sure why this has to be different than block()
	 *
	 * @param $text
	 * @return string
	 */
	public function line($text)
	{
		return $this->markdown->transform($text);
	}

	/**
	 * Spew a block
	 *
	 * @param $text
	 * @return string
	 */
	public function block($text)
	{
		// Match all <code> or <pre> blocks
		// TODO Not sure why I left <pre> here, am not really using it..
		preg_match_all(
			'~<(code|pre)>(.+?)</\\1>~sm',
			$text,
			$matches,
			PREG_OFFSET_CAPTURE | PREG_SET_ORDER
		);

		$offset = 0;
		foreach ($matches as $match)
		{
			// Processes only <code> blocks
			$tag_name = isset($match[1][0]) ? $match[1][0] : NULL;
			if ($tag_name == 'code')
			{
				$position  = $match[2][1];
				$pre_code  = $match[2][0];
				$pre_len   = strlen($pre_code);

				// Wraps with <pre> the formatted code
				// TODO It looks better, but could be replaced with a CSS rule maybe
				$post_code = '<pre>'.$this->highlighter->highlight($pre_code).'</pre>';

				// Replace the new formatted code instead of the old
				$text =
					substr($text, 0, $offset + $position)
					.$post_code
					.substr($text, $offset + $position + $pre_len)
				;

				// The new piece of code we injected might be of a different
				// length, so all our positions need to be shifted by that difference
				$offset += strlen($post_code) - strlen($pre_code);
			}
		}

		return $this->markdown->transform($text);
	}

} // End MarkdownMarkup
