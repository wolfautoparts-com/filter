<?php
/**
 * 2019-09-09
 * @used-by \Wolf\Filter\Observer\ControllerActionPredispatch::execute()
 * @param string $s
 * @return string
 */
function wolf_u2n($s) {return ucwords(preg_replace('/\/|-/', ' ', df_strip_ext($s)));}