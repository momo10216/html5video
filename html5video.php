<?php
/**
* @Copyright Copyright (C) 2013 Norbert Kuemin <momo_102@bluemail.ch>
* @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.plugin.plugin');

class plgContentHtml5Video extends JPlugin {

	var $noContextMenuElementIdList = array();

	public function onContentPrepare($context, &$article, &$params, $limitstart) {
		$app = JFactory::getApplication();
	  	$globalParams = $this->params;
		$hits = preg_match_all('#{html5video\s*(.*?)}#s', $article->text, $matches);
		if (!empty($hits)) {
			$document = JFactory::getDocument();			
			for ($i=0; $i<$hits; $i++) {
				$entryParamsText = $matches[1][$i];
				$plgParams = $this->html5Video_getParams($globalParams, $entryParamsText);
				$html = $this->html5Video_createHTML($i, $plgParams);
				$pattern = str_replace('[', '\[', $matches[0][$i]);
				$pattern = str_replace(']', '\]', $pattern);
				$pattern = str_replace('/', '\/', $pattern);
				$pattern = str_replace('|', '\|', $pattern);
		    		$article->text = preg_replace('/'.$pattern.'/', $html, $article->text, 1);
			}
			if (count($this->noContextMenuElementIdList) > 0) {
				$article->text .= $this->html5Video_noContextMenu();
			}
		} else {
			return false;
		}
		return true;
	}

	protected function html5Video_getParams($globalParams, $entryParamsText) {

		// Initialize with the global paramteres
		$entryParamsList['width'] 		= $globalParams->get('width');
		$entryParamsList['height'] 		= $globalParams->get('height');
		$entryParamsList['controls']		= $globalParams->get('controls');
		$entryParamsList['autoplay']		= $globalParams->get('autoplay');
		$entryParamsList['preload']		= $globalParams->get('preload');
		$entryParamsList['loop']		= $globalParams->get('loop');
		$entryParamsList['poster_visibility']	= $globalParams->get('poster_visible');
		$entryParamsList['nocontextmenu'] 	= $globalParams->get('nocontextmenu');
		$entryParamsList['video_mp4']		= '';
		$entryParamsList['video_webm']		= '';
		$entryParamsList['video_ogg']		= '';
		$entryParamsList['poster']		= '';
		$entryParamsList['text_track']		= '';

		// Overwrite with the local paramteres
		$items = explode(' ', $entryParamsText);
		foreach ($items as $item) {
			if ($item != '') {
				$item	= explode('=', $item);
				$name 	= $item[0];
				$value	= strtr($item[1], array('['=>'', ']'=>''));
				if ($name == "text_track") {
					$entryParamsList[$name][] = $value;
				} else {
					$entryParamsList[$name] = $value;
				}
			}
		}
		return $entryParamsList;
	}

	protected function html5Video_createJS($js) {
		$retval = "<script language=\"JavaScript1.2\">\n";
		$retval .= "<!-- Begin\n";
		$retval .= $js;
		$retval .= "// End -->\n";
		$retval .= "</script>\n";
		return $retval;
	}

	protected function html5Video_noContextMenu() {
		$js = "function nocontext(e) {\n";
		$js .= "   var clickedId = (e==null) ? event.srcElement.id : e.target.id;\n";
		foreach ($this->noContextMenuElementIdList as $elementId) {
			$js .= "   if (clickedId == \"".$elementId."\") { return false; }\n";
		}
		$js .= "};\n";
		$js .= "document.oncontextmenu = nocontext;\n";
		return $this->html5Video_createJS($js);
	}

	protected function html5Video_createHTML($id, $params) {
		$elementId = "html5video_".$id;
		$html = '';
		$html .= "\n".'<video id="'.$elementId.'" class="html5video"';
		// Width
		$width = $params['width'];
		if (!empty($width)) {
			$html .= ' width="'.$width.'"';
		}
		// Height
		$height = $params['height'];
		if (!empty($width)) {
			$html .= ' height="'.$height.'"';
		}
		// Controls
		if ($params['controls'] == "1") {
			$html .= ' controls="controls"';
		}
		// Autoplay
		if ($params['autoplay'] == "1") {
			$html .= ' autoplay="autoplay"';
		}
		// Preload
		$preload = $params['preload'];
		if ($preload == "auto" || $preload == "metadata" || $preload == "none") {
			$html .= ' preload="'.$preload.'"';
		}
		// Loop
		if ($params['loop'] == "1") {
			$html .= ' loop="loop"';
		}
		// Poster image
		$poster	= $params['poster'];
		if ($params['poster_visibility'] == "1" && $poster != "") {
			$html .= ' poster="'.$poster.'"';
		}
		$html .= ' data-setup="{}">'."\n";

		// Add video sources
		$video_mp4 = $params['video_mp4'];
		if (!empty($video_mp4)) {
			$html .= '   <source src="'.$video_mp4.'" type="video/mp4" />'."\n";
		}
		$video_webm = $params['video_webm'];
		if (!empty($video_webm)) {
			$html .= '   <source src="'.$video_webm.'" type="video/webm" />'."\n";
		}
		$video_ogg = $params['video_ogg'];
		if (!empty($video_ogg)) {
			$html .= '   <source src="'.$video_ogg.'" type="video/ogg" />'."\n";
		}
		
		// Text tracks
		$tracks	= $params['text_track'];
		if (!empty($tracks)) {
			$text_track_html = '';
			foreach ($tracks AS $track) {
				$track_items = explode('|', $track);
				$text_track_html .= '   <track kind="'.$track_items[0].'" src="'.$track_items[1].'" srclang="'.$track_items[2].'" label="'.$track_items[3].'" />'."\n";
			}
		}
		$html .= $text_track_html;

		$html .= '</video>'."\n";;

		// Disable context menu
		if ($params['nocontextmenu'] == "1") {
			array_push($this->noContextMenuElementIdList,$elementId);
		}

		return $html;

	}
	
}
