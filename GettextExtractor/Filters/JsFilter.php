<?php
/********************************************************************\
/*  This file is part of the Fregis System (http://www.fregis.cz)     \
/*  Copyright (c) 2012 Karel Hák, Martin Jelič, Jakub Kocourek         \
/*                                                                     /
/*  @license http://www.fregis.cz/license                             /
/********************************************************************/

class GettextExtractor_Filters_JsFilter implements \GettextExtractor_Filters_IFilter
{
	public function extract($file)
	{
		$pInfo = pathinfo($file);
		$data = array();
		foreach (file($file) as $line => $contents) {
			// match all jsTranslate(..translated text.. ) tags

			preg_match_all('/'.
				'jsTranslate\(\s*(\'[^\']*\'|"[^"]*")\)'. // js text for translate
				'/', $contents, $matches);
			if (empty($matches)) continue;
			if (empty($matches[0])) continue;

			foreach ($matches[1] as $match) {
				if($match == "") continue;
				$result = array(
					\GettextExtractor_Extractor::LINE => $line + 1
				);
				$result[\GettextExtractor_Extractor::SINGULAR] = substr($match, 1, -1);
				$data[] = $result;
			}
		}
		return $data;
	}
}