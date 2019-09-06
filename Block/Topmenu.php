<?php
namespace Wolf\Filter\Block;

use Magento\Framework\Data\Tree\Node;
use Magento\Framework\Data\Tree\NodeFactory;
use Magento\Framework\Data\TreeFactory;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Framework\App\CacheInterface;
use \Psr\Log\LoggerInterface;


class Topmenu extends \Magento\Theme\Block\Html\Topmenu
{

    protected $_logger;

    protected $_cache;

    /**
     * @param Template\Context $context
     * @param NodeFactory $nodeFactory
     * @param TreeFactory $treeFactory
     * @param LoggerInterface $logger
     * @param CacheInterface $cache
     * @param array $data
     */
    function __construct(
        Template\Context $context,
        NodeFactory $nodeFactory,
        TreeFactory $treeFactory,
        LoggerInterface $logger,
        CacheInterface $cache,
        array $data = []
    ) {

        $this->_logger = $logger;
        $this->_cache = $cache;

        parent::__construct($context, $nodeFactory, $treeFactory, $data);
    }


    /**
     * Get top menu html
     *
     * @param string $outermostClass
     * @param string $childrenWrapClass
     * @param int $limit
     * @return string
     */
    function getHtml($outermostClass = '', $childrenWrapClass = '', $limit = 0)
    {

        $cacheId = 'wolf_category_filter_menu_html';
        $cacheTags = array('WOLF_CATEGORY_FILTER');
        $fromCache = false;
        $myData = array();


        if(false !== ($data = $this->_cache->load($cacheId))) {

            $myData = unserialize($data);
            $fromCache = true;

        } else {

            $myData['menu'] = $this->getMenu();

        }

        $this->_eventManager->dispatch(
            'page_block_html_topmenu_gethtml_before',
            ['menu' => $myData['menu'], 'block' => $this, 'request' => $this->getRequest()]
        );


        $myData['menu']->setOutermostClass($outermostClass);
        $myData['menu']->setChildrenWrapClass($childrenWrapClass);


        if(!$fromCache) {

            $myData['html'] = $this->_getHtml($myData['menu'], $childrenWrapClass, $limit);
            $this->_cache->save(serialize($myData), $cacheId, $cacheTags);
        }


        $transportObject = new \Magento\Framework\DataObject(['html' => $myData['html']]);

        $this->_eventManager->dispatch(
            'page_block_html_topmenu_gethtml_after',
            ['menu' => $myData['menu'], 'transportObject' => $transportObject]
        );

        $html = $transportObject->getHtml();
        return $html;
    }

}