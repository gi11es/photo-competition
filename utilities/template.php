<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

class TemplateException extends Exception {
}

class Template {

	/*
	 * Returns a version of the template filled with the values contained in the $variables hashmap
	 */
	public static function Templatize($text, $variables) {
		$templatized = $text;
	
		foreach ($variables as $pattern => $replacement) {
			$templatized = str_replace("#".$pattern, $replacement, $templatized);
		}
		return $templatized;
	}
}

?>
