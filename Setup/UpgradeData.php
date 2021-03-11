<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    const PLATFORM_ADDRESS_ID_FIELD = 'platform_address_id';

    /**
     * @var \Magento\Customer\Setup\CustomerSetupFactory
     */
    private $customerSetupFactory;

    public function __construct(
        \Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);

        if (version_compare($context->getVersion(), '1.3.8') < 0) {
            $customerSetup->addAttribute('customer_address', self::PLATFORM_ADDRESS_ID_FIELD, [
                'label' => 'Platform Address ID',
                'input' => 'varchar',
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'source' => '',
                'required' => false,
                'position' => 150,
                'visible' => true,
                'system' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_searchable_in_grid' => false,
                'frontend_input' => 'hidden',
                'backend' => ''
            ]);

            $attribute=$customerSetup->getEavConfig()
                ->getAttribute('customer_address', self::PLATFORM_ADDRESS_ID_FIELD)
                ->addData(['used_in_forms' => [
                    'adminhtml_customer_address',
                    'customer_address_edit',
                    'customer_address',
                ]
                ]);

            $attribute->save();
        }
    }
}
