<?php


define('GD_FONT', 4);

function get($page) {
  $path = $page . '.json';

  if(!file_exists($path)) {
    $data = file_get_contents('http://teletekst-data.nos.nl/json/' . $page);
    file_put_contents($path, $data);
  } else {
    $data = file_get_contents($path);
  }

  return json_decode($data);
}

function drawBlock($gd, $x, $y, $charWidth, $charHeight, $block, $fg) {
  $opacity = array_sum($block);
  
  var_dump([$charWidth, $charHeight]);
  
  $xSteps = [0, round($charWidth/2), $charWidth];
  $ySteps = [0, round($charHeight/3), round($charHeight/3*2), $charHeight];
  
  var_dump($xSteps);
  var_dump($ySteps);
  die();
  
  if($block[0] === 1) {
    imagefilledrectangle($gd, $x, $y, $x + $xSteps[1], $y + $ySteps[1], $fg);
  }
  
  if($block[1] === 1) {
    imagefilledrectangle($gd, $x+ $xSteps[1], $y, $x + $xSteps[2], $y + $ySteps[1], $fg);
  }
  
  if($block[2] === 1) {
    imagefilledrectangle($gd, $x, $y+ $ySteps[1], $x + $xSteps[1], $y + $ySteps[2], $fg);
  }
  
  if($block[3] === 1) {
    imagefilledrectangle($gd, $x+ $xSteps[1], $y+ $ySteps[1], $x + $xSteps[2], $y + $ySteps[2], $fg);
  }
  
  if($block[4] === 1) {
    imagefilledrectangle($gd, $x, $y+ $ySteps[2], $x + $xSteps[1], $y + $ySteps[3], $fg);
  }
  
  if($block[5] === 1) {
    imagefilledrectangle($gd, $x+ $xSteps[1], $y+ $ySteps[2], $x + $xSteps[2], $y + $ySteps[3], $fg);
  }
}

$map = get('100');
$forecast = get(704); 


$charWidth = imagefontwidth(GD_FONT);
$charHeight = imagefontheight(GD_FONT);
$screenWidth = imagefontwidth(GD_FONT) * 41;
$gd = imagecreate($screenWidth, imagefontheight(GD_FONT) * 25);

$colors = [
  'black' => imagecolorallocate($gd , 0, 0, 0),
  'red' => imagecolorallocate($gd , 255, 0, 0),
  'green' => imagecolorallocate($gd , 0, 255, 0),
  'yellow' => imagecolorallocate($gd , 255, 255, 0),
  'blue' => imagecolorallocate($gd , 0, 0, 255),
  'magenta' => imagecolorallocate($gd , 255, 0, 255),
  'cyan' => imagecolorallocate($gd , 0, 255, 255),
  'white' => imagecolorallocate($gd , 255, 255, 255)
];

imagefill ($gd, 0, 0, $colors['black']);


$doc = new DOMDocument('1.0', 'utf-8');
$doc->preserveWhiteSpace = true;
$doc->loadHTML('<pre id="content">' .$map->content.'</pre>');

$xpath = new DOMXpath($doc);

// example 1: for everything with an id
$pre = $xpath->query("//pre")[0];

$x = 0;
$y = 0;

$symbols = [
  '&#xf020;','&#xf021;','&#xf022;','&#xf023;','&#xf025;','&#xf026;','&#xf027;','&#xf029;','&#xf02a;','&#xf02b;','&#xf02c;','&#xf02d;',
  '&#xf02f;','&#xf030;','&#xf031;','&#xf036;','&#xf037;','&#xf038;','&#xf03a;','&#xf03c;','&#xf03e;','&#xf03f','&#xf060;','&#xf063;',
  '&#xf065;','&#xf066;','&#xf067;','&#xf068;','&#xf06a;','&#xf06b;','&#xf06c;','&#xf06e;','&#xf06f;','&#xf070;','&#xf072;','&#xf074;',
  '&#xf076;','&#xf077;','&#xf078;','&#xf07a;','&#xf07c;','&#xf07d;','&#xf07e;','&#xf07f;','&#xf034;','&#xf035;','&#xf071;',
  '&#xf073;','&#xf075;'
];

$blocks = [];

$baseCharcode = 61472;
for($i = 0; $i <= 0b111111; $i++) {
  
  $offset = 0;
  if($i >> 5 & 1 === 1) {
    // if last bit is set
    $offset += 0x20;
  }
  
  $charCode = '&#x' . dechex($baseCharcode + $i + $offset) . ';';
  $blocks[html_entity_decode($charCode)] = [
    $i >> 0 & 1,
    $i >> 1 & 1,
    $i >> 2 & 1,
    $i >> 3 & 1,
    $i >> 4 & 1,
    $i >> 5 & 1,
  ];
}

$symbols2 = [];

foreach($symbols as &$s) {
  $s = html_entity_decode($s);
}

var_dump($symbols);

foreach($pre->childNodes as $component) {

  $bg = null;
  $fg = $colors['white'];
  
  $text = $component->textContent;
  
  if($component instanceof DOMElement) {      
    $classes = explode(' ', $component->getAttribute('class'));
    $classes = array_map(function($c) {
      return trim($c);
    }, $classes);
    $classes = array_filter($classes);
  
    foreach($colors as $name => $color) {
      if(array_search('bg-'.$name, $classes) !== false) {
        $bg = $color;
      }
      if(array_search($name, $classes) !== false) {
        $fg = $color;
      }
    }
  }
  
  $chars = preg_split('//u',$text, -1, PREG_SPLIT_NO_EMPTY);
  foreach($chars as $char) {
    if($char === "\n") {
      $x = 0;
      $y += $dy;
      continue;
    }
    
    $dx = $charWidth;
    $dy = $charHeight;

    if($bg !== null) {
      imagefilledrectangle($gd, $x, $y, $x + $dx, $y + $dy, $bg);
    }
    
    if(isset($blocks[$char])) {
      drawBlock($gd, $x, $y, $charWidth, $charHeight, $blocks[$char], $fg);
    } else {
      if(in_array($char, $symbols)) {
        $char = '#';
      } elseif(strlen($char) !== mb_strlen($char)) {
        $char = mb_convert_encoding($char, 'ISO-8859-2');
      } elseif(urlencode($char) != $char && $char != ' ') {
            $symbols2[] = $char;
      }
      imagestring ($gd, GD_FONT, $x, $y, $char, $fg);
    }
    
    $x += $dx;
    if($x >= $screenWidth) {
      $x = 0;
      $y += $dy;
    }
  }
}

//var_dump(mb_list_encodings());

$symbols2 = array_unique($symbols2);
sort($symbols2);
echo json_encode($symbols2);

imagegif($gd, '702.gif');


//var_dump($map);