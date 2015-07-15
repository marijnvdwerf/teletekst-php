<?php

namespace marijnvdwerf\teletekst;

use DOMDocument;
use DOMElement;
use DOMXPath;

class ImageFormatter
{
    private $blocks = [];
    private $font = 4;

    public function __construct()
    {
        $baseCharcode = 61472;
        for ($i = 0; $i <= 0b111111; $i++) {

            $offset = 0;
            if ($i >> 5 & 1 === 1) {
                // if last bit is set
                $offset += 0x20;
            }

            $charCode = '&#x' . dechex($baseCharcode + $i + $offset) . ';';
            $this->blocks[html_entity_decode($charCode)] = [
                $i >> 0 & 1,
                $i >> 1 & 1,
                $i >> 2 & 1,
                $i >> 3 & 1,
                $i >> 4 & 1,
                $i >> 5 & 1,
            ];
        }
    }

    /**
     * @param $page string HTML content of the page to format
     * @return resource GD Resource
     */
    public function formatPage($page)
    {
        $charWidth = imagefontwidth($this->font);
        $charHeight = imagefontheight($this->font);
        $screenWidth = imagefontwidth($this->font) * 41;
        $gd = imagecreate($screenWidth, imagefontheight($this->font) * 25);

        $colors = [
            'black' => imagecolorallocate($gd, 0, 0, 0),
            'red' => imagecolorallocate($gd, 255, 0, 0),
            'green' => imagecolorallocate($gd, 0, 255, 0),
            'yellow' => imagecolorallocate($gd, 255, 255, 0),
            'blue' => imagecolorallocate($gd, 0, 0, 255),
            'magenta' => imagecolorallocate($gd, 255, 0, 255),
            'cyan' => imagecolorallocate($gd, 0, 255, 255),
            'white' => imagecolorallocate($gd, 255, 255, 255)
        ];

        imagefill($gd, 0, 0, $colors['black']);


        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->preserveWhiteSpace = true;
        $doc->loadHTML('<pre id="content">' . $page . '</pre>');

        $pre = (new DOMXpath($doc))->query("//pre")[0];

        $x = 0;
        $y = 0;

        foreach ($pre->childNodes as $component) {

            $bg = null;
            $fg = $colors['white'];
            $doubleHeight = false;

            $text = $component->textContent;

            if ($component instanceof DOMElement) {
                $classes = explode(' ', $component->getAttribute('class'));
                $classes = array_map(function ($c) {
                    return trim($c);
                }, $classes);
                $classes = array_filter($classes);

                if (array_search('doubleHeight', $classes) !== false) {
                    $doubleHeight = true;
                }

                foreach ($colors as $name => $color) {
                    if (array_search('bg-' . $name, $classes) !== false) {
                        $bg = $color;
                    }
                    if (array_search($name, $classes) !== false) {
                        $fg = $color;
                    }
                }
            }

            $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($chars as $char) {
                if ($char === "\n") {
                    $x = 0;
                    $y += $charHeight;
                    continue;
                }

                if ($bg !== null) {
                    imagefilledrectangle($gd, $x, $y, $x + $charWidth, $y + $charHeight, $bg);
                }

                if (isset($this->blocks[$char])) {
                    self::drawBlock($gd, $x, $y, $charWidth, $charHeight, $this->blocks[$char], $fg);
                } else {
                    if (strlen($char) !== mb_strlen($char)) {
                        // Convert multibyte character to single-byte representation
                        $char = mb_convert_encoding($char, 'ISO-8859-2');
                    }

                    imagestring($gd, $this->font, $x, $y, $char, $fg);
                }


                if ($doubleHeight) {
                    //Create temporary image
                    $temp = imagecreatetruecolor($charWidth, $charHeight * 2);
                    imagecopyresized($temp, $gd, 0, 0, $x, $y, $charWidth, $charHeight * 2, $charWidth, $charHeight);
                    imagecopy($gd, $temp, $x, $y, 0, 0, $charWidth, $charHeight * 2);
                }

                $x += $charWidth;
            }
        }

        return $gd;
    }

    private static function  drawBlock($gd, $x, $y, $charWidth, $charHeight, $block, $fg)
    {
        $xSteps = [0, round($charWidth / 2), $charWidth];
        $ySteps = [0, round($charHeight / 3), round($charHeight / 3 * 2), $charHeight];

        if ($block[0] === 1) {
            imagefilledrectangle($gd, $x, $y, $x + $xSteps[1], $y + $ySteps[1], $fg);
        }

        if ($block[1] === 1) {
            imagefilledrectangle($gd, $x + $xSteps[1], $y, $x + $xSteps[2], $y + $ySteps[1], $fg);
        }

        if ($block[2] === 1) {
            imagefilledrectangle($gd, $x, $y + $ySteps[1], $x + $xSteps[1], $y + $ySteps[2], $fg);
        }

        if ($block[3] === 1) {
            imagefilledrectangle($gd, $x + $xSteps[1], $y + $ySteps[1], $x + $xSteps[2], $y + $ySteps[2], $fg);
        }

        if ($block[4] === 1) {
            imagefilledrectangle($gd, $x, $y + $ySteps[2], $x + $xSteps[1], $y + $ySteps[3], $fg);
        }

        if ($block[5] === 1) {
            imagefilledrectangle($gd, $x + $xSteps[1], $y + $ySteps[2], $x + $xSteps[2], $y + $ySteps[3], $fg);
        }
    }
}
