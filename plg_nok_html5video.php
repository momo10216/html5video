<?php
/**
* @Copyright Copyright (C) 2013 Norbert Kuemin <momo_102@bluemail.ch>
* @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.plugin.plugin');

class plgContentplg_nok_html5video extends JPlugin {

	var $noContextMenuElementIdList = array();

	public function onContentPrepare($context, &$article, &$params, $limitstart) {
		$app = JFactory::getApplication();
	  	$globalParams = $this->params;
		$found = false;
		$document = JFactory::getDocument();
		$hits_audio = preg_match_all('#{html5audio\s*(.*?)}#s', $article->text, $matches_audio);
		if (!empty($hits_audio)) {
			$found = true;
			for ($i=0; $i<$hits_audio; $i++) {
				$entryParamsText = $matches_audio[1][$i];
				$plgParams = $this->html5Common_getParams($globalParams, $entryParamsText);
				$html = $this->html5Audio_createHTML($i, $plgParams);
				$pattern = str_replace('[', '\[', $matches_audio[0][$i]);
				$pattern = str_replace(']', '\]', $pattern);
				$pattern = str_replace('/', '\/', $pattern);
				$pattern = str_replace('|', '\|', $pattern);
				$article->text = preg_replace('/'.$pattern.'/', $html, $article->text, 1);
			}
		}
		$hits_video = preg_match_all('#{html5video\s*(.*?)}#s', $article->text, $matches_video);
		if (!empty($hits_video)) {
			$found = true;
			for ($i=0; $i<$hits_video; $i++) {
				$entryParamsText = $matches_video[1][$i];
				$plgParams = $this->html5Common_getParams($globalParams, $entryParamsText);
				$html = $this->html5Video_createHTML($i, $plgParams);
				$pattern = str_replace('[', '\[', $matches_video[0][$i]);
				$pattern = str_replace(']', '\]', $pattern);
				$pattern = str_replace('/', '\/', $pattern);
				$pattern = str_replace('|', '\|', $pattern);
				$article->text = preg_replace('/'.$pattern.'/', $html, $article->text, 1);
			}
		}
		if (count($this->noContextMenuElementIdList) > 0) {
			$article->text .= $this->html5Common_noContextMenu();
		}
		return $found;
	}

	protected function html5Common_getParams($globalParams, $entryParamsText) {

		// Initialize with the global paramteres
		$entryParamsList['width'] = $globalParams->get('width');
		$entryParamsList['height'] = $globalParams->get('height');
		$entryParamsList['controls'] = $globalParams->get('controls');
		$entryParamsList['autoplay'] = $globalParams->get('autoplay');
		$entryParamsList['muted'] = $globalParams->get('muted');
		$entryParamsList['preload'] = $globalParams->get('preload');
		$entryParamsList['loop'] = $globalParams->get('loop');
		$entryParamsList['poster_visibility'] = $globalParams->get('poster_visibility');
		$entryParamsList['nocontextmenu'] = $globalParams->get('nocontextmenu');
		$entryParamsList['nosupport'] = $globalParams->get('nosupport');
		$entryParamsList['audio_mp3'] = '';
		$entryParamsList['audio_wav'] = '';
		$entryParamsList['audio_ogg'] = '';
		$entryParamsList['audio_aac'] = '';
		$entryParamsList['video_mp4'] = '';
		$entryParamsList['video_webm'] = '';
		$entryParamsList['video_ogg'] = '';
		$entryParamsList['poster'] = '';
		$entryParamsList['text_track'] = '';
		$entryParamsList['class'] = '';
		$entryParamsList['id'] = '';

		// Overwrite with the local paramteres
		$items = explode('] ', $entryParamsText);
		foreach ($items as $item) {
			if ($item != '') {
				$item	= explode('=[', $item);
				$name 	= $item[0];
				$value	= strtr($item[1], array('['=>'', ']'=>''));
				if ($name == 'text_track') {
					$entryParamsList[$name][] = $value;
				} else if ($name == 'nosupport_'.$this->html5Common_getCurrentLanguageCode()) {
					$entryParamsList['nosupport'] = $value;
				} else {
					$entryParamsList[$name] = $value;
				}
			}
		}
		if ($entryParamsList['autoplay'] == '1') {
			$entryParamsList['muted'] = '1';
		}
		return $entryParamsList;
	}

	protected function html5Common_createJS($js) {
		$retval = "<script language=\"JavaScript1.2\">\n";
		$retval .= "<!-- Begin\n";
		$retval .= $js;
		$retval .= "// End -->\n";
		$retval .= "</script>\n";
		return $retval;
	}

	protected function html5Common_noContextMenu() {
		$js = "function nocontext(e) {\n";
		$js .= "   var clickedId = (e==null) ? event.srcElement.id : e.target.id;\n";
		foreach ($this->noContextMenuElementIdList as $elementId) {
			$js .= "   if (clickedId == \"".$elementId."\") { return false; }\n";
		}
		$js .= "};\n";
		$js .= "document.oncontextmenu = nocontext;\n";
		return $this->html5Common_createJS($js);
	}

	protected function html5Common_createAttributeHTML($params) {
		$html = '';
		// Controls
		if ($params['controls'] == '1') {
			$html .= ' controls="controls"';
		}
		// Autoplay
		if ($params['autoplay'] == '1') {
			$html .= ' autoplay="autoplay"';
		}
		// Autoplay
		if ($params['muted'] == '1') {
			$html .= ' muted="muted"';
		}
		// Preload
		$preload = $params['preload'];
		if ($preload == "auto" || $preload == "metadata" || $preload == "none") {
			$html .= ' preload="'.$preload.'"';
		}
		// Loop
		if ($params['loop'] == '1') {
			$html .= ' loop="loop"';
		}
		return $html;
	}

	protected function html5Common_createInnerHTML($params) {
		// Text tracks
		$tracks	= $params['text_track'];
		$spacing = '   ';
		$text_inner_html = '';
		if (!empty($tracks)) {
			foreach ($tracks AS $track) {
				$track_items = explode('|', $track);
				$text_inner_html .= $spacing.'<track kind="'.$track_items[0].'" src="'.$track_items[1].'" srclang="'.$track_items[2].'" label="'.$track_items[3].'" />'."\n";
			}
		}
		if ($params['nosupport'] != '') {
			$text_inner_html .= $spacing.$params['nosupport']."\n";
		}
		return $text_inner_html;
	}

	protected function html5Common_getCurrentLanguageCode() {
		$lang = JFactory::getLanguage();
		$tag = $lang->getTag();
		$tag_items = explode('-', $tag);
		return $tag_items[0];
	}

	protected function html5Common_addNoContextMenuElementId($params,$elementId) {
		if ($params['nocontextmenu'] == "1") {
			array_push($this->noContextMenuElementIdList,$elementId);
		}
	}

	protected function html5Audio_createHTML($id, $params) {
		$class = 'html5audio';
		if (!empty($params['class'])) { $class = $params['class']; }
		$elementId = $class.'_'.$id;
		if (!empty($params['id'])) { $elementId = $params['id']; }
		$html = '';
		$style = '';
		$html .= "\n".'<audio id="'.$elementId.'" class="'.$class.'"';
		$html .= $this->html5Common_createAttributeHTML($params);
		// Width
		$width = $params['width'];
		if (!empty($width)) {
			$style .= ' width: '.$width.'px;';
		}
		// Height
		$height = $params['height'];
		if (!empty($width)) {
			$style .= ' height: '.$height.'px;';
		}
		// Poster image
		$poster	= $params['poster'];
		if ($params['poster_visibility'] == "1" && $poster != "") {
			$style .= ' background-image: url(\''.$poster.'\');';
		}
		if (!empty($style)) {
			$html .= ' style="'.$style.'"';
		}
		$html .= ">\n";
		// Add audio sources
		$audio_mp3 = $params['audio_mp3'];
		if (!empty($audio_mp3)) {
			$html .= '   <source src="'.$audio_mp3.'" type="audio/mpeg" />'."\n";
		}
		$audio_aac = $params['audio_aac'];
		if (!empty($audio_aac)) {
			$html .= '   <source src="'.$audio_aac.'" type="audio/mp4" />'."\n";
		}
		$audio_ogg = $params['audio_ogg'];
		if (!empty($audio_ogg)) {
			$html .= '   <source src="'.$audio_ogg.'" type="audio/ogg" />'."\n";
		}
		$audio_wav = $params['audio_wav'];
		if (!empty($audio_wav)) {
			$html .= '   <source src="'.$audio_wav.'" type="audio/wav" />'."\n";
		}
		$html .= $this->html5Common_createInnerHTML($params);
		$html .= '</audio>'."\n";;
		$this->html5Common_addNoContextMenuElementId($params,$elementId);
		return $html;
	}
	
	protected function html5Video_createHTML($id, $params) {
		$class = 'html5video';
		if (!empty($params['class'])) { $class = $params['class']; }
		$elementId = $class.'_'.$id;
		if (!empty($params['id'])) { $elementId = $params['id']; }
		$html = '';
		$html .= "\n".'<video id="'.$elementId.'" class="'.$class.'"';
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
		// Poster image
		$poster	= $params['poster'];
		if ($params['poster_visibility'] == "1" && !empty($poster)) {
			$html .= ' poster="'.$poster.'"';
		}
		$html .= $this->html5Common_createAttributeHTML($params);
		$html .= ">\n";
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
		$html .= $this->html5Common_createInnerHTML($params);
		$html .= '</video>'."\n";;
		$this->html5Common_addNoContextMenuElementId($params,$elementId);
		return $html;
	}
}
