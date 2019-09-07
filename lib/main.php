<?php
use Wolf\Filter\Observer\Navigation as Ob;
/**
 * 2019-09-07
 * @used-by \Wolf\Filter\Block\Navigation::getConfigJson()
 * @used-by \Wolf\Filter\Controller\Garage\AllCars::execute()
 * @used-by \Wolf\Filter\Controller\Index\Change::execute()
 * @used-by \Wolf\Filter\Observer\Navigation::execute()
 * @return mixed[]
 */
function wolf_tree_load() {return false === ($r = df_cache_load(Ob::CACHE_KEY)) ? [] : unserialize($r);}

