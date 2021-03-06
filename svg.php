<?php
// Configuration

// Nodejs-based tool for optimizing SVG vector graphics files
$svgoPath = 'H:/github/svg-files-to-svg-font/node_modules/.bin/svgo.cmd';

// Original SVG icons
$svgIcons = glob('icons/*.svg');

// Template for SVG font
$svgFontTemplate = file_get_contents('svg.template');

// Filename for SVG font
$svgFontFile = 'NetSuite Custom Icons.svg';

// Split template into parts
$svgFontTemplate = explode('<!-- split -->', $svgFontTemplate);
foreach($svgFontTemplate as $key => $value) {
	$svgFontTemplate[$key] = trim($value);
}

// Collect icons data from files
$svgGlyphArray = array();
$svgNum = 0;
foreach($svgIcons as $svgIcon) {
	// Skip empty files
	if(filesize($svgIcon) == 0) {
		var_dump(__LINE__);
		continue;
	}

	// Get svg data from svg icon filename (num, unicode, glyph-name)
	$svgData = str_replace('.svg', '', basename($svgIcon));
	$svgData = preg_split("#[-\.\/]#", $svgData, 3);

	// Skip SVG files with wrong names
	if(!is_array($svgData) || count($svgData) != 3) {
		var_dump($svgData, __LINE__);
		continue;
	}

	$svgGlyphArray[$svgNum]['num'] = $svgData[0];
	$svgGlyphArray[$svgNum]['unicode'] = $svgData[1];
	$svgGlyphArray[$svgNum]['glyphName'] = $svgData[2];
	$svgGlyphArray[$svgNum]['path'] = $svgIcon;
	$svgGlyphArray[$svgNum]['minPath'] = str_replace(array('icons/', '.svg'), array('icons/min/', '.min.svg'), $svgIcon);

	$svgGlyphArray[$svgNum]['svgFontPath'] = getSvgPathForFont($svgIcon);

	// Optimaze SVG files with svgo
	// Minify svg icon with svgo tool
	if(!file_exists($svgGlyphArray[$svgNum]['minPath'])) {
		system($svgoPath . ' -i ' . $svgGlyphArray[$svgNum]['path'] . ' -o ' . $svgGlyphArray[$svgNum]['minPath']);
	}

	$svgGlyphArray[$svgNum]['svgCssPath'] = getSvgPathForCss($svgGlyphArray[$svgNum]['minPath']);
	$svgNum++;
}

//var_dump($svgGlyphArray);

$svgFontFileContent = $svgFontTemplate[0] . "\r\n";
foreach ($svgGlyphArray as $svgData) {
	$svgGlyph = str_replace('{$path}', $svgData['svgFontPath'], $svgFontTemplate[1]);
	$svgGlyph = str_replace('{$unicode}', '&#x' . $svgData['unicode'] . ';', $svgGlyph);
	$svgGlyph = str_replace('{$glyphName}', $svgData['glyphName'], $svgGlyph);
	$svgFontFileContent .= "\t" . $svgGlyph . "\r\n";
}
$svgFontFileContent .= $svgFontTemplate[2] . "\r\n";
if(file_put_contents($svgFontFile, $svgFontFileContent)) {
	echo $svgFontFile . ' created' . "\r\n";
}

function getSvgPathForFont($svgIcon) {
	if(!file_exists($svgIcon)) {
		var_dump($svgIcon, __LINE__);
		return '';
	}
	// Get d attribute from original svg file
	$svgContent = file_get_contents($svgIcon);
	preg_match("#\sd=\"(.+?)\"#umsi", $svgContent, $path);

	// Exit if there is no d attribute in path
	if(!isset($path[1])) {
		var_dump($svgIcon, $svgContent, $path, __LINE__);
		return '';
	}
	// 1. Get path string and convert it into array
	$parsedPath = parsePath($path[1]);

	// 2. Convert from absolute to relative
	// $parsedPath = convertToRelative($parsedPath);
	
	// 3. Transform path for font
	$transformedPath = transformPath($parsedPath);
	
	// 4. Get path array and convert to string
	$path = pathString($transformedPath);

	return $path;
}

function getSvgPathForCss($svgIconMin) {
	if(!file_exists($svgIconMin)) {
		var_dump($svgIconMin, __LINE__);
		return '';
	}
	// Get d attribute from mini svg file
	$svgMinContent = file_get_contents($svgIconMin);
	preg_match("#\sd=\"(.+?)\"#umsi", $svgMinContent, $path);

	// Exit if there is no d attribute in path
	if(!isset($path[1])) {
		var_dump($svgIconMin, $svgMinContent, $path, __LINE__);
		return '';
	}
	return $path[1];
}


/* CSS Generation */

$css01 = '';
$css02 = '';
$css03 = '';
$css04 = '';
foreach($svgGlyphArray as $glyphData) {
	$css01 .= '$fa-var-' . $glyphData['glyphName'] . '-custom-icon: "\\' . $glyphData['unicode'] . '";
%#{$fa-css-prefix}-' . $glyphData['glyphName'] . '-custom-icon:before {
	content: $fa-var-' . $glyphData['glyphName'] . '-custom-icon;
}' . "\r\n\r\n";

$css01 .= '// .icon-' . $glyphData['glyphName'] . '-custom - ' . $glyphData['glyphName'] . ' icon' . "\r\n";

$css02 .= '.icon-' . $glyphData['glyphName'] . '-custom,' . "\r\n";

	$css03 .= '.icon-' . $glyphData['glyphName'] . '-custom {
	@extend %fa-' . $glyphData['glyphName'] . '-custom-icon;
}' . "\r\n\r\n";

	$css04 .= '$svg-path-' . $glyphData['glyphName'] . ": '" . $glyphData['svgCssPath'] . "';\r\n";
	
	$css04 .= '.icon-' . $glyphData['glyphName'] . '-custom-svg {
	background-image: url(create-svg($svg-path-' . $glyphData['glyphName'] . ', $sc-color-primary));
}' . "\r\n\r\n";
}

echo $css01, $css02, $css03, $css04;

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
 * Back into a string
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