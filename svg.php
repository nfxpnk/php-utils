<?php
$svgIcons = glob('icons/*.svg');

$svgFontTemplate = file_get_contents('svg.template');
$svgFontTemplate = explode('<!-- split -->', $svgFontTemplate);

foreach($svgFontTemplate as $key => $value) {
	$svgFontTemplate[$key] = trim($value);
}

$svgGlyphArray = array();
$cssArray = array();

$svgFontTemplate[0] = str_replace('{$ascent}', 1024 - 4, $svgFontTemplate[0]);
$svgFontTemplate[0] = str_replace('{$descent}', -4, $svgFontTemplate[0]);

foreach($svgIcons as $svgIcon) {
	if(filesize($svgIcon) == 0) {
		continue;
	}

	$svgIconMin = str_replace('icons/', 'icons/min/', $svgIcon);
	$svgIconMin = str_replace('.svg', '.min.svg', $svgIconMin);
	
	system('H:/github/svg-files-to-svg-font/node_modules/.bin/svgo.cmd ' . $svgIcon . ' ' . $svgIconMin);

	$svgData = str_replace('.svg', '', $svgIcon);
	$svgData = preg_split("#[-\.\/]#", $svgData, 4);

	$svgContent = file_get_contents($svgIcon);
	preg_match("#\sd=\"(.+?)\"#umsi", $svgContent, $path);

	if(!isset($path[1])) {
		var_dump($svgContent, $path);
	}

	$path3 = parse_path($path[1]);
	$path3 = transform_path($path3);
	$path3 = path_string($path3);
	$svgGlyph = str_replace('{$path}', $path3, $svgFontTemplate[1]);
	$svgGlyph = str_replace('{$unicode}', '&#x' . $svgData[2] . ';', $svgGlyph);
	$svgGlyph = str_replace('{$glyphName}', $svgData[3], $svgGlyph);
	$svgGlyphArray[$svgData[2]] = $svgData;
	$svgGlyphArray[$svgData[2]]['svgGlyph'] = $svgGlyph;
	
	$svgMinContent = file_get_contents($svgIconMin);
	preg_match("#\sd=\"(.+?)\"#umsi", $svgMinContent, $path);
	//var_dump($svgIconMin, $svgMinContent);
	//exit;
	
	$svgGlyphArray[$svgData[2]]['path'] = $path[1];
	$cssArray[$svgData[2]] = $svgData[3];
}

$svgFontFile = 'NetSuite Custom Icons.svg';

$svgFontFileContent = $svgFontTemplate[0] . "\r\n";
foreach ($svgGlyphArray as $svgData) {
	$svgFontFileContent .= "\t" . $svgData['svgGlyph'] . "\r\n";
}
$svgFontFileContent .= $svgFontTemplate[2] . "\r\n";
file_put_contents($svgFontFile, $svgFontFileContent);

foreach($cssArray as $unicode => $glyphName) {
	echo '$fa-var-'.$glyphName.'-custom-icon: "\\' . $unicode . '";
%#{$fa-css-prefix}-'.$glyphName.'-custom-icon:before {
	content: $fa-var-'.$glyphName.'-custom-icon;
}' . "\r\n\r\n";

}

foreach($cssArray as $unicode => $glyphName) {
	echo '// .icon-'.$glyphName.'-custom - '.$glyphName.' icon' . "\r\n";
}

foreach($cssArray as $unicode => $glyphName) {
	echo '.icon-'.$glyphName.'-custom,' . "\r\n";
}

foreach($cssArray as $unicode => $glyphName) {
	echo '.icon-'.$glyphName.'-custom {
	@extend %fa-'.$glyphName.'-custom-icon;
}' . "\r\n\r\n";
}

foreach($cssArray as $unicode => $glyphName) {
	$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" height="1024" width="1024" version="1"><path d="' . $svgGlyphArray[$unicode]['path'] . '"/></svg>';

	$svg = str_replace('d="', 'fill="fillPlaceholder" d="', $svg);
	$svg = rawurlencode($svg);
	$svg = str_replace('%20', ' ', $svg);
	$svg = str_replace('fillPlaceholder', '#{$fill-color}', $svg);

	echo '$svg-path-' . $glyphName . ": '" .$svgGlyphArray[$unicode]['path']. "';\r\n";
	
	/*echo '.icon-'.$glyphName.'-custom-svg {
	background-image: url(create-svg($svg-path-'.$glyphName.', \'#000\'));
}' . "\r\n\r\n";*/
}


foreach($cssArray as $unicode => $glyphName) {
	//echo '<div class="svg-' . $glyphName . '"></div>' . "\r\n";
}

/**
 * Parse the d attribute of an svg path element.
 **/
function parse_path($d) {
	$d = trim($d);
	$l = strlen($d);
	$path = array();

	for ($i = 0; $i < $l; ) {
		while ($i < $l && (ctype_space($d[$i]) || $d[$i]===',')) ++$i;

		if (ctype_alpha($d[$i])) {
			$type = $d[$i++];
		} else if ( ! empty($path) && strtolower($type) === 'm') {
			$type = ($type == 'm') ? 'l' : 'L';
		}
		$args = array( $type );

		$count = 0;
		$arg_counts = array( 'A'=>7, 'C'=>6, 'H'=>1, 'L'=>2, 'M'=>2, 'Q'=>4, 'S'=>4, 'T'=>2, 'V'=>1, 'Z'=>0, 'E' => 10 );
		$total = $arg_counts[strtoupper($type)];
		while ($count < $total) {
			while ($i < $l && (ctype_space($d[$i]) || $d[$i] === ',')) ++$i;
			$start = $i;
			if ($d[$i] === '-') ++$i;
			while ($i < $l && (ctype_digit($d[$i]) || $d[$i] === '.')) ++$i;
			$args[] = (float)substr($d, $start, $i - $start);
			++$count;
		}
		$path[] = $args;
	}
	return $path;
}


/**
 * Mirror and adjust to baseline
 **/
function transform_path($path, $height=1001, $descent= -4) {
	$top = ($height + $descent);
	foreach ($path as $i => $args) {
		switch ($args[0]) {
			case 'A':
				$args[3] = ($args[1] == $args[2]) ? 0 : (540 - $args[3]) % 360;
				$args[5] = $args[5] ? 0 : 1;
				$args[7] = $top - $args[7];
				break;
			case 'a':
				$args[3] = ($args[1] == $args[2]) ? 0 : (540 - $args[3]) % 360;
				$args[5] = $args[5] ? 0 : 1;
				$args[7] = -$args[7];
				break;
			case 'C':
				$args[2] = $top - $args[2];
				$args[4] = $top - $args[4];
				$args[6] = $top - $args[6];
				break;
			case 'c':
				$args[2] = -$args[2];
				$args[4] = -$args[4];
				$args[6] = -$args[6];
				break;
			case 'H': case 'h': break;
			case 'L': $args[2] = $top - $args[2]; break;
			case 'l': $args[2] = -$args[2]; break;
			case 'M': $args[2] = $top - $args[2]; break;
			case 'm': $args[2] = -$args[2]; break;
			case 'S': case 'Q':
				$args[2] = $top - $args[2];
				$args[4] = $top - $args[4];
				break;
			case 's': case 'q':
				$args[2] = -$args[2];
				$args[4] = -$args[4];
				break;
			case 'T': $args[2] = $top - $args[2]; break;
			case 't': $args[2] = -$args[2]; break;
			case 'V': $args[1] = $top - $args[1]; break;
			case 'v': $args[1] = -$args[1]; break;
			case 'Z': case 'z': default: break;
		}
		$path[$i] = $args;
	}
	return $path;
}


/**
 * Scale, and turn it back into a string
 **/
function path_string($path, $scale=1) {
	$d = '';
	$type = '';
	foreach($path as $args) {
		$d .= ' ';
		if ($args[0] !== $type && ! (
			($args[0] === 'L' && $type === 'M') ||
			($args[0] === 'l' && $type === 'm'))) {
			$d .= $args[0];
		}
		$type = $args[0];
		$args = array_slice($args, 1);
		if (strtolower($type) == 'a') {
			$args[0] = intval($args[0] * $scale);
			$args[1] = intval($args[1] * $scale);
		//	$args[2] = ($args[0] == $args[1]) ? 0 : $args[2]; // done in transform
			$args[3] = (int)(bool)$args[4];
			$args[4] = (int)(bool)$args[5];
			$args[5] = intval($args[5] * $scale);
			$args[6] = intval($args[6] * $scale);
		} else {
			foreach($args as $i => $v) { $args[$i] = intval($v * $scale); }
		}
		$d .= implode(',', $args);
	}
	return trim($d);
}