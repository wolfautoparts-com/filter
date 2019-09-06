<?php

namespace Wolf\Filter\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;

class Test extends Action
{

    /**
     * Change constructor.
     * @param Context $context
     * @param LoggerInterface $logger
     * @param ResultFactory $resultFactory
     */
    function __construct(
        Context $context,
        LoggerInterface $logger,
        ResultFactory $resultFactory
    )
    {
        $this->_logger = $logger;
        $this->_resultFactory = $resultFactory;




        parent::__construct($context);
    }

    /**
     *
     */
    function execute()
    {

        $result = $this->_resultFactory->create('raw');



        $result->setHeader('Content-Type', 'text/xml');

        ob_start();
        phpinfo();
        $contents = ob_get_contents();
        ob_clean();

        $result->setContents($contents);
        return $result;
    }

}
