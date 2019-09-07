<?php
namespace Wolf\Filter\Block;
use Wolf\Filter\Observer\Navigation as Ob;
class Topmenu extends \Magento\Theme\Block\Html\Topmenu {
	/**
	 * @param string $outermostClass
	 * @param string $childrenWrapClass
	 * @param int $limit
	 * @return string
	 */
	function getHtml($outermostClass = '', $childrenWrapClass = '', $limit = 0) {
		$cacheId = 'wolf_category_filter_menu_html';
		$fromCache = false;
		$myData = [];
		if (false !== ($data = df_cache_load($cacheId))) {
			$myData = unserialize($data);
			$fromCache = true;
		}
		else {
			$myData['menu'] = $this->getMenu();
		}
		$this->_eventManager->dispatch('page_block_html_topmenu_gethtml_before', [
			'block' => $this, 'menu' => $myData['menu'], 'request' => $this->getRequest()
		]);
		$myData['menu']->setOutermostClass($outermostClass);
		$myData['menu']->setChildrenWrapClass($childrenWrapClass);
		if (!$fromCache) {
			$myData['html'] = $this->_getHtml($myData['menu'], $childrenWrapClass, $limit);
			df_cache_save(serialize($myData), $cacheId, [Ob::CACHE_TAG]);
		}
		$transportObject = new \Magento\Framework\DataObject(['html' => $myData['html']]);
		$this->_eventManager->dispatch('page_block_html_topmenu_gethtml_after', [
			'menu' => $myData['menu'], 'transportObject' => $transportObject
		]);
		$html = $transportObject->getHtml();
		return $html;
	}
}