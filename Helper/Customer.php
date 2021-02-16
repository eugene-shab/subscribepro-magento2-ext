<?php

namespace Swarming\SubscribePro\Helper;

use Magento\Catalog\Helper\Image;
use Magento\Catalog\Helper\Product\ConfigurationPool;
use Magento\Catalog\Model\Product\Url;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Tax\Api\TaxCalculationInterface;
use SubscribePro\Service\Customer\CustomerInterface;
use Swarming\SubscribePro\Model\CatalogRule\InspectorInterface;
use Swarming\SubscribePro\Model\Subscription\OptionItemManager;

class Customer
{
    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Product
     */
    protected $platformProductManager;

    /**
     * @var OptionItemManager
     */
    protected $subscriptionOptionItemManager;

    /**
     * @var Url
     */
    protected $productUrlModel;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var ConfigurationPool
     */
    protected $productConfigurationPool;

    /**
     * @var TaxCalculationInterface
     */
    protected $taxCalculation;

    /**
     * @var InspectorInterface
     */
    protected $catalogRuleInspector;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    private $customerService;

    /**
     * @param \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager
     * @param OptionItemManager $subscriptionOptionItemManager
     * @param Url $productUrlModel
     * @param Image $imageHelper
     * @param ConfigurationPool $productConfigurationPool
     * @param TaxCalculationInterface $taxCalculation
     * @param InspectorInterface $catalogRuleInspector
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Swarming\SubscribePro\Platform\Manager\Customer $customerService
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager,
        OptionItemManager $subscriptionOptionItemManager,
        Url $productUrlModel,
        Image $imageHelper,
        ConfigurationPool $productConfigurationPool,
        TaxCalculationInterface $taxCalculation,
        InspectorInterface $catalogRuleInspector,
        PriceCurrencyInterface $priceCurrency,
        \Swarming\SubscribePro\Platform\Manager\Customer $customerService
    ) {
        $this->platformProductManager = $platformProductManager;
        $this->subscriptionOptionItemManager = $subscriptionOptionItemManager;
        $this->productUrlModel = $productUrlModel;
        $this->imageHelper = $imageHelper;
        $this->productConfigurationPool = $productConfigurationPool;
        $this->taxCalculation = $taxCalculation;
        $this->catalogRuleInspector = $catalogRuleInspector;
        $this->priceCurrency = $priceCurrency;
        $this->customerService = $customerService;
    }

    /**
     * @param $customer
     * @return null
     */
    public function fetchSubscribeProCustomerId($customer)
    {
//        if (strlen($customer->getData('subscribe_pro_customer_id'))) {
//            return $customer->getData('subscribe_pro_customer_id');
//        } else {
            $platformCustomers = $this->customerService->getCustomer($customer->getEmail());
            if (!empty($platformCustomers)) {
                return $platformCustomers->getId();
            } else {
                return null;
            }
//        }
    }
}
