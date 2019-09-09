<?php
use Wolf\Filter\Observer\TopMenuGetHTMLBefore as Ob;

/**
 * 2019-09-07
 * @used-by \Wolf\Filter\Block\Navigation::topLevel()
 * @used-by \Wolf\Filter\Controller\Index\Change::execute()
 * @used-by \Wolf\Filter\Observer\TopMenuGetHTMLBefore::execute()
 * @return mixed[]
 */
function wolf_tree_load() {return false === ($r = df_cache_load(Ob::CACHE_KEY)) ? [] : unserialize($r);}