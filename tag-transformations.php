<?php
/*
Plugin Name: Dunstan's Tag Transformations
Plugin URI: http://mathibus.com/archive/2005/06/tag-transformations
Description: <a href="http://1976design.com/blog/archive/2004/07/29/redesign-tag-transform/">Dunstan Orchard's tag transformations</a> can now be used on every WordPress weblog.
Version: 1.0
Author: Dunstan Orchard, Mathias Bynens
Author URI: http://mathibus.com/
*/

$dir = ABSPATH; // trailingslashed path to your WordPress installation
$iri = get_settings('siteurl') . '/';

/*
Code files should go in http://yoursite.com/path/to/wordpress/code/
	ex.	http://yoursite.com/blog/code/123a.txt
		<codeins="123a" />

Images can be placed wherever you want; there's $path, remember?
	ex.	http://yoursite.com/blog/images/123b.png
		<imageins="123b" path="images" [...] />
	ex.	http://yoursite.com/blog/dropbox/123b.gif
		<imageins="123b" path="dropbox" [...] />
	ex.	http://yoursite.com/blog/dropbox/pictures/123b.jpg
		<imageins="123b" path="dropbox/pictures" [...] />
*/

function mj_image_transform($file, $path, $alt, $caption, $class, $link, $linktitle) {
	// set vars
	global $dir, $iri;
	$html = '';
	$container_open = '';
	$container_close = '';
	$link_start = '';
	$link_end = '';
	$filetypes = array('jpg', 'png', 'gif');

	// get filetype for image
	foreach ($filetypes as $type) {
		if (file_exists($dir . $path . '/' . $file . '.' . $type)) {
			$ext = '.' . $type;
			break;
			}
		}

	// if no filetype matched, then quit
	if (!isset($ext)) {
		return '<p><strong>Error:</strong> No filetype matched! Please make sure there&#8217;s an image called <code>' . $file . '</code> in the <code>/' . $path . '/</code> folder.</p>';
		}

	// get file info
	$file_info = getimagesize($dir . $path . '/' . $file . $ext);

	// set vars
	$add_width = ($caption <> '') ? 16 : 12;
	$width = ' style="width: ' . ($file_info[0] + $add_width) . 'px;"';
	$class = ($class == '') ? '' : $class.' ';
	
	// if there's a caption
	if ($caption <> '') { 
		$container_open .= '<div class="' . $class . 'caption"' . $width . '>' . "\n";
		$caption = $caption . "\n";
		$container_close .= '</div>' . "\n";
		$width = '';
		$class = '';
		}

	// if there's a link
	if ($link <> '') {
		// if it's a link to a larger version of the same image
		if ($link == 'large') {
			// get filetype for the larger image
			foreach ($filetypes as $type) {
				if (file_exists($dir . $path . '/large/' . $file . '.' . $type)) {
					$ext2 = '.' . $type;
					break;
					}
				}
			
			// if a filetype was found, set the link
			if (isset($ext2)) {
				$link_start = '<a href="/' . $path . '/large/' . $file . $ext2 . '" title="View a larger version of this image">';
				$link_end = '</a>';
				}
			else {
				return '<p><strong>Error:</strong> No large version found! Please make sure there&#8217;s an image called <code>' . $file . '</code> in the <code>/' . $path . '/large/</code> folder.</p>';
				}
			}

		// else set the link to the IRI specified
		else {
			$link_start = '<a href="' . $link . '" title="' . $linktitle . '">';
			$link_end = '</a>';
			}
		}

	// build the html
	$html .= $container_open;
	$html .= '<div class="' . $class . 'image"' . $width . '>';
	$html .= $link_start . '<img src="' . $iri . $path . '/' . $file . $ext . '" ' . $file_info[3] . ' alt="' . $alt . '" />' . $link_end;
	$html .= $caption;
	$html .= '</div>' . "\n";
	$html .= $container_close;

	// send the html back
	return $html;
	}

// this function is used to find the last occurance of a string within another string
// it's used because strrpos() only looks for a single character, not a string
function strLastPos($haystack, $needle) {
	// flip both strings around and search, then adjust position based on string lengths
	return strlen($haystack) - strlen($needle) - strpos(strrev($haystack), strrev($needle));
	}

function mj_code_transform($filename) {
	global $dir, $iri;
	$filename = 'code/' . $filename . '.txt';
	// set vars
	$list = '';
	$cmnt = '';
	$multi_line_cmnt = 0;
	$tab = '';
	$class = '';
	
	// open the file
	$file = fopen($dir . $filename, 'r')
		or die('<p><strong>Error:</strong> No such file found! Please make sure <code>' . $iri . $filename . '</code> exists.</p>');

	// for each line in the file
	while (!feof($file)) {
		// get line
		$line = fgets($file, 4096);

		// convert tags to safe entities for display
		$line = htmlentities($line);

		// count the number of tabs at the start of the line, and set the appropriate tab class
		$tab = substr_count($line, "\t");
		$tab = ($tab > 0) ? 'tab' . $tab : '';

		// remove any tabs and whitespace at the start of the line
		$line = ltrim($line);

		// find position of comment characters
		$slashslash_pos = strpos($line, '//');
		$apos_pos = strpos($line, "'");
		$slashstar_pos = strpos($line, '/*');
		$starslash_pos = strLastPos($line, '*/');

		// if it's an ongoing multi-line comment
		if ($multi_line_cmnt == 1) {
			$cmnt = 'cmnt';
			$multi_line_cmnt = 1;
			}
		
		// if it's not an ongoing multi-line comment
		if ($multi_line_cmnt <> 1) {
			// if it's a single line comment
			if (($slashslash_pos === 0) || ($apos_pos === 0)) {
				$cmnt = 'cmnt';
				$multi_line_cmnt = 0;
				}
			else {
				$cmnt = '';
				$multi_line_cmnt = 0;
				}

			// if it's potentially the start of a multi-line comment
			if ($slashstar_pos === 0) {
				$cmnt = 'cmnt';

				// if multi-line comment end string is found on the same line
				if ($starslash_pos == (strlen($line) - 3)) {
					$multi_line_cmnt = 0;
					}
				// if the multi-line comment end string is not found on the same line
				else {
					$multi_line_cmnt = 1;
					}
				}
			}

		// if the line contains the multi-line end string
		if ($starslash_pos == (strlen($line) - 3)) {
 			$cmnt = 'cmnt';
			$multi_line_cmnt = 0;
			}

		// if both cmnt and tab classes are to be applied
		if ( ($cmnt <> '') && ($tab <> '') ) {
			$class = ' class="' . $tab . ' ' . $cmnt . '"';
			}
		// if only one class is to be applied
		else if ( ($cmnt <> '') || ($tab <> '') ) {
			$class = ' class="' . $tab . $cmnt . '"';
			}
		// if no classes are to be applied
		else {
			$class = '';
			}

		// remove return and other whitespace at the end of the line
		$line = rtrim($line);

		// if the line is blank, put a space in to stop some browsers collapsing the line
		if ('' == $line) {
			// insert all the information and close the list item
			$list .= '<li>&#160;</li>' . "\n";
			}
		// otherwise insert the line contents
		else {
			// insert all the information and close the list item
			$list .= '<li' . $class . '><code>' . ent2ncr($line) . '</code></li>' . "\n";
			}
		}

	// close the file handle
	fclose($file);

	// add in the link to the file
	$list .= '<li class="download">Download this code: <a href="' . $iri . $filename . '" title="Download the above code as a text file">/' . $filename . '</a></li>';

	// build the list
	$list = '<ol class="code">' . "\n" . $list . "\n" . '</ol>';
	
	// return the list
	return $list;
	}

function mj_addtags($content) {
	$content = preg_replace('!<imageins="(.*?)" path="(.*?)" alt="(.*?)" caption="(.*?)" class="(.*?)" link="(.*?)" linktitle="(.*?)" />!ie', "mj_image_transform('$1', '$2', '$3', '$4', '$5', '$6', '$7')", $content);
	$content = preg_replace('!<imageins="(.*?)" path="(.*?)" alt="(.*?)" caption="(.*?)" class="(.*?)" link="(.*?)" />!ie', "mj_image_transform('$1', '$2', '$3', '$4', '$5', '$6', '')", $content);
	$content = preg_replace('!<imageins="(.*?)" path="(.*?)" alt="(.*?)" caption="(.*?)" class="(.*?)" />!ie', "mj_image_transform('$1', '$2', '$3', '$4', '$5', '', '')", $content);
	$content = preg_replace('!<imageins="(.*?)" path="(.*?)" alt="(.*?)" caption="(.*?)" />!ie', "mj_image_transform('$1', '$2', '$3', '$4', '', '', '')", $content);
	$content = preg_replace('!<imageins="(.*?)" path="(.*?)" alt="(.*?)" />!ie', "mj_image_transform('$1', '$2', '$3', '', '', '', '')", $content);
	$content = preg_replace('!<imageins="(.*?)" path="(.*?)" />!ie', "mj_image_transform('$1', '$2', '', '', '', '', '')", $content);
	$content = preg_replace('!<codeins="(.*?)" />!ie', "mj_code_transform('$1')", $content);
	return $content;
}

remove_filter('the_content', 'wpautop');	// must... remove... first
remove_filter('the_excerpt', 'wpautop');
add_filter('the_content', 'mj_addtags', 10);
add_filter('the_excerpt', 'mj_addtags', 10);
add_filter('the_content', 'wpautop', 11);	// then bring it back
add_filter('the_excerpt', 'wpautop', 11);

?>