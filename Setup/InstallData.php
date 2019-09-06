<?php

namespace Wolf\Filter\Setup;


use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Config;
use Magento\Customer\Model\Customer;
use \Psr\Log\LoggerInterface;

class InstallData implements InstallDataInterface
{


    /**
     * @var \Magento\Eav\Setup\EavSetupFactory
     */
    private $_eavSetupFactory;

    /**
     * @var \Magento\Eav\Model\AttributeRepository
     */
    private $_attributeRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * Init
     *
     * @param \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory
     * @param \Magento\Eav\Model\AttributeRepository $attributeRepository
     * @param \Magento\Framework\App\Cache\TypeListInterface
     */
    function __construct(
        \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory,
        \Magento\Eav\Model\AttributeRepository $attributeRepository,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        LoggerInterface $logger
    )
    {
        $this->_eavSetupFactory = $eavSetupFactory;
        $this->_attributeRepository = $attributeRepository;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        $this->_logger = $logger;

    }

    /**
     *
     */
    private function cleanCache(){
        //$types = array('config','layout','block_html','collections','reflection','db_ddl','eav','config_integration','config_integration_api','full_page','translate','config_webservice');
        $types = $this->_cacheTypeList->getTypes();
        $this->_logger->debug('types');
        $this->_logger->debug(json_encode($types));


        foreach ($types as $type) {
            $this->_logger->debug('type[id]');
            $this->_logger->debug($type['id']);
            $this->_cacheTypeList->cleanType($type['id']);

        }
        foreach ($this->_cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }


    /**
     * Installs DB schema for module
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Exception
     * @return void
     */
	function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
	{

        $installer = $setup;
        $installer->startSetup();

        $eavSetup = $this->_eavSetupFactory->create(['setup' => $setup]);

        $eavSetup->removeAttribute(\Magento\Customer\Model\Customer::ENTITY, 'garage_json');
        $eavSetup->removeAttribute(\Magento\Customer\Model\Customer::ENTITY, 'customer_garage_json');
        $this->cleanCache();

        // add customer_attribute to customer
        $eavSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY, 'customer_garage_json', [
            'type' => 'text',
            'label' => 'Garage JSON',
            'input' => 'text',
            'required' => false,
            'system' => 0,
            'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
            'sort_order' => '200']);

        // allow customer_attribute attribute to be saved in the specific areas
        $attribute = $this->_attributeRepository->get('customer', 'customer_garage_json');
        $setup->getConnection()
            ->insertOnDuplicate(
                $setup->getTable('customer_form_attribute'),
                [
                    ['form_code' => 'adminhtml_customer', 'attribute_id' => $attribute->getId()],
                    ['form_code' => 'customer_account_create', 'attribute_id' => $attribute->getId()],
                    ['form_code' => 'customer_account_edit', 'attribute_id' => $attribute->getId()],
                ]
            );

        $installer->endSetup();


	    /*

        $installer = $setup;
        $installer->startSetup();

        $customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);

//
//        $my_attribute = $customerSetup->getEavConfig()->getAttribute(\Magento\Customer\Model\Customer::ENTITY, ' garage_json');
//        $my_attribute->setData('garage_json', '{test: true}');
//        $my_attribute->save();


        $customerSetup->removeAttribute(\Magento\Customer\Model\Customer::ENTITY, "garage_json");

        $this->cleanCache();

        $customerSetup->addAttribute(\Magento\Customer\Model\Customer::ENTITY, "garage_json",  array(
            "type"     => "varchar",
            "backend"  => "",
            "label"    => "Garage JSON",
            "input"    => "text",
            "source"   => "",
            "visible"  => true,
            "required" => true,
            "default" => "",
            "frontend" => "",
            "unique"     => false,
            "note"       => ""

        ));


        $my_attribute = $customerSetup->getEavConfig()->getAttribute(\Magento\Customer\Model\Customer::ENTITY, 'garage_json');
        $used_in_forms[]="adminhtml_customer";
        $used_in_forms[]="adminhtml_checkout";
        $my_attribute
            ->setData("used_in_forms", $used_in_forms)
            ->setData("is_used_for_customer_segment", true)
            ->setData("is_system", 0)
            ->setData("is_user_defined", 1)
            ->setData("is_visible", 1)
            ->setData("sort_order", 100);
        $my_attribute->save();

        $installer->endSetup();

	    */
	}
}