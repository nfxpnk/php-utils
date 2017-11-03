<?php
// node svgo binary path
$svgoPath = 'H:/github/svg-files-to-svg-font/node_modules/.bin/svgo.cmd';

// Original SVG icons
$svgIcons = glob('icons/*.svg');

// Template for SVG font
$svgFontTemplate = file_get_contents('svg.template');

// Split template into parts
$svgFontTemplate = explode('<!-- split -->', $svgFontTemplate);
foreach($svgFontTemplate as $key => $value) {
	$svgFontTemplate[$key] = trim($value);
}

$svgGlyphArray = array();
$cssArray = array();

foreach($svgIcons as $svgIcon) {
	// Skip empty files
	if(filesize($svgIcon) == 0) {
		continue;
	}

	// Filename for minified svg icon
	$svgIconMin = str_replace('icons/', 'icons/min/', $svgIcon);
	$svgIconMin = str_replace('.svg', '.min.svg', $svgIconMin);

	// Minify svg icon with svgo tool
	// system($svgoPath . ' ' . $svgIcon . ' ' . $svgIconMin);

	// Get svg data from svg icon filename (num, unicode, glyph-name)
	$svgData = str_replace('.svg', '', $svgIcon);
	$svgData = preg_split("#[-\.\/]#", $svgData, 4);

	// Get d attribute from original svg file
	$svgContent = file_get_contents($svgIcon);
	preg_match("#\sd=\"(.+?)\"#umsi", $svgContent, $path);

	// Exit if there is no d attribute in path
	if(!isset($path[1])) {
		var_dump($svgIcon, $svgContent, $path, __LINE__);
		exit;
	}

	// 1 get path string and convert to array
	$parsedPath = parsePath($path[1]);

	// 2 is absolute or relative
	
	// 3 convert from absolute to relative
	// $parsedPath = convertToRelative($parsedPath);
	
	//var_dump($parsedPath);
	//var_dump($convertToRelative);
	//exit;
	
	// 4 transform path for font
	$transformedPath = transformPath($parsedPath);
	
	// 5 get path array and convert to string
	$path3 = pathString($parsedPath);

	var_dump($path3);

	
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


/* CSS Generation */


foreach($cssArray as $unicode => $glyphName) {
	/*echo '$fa-var-'.$glyphName.'-custom-icon: "\\' . $unicode . '";
%#{$fa-css-prefix}-'.$glyphName.'-custom-icon:before {
	content: $fa-var-'.$glyphName.'-custom-icon;
}' . "\r\n\r\n";*/

}

foreach($cssArray as $unicode => $glyphName) {
	//echo '// .icon-'.$glyphName.'-custom - '.$glyphName.' icon' . "\r\n";
}

foreach($cssArray as $unicode => $glyphName) {
	//echo '.icon-'.$glyphName.'-custom,' . "\r\n";
}

foreach($cssArray as $unicode => $glyphName) {
	/*echo '.icon-'.$glyphName.'-custom {
	@extend %fa-'.$glyphName.'-custom-icon;
}' . "\r\n\r\n";*/
}

foreach($cssArray as $unicode => $glyphName) {
	$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" height="1024" width="1024" version="1"><path d="' . $svgGlyphArray[$unicode]['path'] . '"/></svg>';

	$svg = str_replace('d="', 'fill="fillPlaceholder" d="', $svg);
	$svg = rawurlencode($svg);
	$svg = str_replace('%20', ' ', $svg);
	$svg = str_replace('fillPlaceholder', '#{$fill-color}', $svg);

	//echo '$svg-path-' . $glyphName . ": '" .$svgGlyphArray[$unicode]['path']. "';\r\n";
	
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
function parsePath($d) {
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
function transformPath($path, $height=16, $descent= -4) {
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
function pathString($path) {
	$d = '';
	foreach($path as $args) {
		$d .= $args[0];
		unset($args[0]);
		$d .= implode(',', $args);
	}
	return trim($d);
}

function pathString2($path) {
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
			$args[0] = intval($args[0]);
			$args[1] = intval($args[1]);
			$args[2] = ($args[0] == $args[1]) ? 0 : $args[2]; // done in transform
			$args[3] = (int)(bool)$args[4];
			$args[4] = (int)(bool)$args[5];
			$args[5] = intval($args[5]);
			$args[6] = intval($args[6]);
		} else {
			foreach($args as $i => $v) {
				$args[$i] = intval($v);
			}
		}
		$d .= implode(',', $args);
	}
	return trim($d);
}


/**
 * Convert absolute path data coordinates to relative.
 *
 * @param {Array} path input path data
 * @param {Object} params plugin params
 * @return {Array} output path data
 */
 
function convertToRelative($path) {
	$point = array(0, 0);
	$subpathPoint = array(0, 0);

	$relativePath = array();
	
	foreach($path as $index => $data) {
		$instruction = $data[0];
		unset($data[0]);
		$data = array_values($data);

		// data !== !z
		if ($data) {
			// already relative
			// recalculate current point
			if (strpos('mcslqta', $instruction) !== false) {
				$point[0] += $data[count($data) - 2];
				$point[1] += $data[count($data) - 1];
				if ($instruction === 'm') {
					$subpathPoint[0] = $point[0];
					$subpathPoint[1] = $point[1];
				}
			} else if ($instruction === 'h') {
				$point[0] += $data[0];
			} else if ($instruction === 'v') {
				$point[1] += $data[0];
			}
			// end of IF


			// convert absolute path data coordinates to relative
			// if "M" was not transformed from "m"
			// M → m
			if ($instruction === 'M') {
				if ($index > 0) {
					$instruction = 'm';
				}

				$data[0] -= $point[0];
				$data[1] -= $point[1];

				$subpathPoint[0] = $point[0] += $data[0];
				$subpathPoint[1] = $point[1] += $data[1];
			}

			// L → l
			// T → t
			else if (strpos('LT', $instruction) !== false) {
				$instruction = strtolower($instruction);

				// x y
				// 0 1
				$data[0] -= $point[0];
				$data[1] -= $point[1];

				$point[0] += $data[0];
				$point[1] += $data[1];
			// C → c
			} else if ($instruction === 'C') {
				$instruction = 'c';

				// x1 y1 x2 y2 x y
				// 0  1  2  3  4 5
				$data[0] -= $point[0];
				$data[1] -= $point[1];
				$data[2] -= $point[0];
				$data[3] -= $point[1];
				$data[4] -= $point[0];
				$data[5] -= $point[1];

				$point[0] += $data[4];
				$point[1] += $data[5];

			// S → s
			// Q → q
			} else if (strpos('SQ', $instruction) !== false) {
				$instruction = strtolower($instruction);

				// x1 y1 x y
				// 0  1  2 3
				$data[0] -= $point[0];
				$data[1] -= $point[1];
				$data[2] -= $point[0];
				$data[3] -= $point[1];

				$point[0] += $data[2];
				$point[1] += $data[3];

			// A → a
			} else if ($instruction === 'A') {
				$instruction = 'a';

				// rx ry x-axis-rotation large-arc-flag sweep-flag x y
				// 0  1  2			   3			  4		  5 6
				$data[5] -= $point[0];
				$data[6] -= $point[1];

				$point[0] += $data[5];
				$point[1] += $data[6];

			// H → h
			} else if ($instruction === 'H') {
				$instruction = 'h';
				$data[0] -= $point[0];
				$point[0] += $data[0];

			// V → v
			} else if ($instruction === 'V') {

				$instruction = 'v';
				$data[0] -= $point[1];
				$point[1] += $data[0];
			}

			$relativePath[$index] = array_merge(array($instruction), $data);
		} 
		else if ($instruction == 'z') { // !data === z, reset current point
			$relativePath[$index][0] = $instruction;

			$point[0] = $subpathPoint[0];
			$point[1] = $subpathPoint[1];
		}
	}
	return $relativePath;
}