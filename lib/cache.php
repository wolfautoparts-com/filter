<?php
use Wolf\Filter\Observer\TopMenuGetHTMLBefore as Ob;

/**
 * 2019-09-07
 * 2019-09-09 We can not cache the result.
 * @used-by \Wolf\Filter\Observer\TopMenuGetHTMLBefore::execute()
 * @return mixed[]
 */
function wolf_tree_load() {return false === ($r = df_cache_load(Ob::CACHE_KEY)) ? [] : unserialize($r);}