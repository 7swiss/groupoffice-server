<?php
namespace IFW\Template;

class BlockParser {

	public $tagStart = '<';
	public $tagEnd = '>';

	/**
	 * Parse the given text into an array of blocks.
	 * 
	 * @param string $text
	 * @return array eg.
	 * 
	 * array (size=4)
	 * 'outerText' => string '<tagName path="Tickets.png" lw="300" ph="300" zoom="true" lightbox="docs" caption="Screenshot of the tickets module"></tagName>' (length=133)
	 * 'tagName' => string 'tagName' (length=5)
	 * 'attributes' => 
	 *   array (size=6)
	 *     'path' => string 'Tickets.png' (length=11)
	 *     'lw' => string '300' (length=3)
	 *     'ph' => string '300' (length=3)
	 *     'zoom' => string 'true' (length=4)
	 *     'lightbox' => string 'docs' (length=4)
	 *     'caption' => string 'Screenshot of the tickets module' (length=32)
	 * 'innerText' => string '' (length=0)
	 */
	public function parse($text) {

		$closeTagStart = strlen($this->tagStart) > 1 ? substr($this->tagStart, 0, 1) . '/' . substr($this->tagStart, 1) : $this->tagStart . '/';

		$pattern = '/' . preg_quote($this->tagStart, '/') . 
						'([a-zA-Z0-9-^]+)([^' . preg_quote($this->tagEnd, '/') . ']*)' . preg_quote($this->tagEnd, '/') . 
						'((.*?)' . preg_quote($closeTagStart, '/') . '\1' . preg_quote($this->tagEnd, '/') . ')?/s';

		$matched_tags = [];
		preg_match_all($pattern, $text, $matched_tags, PREG_SET_ORDER);

		$blocks = [];
		for ($n = 0, $c = count($matched_tags); $n < $c; $n++) {
			// parse attributes
			$attributes_array = [];
			$attributes = [];
			preg_match_all('/\s*([^=]+)="([^"]*)"/', $matched_tags[$n][2], $attributes, PREG_SET_ORDER);
			for ($i = 0; $i < count($attributes); $i++) {
				$right = $attributes[$i][2];
				$left = $attributes[$i][1];
				$attributes_array[$left] = $right;
			}

			$block = array(
					'outerText' => $matched_tags[$n][0],
					'tagName' => $matched_tags[$n][1],
					'attributes' => $attributes_array,
					'innerText' => isset($matched_tags[$n][4]) ? $matched_tags[$n][4] : null
			);

			$blocks[] = $block;
		}
		return $blocks;
	}

}
