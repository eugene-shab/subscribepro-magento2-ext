<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Plugin\Customer\Address;

use Magento\Customer\Block\Address\Grid as AddressGrid;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use \Magento\Customer\Model\ResourceModel\Address\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Model\ResourceModel\Address\Collection;
use Psr\Log\LoggerInterface;

class GridBlock
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var CollectionFactory
     */
    private $addressCollectionFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Address\Collection
     */
    protected $addressCollection;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $addressCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->addressCollectionFactory = $addressCollectionFactory;
    }

    public function aroundGetAdditionalAddresses(AddressGrid $object, callable $proceed): array
    {
        $isAddressBookEnabled = $this->isAddressBookEnabled();
        if (!$isAddressBookEnabled) {
            return $proceed();
        }

        $additional = [];
        $customer = $object->getCustomer();
        $defaultBilling = (int) $customer->getDefaultBilling();
        $defaultShipping = (int)$customer->getDefaultShipping();

        $addresses = $this->getAddressCollection($customer, $defaultBilling, $defaultShipping);
        $primaryAddressIds = [$defaultBilling, $defaultShipping];
        foreach ($addresses as $address) {
            if (!in_array((int)$address->getId(), $primaryAddressIds, true)) {
                $additional[] = $address->getDataModel();
            }
        }

        return $additional;
    }

    private function getAddressCollection($customer, $defaultBilling, $defaultShipping): Collection
    {
        if (null === $this->addressCollection) {
            if (null === $customer) {
                throw new NoSuchEntityException(__('Customer not logged in'));
            }
            /** @var \Magento\Customer\Model\ResourceModel\Address\Collection $collection */
            $collection = $this->addressCollectionFactory->create();
            $collection->addFieldToSelect('platform_address_id');
            $collection->setOrder('entity_id', 'desc');
            $collection->addFieldToFilter(
                'entity_id',
                ['nin' => [$defaultBilling, $defaultShipping]]
            );
            $collection->setCustomerFilter([$customer->getId()]);
            $this->addressCollection = $collection;
        }

        return $this->addressCollection;
    }

    private function isAddressBookEnabled(): bool
    {
        $path = 'swarming_subscribepro/advanced/enable_hosted_address_book_widget';

        return $this->scopeConfig->isSetFlag(
            $path,
            ScopeInterface::SCOPE_STORE,
        );
    }
}
