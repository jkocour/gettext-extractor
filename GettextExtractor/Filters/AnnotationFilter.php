<?php

class GettextExtractor_Filters_AnnotationFilter implements GettextExtractor_Filters_IFilter {

	public function extract($file) {
		$pInfo = pathinfo($file);
		$data = array();
		foreach (file($file) as $line => $contents) {
			$line++;
			preg_match_all('#' .
					'\*.*@[\w]+\((.+)\)' . // annotations
					'#', $contents, $matches);
			if (empty($matches))
				continue;
			if (empty($matches[0]))
				continue;
			foreach ($matches[1] as $match) {
				if ($match == "")
					continue;
				$msgs = preg_split("/,/", $match);
				foreach ($msgs as $msg) {
					$msg = trim($msg);
					if (($start = strpos($msg, "= \"")) !== FALSE) {
						$msg = substr($msg, $start + 2);
					} elseif (($start = strpos($msg, "=\"")) !== FALSE) {
						$msg = substr($msg, $start + 1);
					}

					if ((substr($msg, 0, 1) == "\"") || (substr($msg, 0, 1) == "'")) {
						$msg = substr($msg, 1, -1);
					}

					$data[$msg][GettextExtractor_Extractor::SINGULAR] = $msg;
					$data[$msg][GettextExtractor_Extractor::FILE] = $pInfo['basename'];
					$data[$msg][GettextExtractor_Extractor::LINE] = $line;
				}
			}
		}
		return $data;
	}

}
