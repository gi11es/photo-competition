<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

require_once (dirname(__FILE__) . '/../entities/wall.php');
require_once (dirname(__FILE__) . '/../constants.php');
require_once (dirname(__FILE__) . '/../settings.php');
require_once (dirname(__FILE__) . '/../quotes.php');
require_once (dirname(__FILE__) . '/cache.php');

class UI {
	public static function RenderDiscussion($title, $page, $user_id, $uniqueid = "", $inline = false) {
		global $ADMINS;

		$id = ereg_replace("[^A-Za-z0-9]", "_", $title);

		$result = "<fb:comments xid=\"" . $id . $uniqueid . "\" ".($inline?"showform =\"true\"":"")."canpost=\"true\" candelete=\"" . ((isset ($ADMINS[$user_id]) && $ADMINS[$user_id]) ? "true" : "false") . "\" returnurl=\"" . $page . "\">";
		$result .= "<fb:title>" . $title . "</fb:title>";
		$result .= "</fb:comments>";

		return $result;
	}
	
	public static function RenderWall($title, $picture_id, $topic_id, $page, $hide_author=false, $author_id=null, $user_id, $key=null) {
		global $PAGE;
		global $TEXT;
	
		$wall = Wall :: getWall($picture_id, $topic_id);
		$posts = $wall->getPosts();
		$post_count = count($posts);
		$post_count_text = "Displaying " . $post_count . " post" . ($post_count > 1 ? "s." : ".");
		$result = "<div class=\"wallkit_frame clearfix\"><h3 class=\"wallkit_title\">$title</h3><div class=\"wallkit_subtitle clearfix\"><div style=\"float:left;\">$post_count_text</div><div style=\"float:right;\"></div></div><div class=\"wallkit_form\"><form method=\"post\" action=\"$page".(isset($key)?"?key=".$key:"")."\"><input type=\"hidden\" name=\"topic\" value=\"$topic_id\"/><input type=\"hidden\" name=\"picture\" value=\"$picture_id\"/><textarea id=\"wall_text\" name=\"wall_text\"></textarea><input type=\"submit\" class=\"inputsubmit\" value=\"Post\" /></form></div>";
		$post_time = array();
		foreach ($posts as $array_key => $post) {
			$post_time[$array_key] = $post["post_time"];
		}
		array_multisort($post_time, SORT_DESC, $posts);
		
		$first_post = true;
		foreach ($posts as $post) {
			$result .= "<div class=\"wallkit_post\">";
			
			if ($hide_author && strcmp($post["user_id"], $author_id) == 0) {
				$profilepic = "<img src=\"http://static.ak.facebook.com//pics/t_default.jpg\">";
				$name = "<b>".$TEXT["USER"]."</b>";
				$postclass = "wallkit_postcontent_author";
			} 
			else if (strcmp("500347728", $post["user_id"]) == 0) {
				$profilepic = "<a href=\"".$PAGE["USERS"]."?userid=".$post["user_id"]."\"><fb:profile-pic uid=\"". $post["user_id"] ."\" size=\"t\" linked=\"false\"/></a>";
				$name = "<b>Housekeeper</b>";
				$postclass = "wallkit_postcontent_housekeeper";
			} else if (strcmp($post["user_id"], $author_id) == 0) {
                                $profilepic = "<a href=\"".$PAGE["USERS"]."?userid=".$post["user_id"]."\"><fb:profile-pic uid=\"". $post["user_id"] ."\" size=\"t\" linked=\"false\"/></a>";
				$name = "<a href=\"".$PAGE["USERS"]."?userid=".$post["user_id"]."\"><fb:name ifcantsee=\"Anonymous user\" linked=\"false\" useyou=\"false\" shownetwork=\"true\" uid=\"" . $post["user_id"] . "\"/></a>";
				$postclass = "wallkit_postcontent_author"; 
                        }
                        else {
				$profilepic = "<a href=\"".$PAGE["USERS"]."?userid=".$post["user_id"]."\"><fb:profile-pic uid=\"". $post["user_id"] ."\" size=\"t\" linked=\"false\"/></a>";
				$name = "<a href=\"".$PAGE["USERS"]."?userid=".$post["user_id"]."\"><fb:name ifcantsee=\"Anonymous user\" linked=\"false\" useyou=\"false\" shownetwork=\"true\" uid=\"" . $post["user_id"] . "\"/></a>";
				$postclass = "wallkit_postcontent";
			} 
			
			// Treat the text, automatically transform URLS
			
			$text = stripslashes($post["text"]);
			$text = preg_replace('@(https?://([-\w\.]+)+(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)?)@', '<b><a target="_blank" href="$1">$1</a></b>', $text);
			
			$result .= "<div class=\"wallkit_profilepic\">$profilepic</div>";
			$result .= "<div class=\"$postclass\"><h4>$name wrote <span class=\"wall_time\"><fb:time t='".$post["post_time"]."' preposition=true /></span> </h4>";
			$result .= "<div>". $text . "</div>";
			
			$display_delete = false;
			if ($first_post) {
				$first_post = false;
				if ($user_id == $post["user_id"])
					$display_delete = true;
			}
			
			$result .= "<div class=\"wallkit_actionset\">".($hide_author && strcmp($post["user_id"], $author_id) == 0?"":"<a href=\"http://www.facebook.com/inbox/?compose&id=".$post["user_id"]."\">message</a>").($display_delete?" - <a href=\"".$page."?".(isset($key)?"key=$key&":"")."delete_post=true&picture=".$picture_id."&topic=".$topic_id."&post_user_id=".$post["user_id"]."\">delete</a>":"")."</div>";
			$result .= "</div></div>";
		}
		$result .= "</div>";
		return $result;
	}

	public static function RenderFooter() {
		global $APP_NAME;
	
		$result = "<br/>";
		$result .= '<div class="basictext">'.$APP_NAME.' is brought to you by <a href="http://www.kouiskas.com">Gilles Dubuc</a>, <a href="http://www.darumastudio.com">photographer</a> and <a href="http://www.kouiskas.com/cv/">IT consultant</a>.</div>';
		$result .= "<br/>";
		$result .= "<br/>";
		return $result;
	}

	public static function RenderMenu($currentpage, $uid) {
		global $ADMINS;
		global $PAGE;
		global $PAGE_CODE;
		global $APP_PATH;
		global $APP_REAL_PATH;
                global $APP_REVIEW_PAGE;

		/* try {
			$sortpreference = Cache :: get("topicdisplay-" . $uid);
		} catch (CacheException $e) {
			$sortpreference = 0;
		} */

		$result = '<fb:tabs>';
		$result .= '<fb:tab-item href="' . $PAGE["COMPETITIONS2"] . '" title="Join the revolution!" ' . ($currentpage == $PAGE_CODE['COMPETITIONS2'] || $currentpage == $PAGE_CODE['COMPETITIONS']  ? "selected='true'" : "") . '/>';
		$result .= '<fb:tab-item href="' . $PAGE["CHAT"] . '" title="Discuss" ' . ($currentpage == $PAGE_CODE['CHAT'] ? "selected='true'" : "") . '/>';
		$result .= '<fb:tab-item href="' . $PAGE["WINNERS"] . '" title="Hall of fame" ' . ($currentpage == $PAGE_CODE['WINNERS'] ? "selected='true'" : "") . '/>';
		$result .= '<fb:tab-item href="' . $PAGE["YOUR_SUBMISSIONS"] . '" title="Your entries" ' . ($currentpage == $PAGE_CODE['YOUR_SUBMISSIONS'] ? "selected='true'" : "") . '/>';
		$result .= '</fb:tabs>';

		return $result;
	}

}
?>