<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Model\ApplePay;

use Magento\Checkout\Helper\Data as CheckoutHelperData;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Method\Logger as PaymentLogger;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\BillingAddressManagement;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\ShippingAddressManagement;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Psr\Log\LoggerInterface;
use Swarming\SubscribePro\Helper\Customer as CustomerHelper;
use Swarming\SubscribePro\Model\Quote\SubscriptionCreator;
use Swarming\SubscribePro\Platform\Manager\Product;
use Swarming\SubscribePro\Platform\Service\Subscription;

class OrderService
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;
    /**
     * @var QuoteManagement
     */
    private $quoteManagement;
    /**
     * @var AddressFactory
     */
    private $addressFactory;
    /**
     * @var ShippingAddressManagement
     */
    private $shippingAddressManagement;
    /**
     * @var BillingAddressManagement
     */
    private $billingAddressManagement;
    /**
     * @var PaymentLogger
     */
    private $paymentLogger;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var CheckoutHelperData
     */
    private $checkoutHelperData;
    /**
     * @var OrderSender
     */
    private $orderSender;
    /**
     * @var LoggerInterface
     */
    private $logger;
    private $subscriptionCreator;
    private $customerHelper;
    private $subscriptionPlatform;
    private $productPlatform;
    private $eventManager;
    private $itemRepository;

    public function __construct(
        CartRepositoryInterface $quoteRepository,
        QuoteManagement $quoteManagement,
        CheckoutSession $checkoutSession,
        AddressFactory $addressFactory,
        ShippingAddressManagement $shippingAddressManagement,
        BillingAddressManagement $billingAddressManagement,
        PaymentLogger $paymentLogger,
        CheckoutHelperData $checkoutHelperData,
        OrderSender $orderSender,
        LoggerInterface $logger,
        SubscriptionCreator $subscriptionCreator,
        CustomerHelper $customerHelper,
        Subscription $subscriptionPlatform,
        Product $productPlatform,
        ManagerInterface $eventManager,
        \Magento\Sales\Model\Order\ItemRepository $itemRepository
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteManagement = $quoteManagement;
        $this->addressFactory = $addressFactory;
        $this->shippingAddressManagement = $shippingAddressManagement;
        $this->billingAddressManagement = $billingAddressManagement;
        $this->paymentLogger = $paymentLogger;
        $this->checkoutSession = $checkoutSession;
        $this->checkoutHelperData = $checkoutHelperData;
        $this->orderSender = $orderSender;
        $this->logger = $logger;
        $this->subscriptionCreator = $subscriptionCreator;
        $this->customerHelper = $customerHelper;
        $this->subscriptionPlatform = $subscriptionPlatform;
        $this->productPlatform = $productPlatform;
        $this->eventManager = $eventManager;
        $this->itemRepository = $itemRepository;
    }

    public function createOrder($quoteId): bool
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $observer = $objectManager->create('Magento\Vault\Observer\AfterPaymentSaveObserver');
        /** @var Quote $quote */
        $quote = $this->quoteRepository->get($quoteId);

        if (!$quote || !$quote->getIsActive()) {
            throw  new LocalizedException(__('Something going wrong with display_id'));
        }

        $shippingAddress = $quote->getShippingAddress();

        if (!$shippingAddress->getShippingMethod()) {
            /*
             * case when only one shipping_method available the apple pay does not trigger an event
             * with "onshippingmethodselected".
             */
            // TODO: need to set shipping_method if only one available or throw error if it more than one methods.
//            $quoteId = $quote->getId();
//            $storeId = $quote->getStoreId();
        }

        $quote->collectTotals();
        $this->quoteRepository->save($quote);

        try {
            /** @var Order $order */
            $order = $this->quoteManagement->submit($quote);
            /* TODO TEST */
//            $payment = $order->getPayment();
//            $extensionAttributes = $payment->getExtensionAttributes();
//
//            $paymentToken = $observer->getPaymentToken($extensionAttributes);
//
//            $test = 1;
            /* END TEST */
            //$this->subscriptionCreator->createSubscriptions($quote, $order);

            // TODO: need to check redirect url if success page was changed by 3rd party module.
//            $redirectUrl = $quote->getPayment()->getOrderPlaceRedirectUrl();
//            if (!$redirectUrl) {
//                $redirectUrl = $this->defaultConfigProvider->getDefaultSuccessPageUrl();
//            }

            $this->checkoutSession
                ->setLastQuoteId($quote->getId())
                ->setLastSuccessQuoteId($quote->getId())
                ->setLastOrderId($order->getId())
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastOrderStatus($order->getStatus());

            return true;
        } catch (LocalizedException $e) {
            $this->checkoutHelperData->sendPaymentFailedEmail(
                $quote,
                $e->getMessage()
            );

            throw $e;
        }
    }

    /**
     * @param Quote $quote
     * @param \Magento\Sales\Model\Order $order
     */
    public function createSubscriptions($quote, $order)
    {
//        $apiServer = Mage::getSingleton('api/server');
        /** @var Mage_Api_Model_Session $apiSession */
//        $apiSession = Mage::getSingleton('api/session');
//        if ($apiSession->isLoggedIn() || strlen($apiServer->getApiName())) {
//            return array();
//        }

//        /** @var SubscribePro_Autoship_Helper_Platform_Customer $customerHelper */
//        $customerHelper = Mage::helper('autoship/platform_customer');
        /** @var SubscribePro_Autoship_Helper_Api $apiHelper */
//        $apiHelper = Mage::helper('autoship/api');

        // Set store on api helper
//        $apiHelper->setConfigStore($quote->getStore());

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        // Find customer and payment details
        $spCustomerId = $this->customerHelper->fetchSubscribeProCustomerId($quote->getCustomer());

//        if (!strlen($spCustomerId)) {
        // Create customer because didn't exist
//            $platformCustomer = $customerHelper->createOrUpdatePlatformCustomer($quote->getCustomer());
//            $spCustomerId = $platformCustomer->getId();
//        }

        // Maintain failed subscription count in session
//        Mage::getSingleton('checkout/session')->setData('failed_subscription_count', 0);

        // Keep track of subscriptions created
//        $subscriptions = [];

        // Go through quote and get list of all items with their shipping addresses

        /** TODO relevant items **/
        $quoteItems = $quote->getAllVisibleItems();
        $shippingAddress = $quote->getShippingAddress();
        // Iterate items
        foreach ($quoteItems as $quoteItemDetails) {
            $subscription = $this->checkAndCreateSubscriptionAndUpdateQuoteItem($spCustomerId, $order, $quoteItemDetails, $shippingAddress);
            if ($subscription != null) {
                $subscriptions[] = $subscription;
            }
        }

        // Return array of created subscriptions
//        return $subscriptions;
    }

    private function checkAndCreateSubscriptionAndUpdateQuoteItem($spCustomerId, $order, $quoteItem, $shippingAddress)
    {
        $interval = $quoteItem->getBuyRequest()->getSubscriptionOption()['interval'];
        $platformProduct = $this->productPlatform->getProduct($quoteItem->getSku());
        $interval = strlen($quoteItem->getBuyRequest()->getSubscriptionOption()['interval'])
            ? $quoteItem->getBuyRequest()->getSubscriptionOption()['interval']
            : $platformProduct->getDefaultInterval();
        $subscription = $this->createSubscriptionAndUpdateQuoteItem($spCustomerId, $order, $quoteItem, $shippingAddress, $interval);

        /** TODO VALIDATION **/
//        /** @var SubscribePro_Autoship_Helper_Platform_Product $platformProductHelper */
//        $platformProductHelper = Mage::helper('autoship/platform_product');
//        /** @var SubscribePro_Autoship_Helper_Product $productHelper */
//        $productHelper = Mage::helper('autoship/product');

//        // Get subscription product profile
//        $product = $this->getRelevantProductFromQuoteItem($quoteItem);
//        // Check if product is enabled for subscription
//        // Check product profile, if this isn't a subscription product, ignore it
//        if ($productHelper->isAvailableForSubscription($product, $quoteItem->getQuote()->getStore())) {
//            // Retrieve product details from SP
//            $platformProduct = $platformProductHelper->getPlatformProduct($product);
//            // Check quote item flag which indicates we should create a new subscription for this product
//            if ($quoteItem->getData('create_new_subscription_at_checkout')
//                || $platformProduct->getSubscriptionOptionMode() == 'subscription_only'
//                || $platformProduct->getDefaultSubscriptionOption() == 'subscription')
//            {
//                $interval = strlen($quoteItem->getData('new_subscription_interval')) ? $quoteItem->getData('new_subscription_interval') : $platformProduct->getDefaultInterval();
//                // For virtual products, set billing address as shipping address
//                $subscription = $this->createSubscriptionAndUpdateQuoteItem($spCustomerId, $order, $quoteItem, $shippingAddress, $interval);
//
//                return $subscription;
//            }
//        }
//
//        return null;
    }

    private function createSubscriptionAndUpdateQuoteItem($spCustomerId, $order, $quoteItem, $shippingAddress, $interval)
    {
        try {
            $subscription = $this->createSubscriptionFromQuoteItem($spCustomerId, $order, $quoteItem, $shippingAddress, $interval);
            $this->eventManager->dispatch(
                'subscribepro_autoship_before_create_subscription_from_quote_item',
                ['subscription' => $subscription, 'quote_item' => $quoteItem]
            );

            $this->subscriptionPlatform->saveSubscription($subscription);
//            // Save in array
            $subscriptions[$subscription->getId()] = $subscription;
//            // Save subscription id and flag on quote item
            $quoteItem->setData('subscription_id', $subscription->getId());
            $quoteItem->setData('subscription_interval', $subscription->getInterval());
            $quoteItem->setData('item_fulfils_subscription', true);
            $quoteItem->save();
//            // Lookup order item
            $orderItem = $this->itemRepository->get($quoteItem->getId());
            //$orderItem = Mage::getModel('sales/order_item')->load($quoteItem->getId(), 'quote_item_id');
            // Save subscription id and flag on order item
            if (strlen($orderItem->getId())) {
                $orderItem->setData('subscription_id', $subscription->getId());
                $orderItem->setData('subscription_interval', $subscription->getInterval());
                $orderItem->setData('item_fulfils_subscription', true);
                $this->addAdditionalOptionsToOrderItem($orderItem);
                $orderItem->save();
            }

            $this->eventManager->dispatch(
                'subscribepro_autoship_after_create_subscription_from_quote_item',
                ['subscription' => $subscription, 'quote_item' => $quoteItem]
            );

            return $subscription;
        } catch (\Exception $e) {
            var_dump($e);
        }

        return null;
    }

    public function addAdditionalOptionsToOrderItem($orderItem)
    {
        if ($orderItem->getData('item_fulfils_subscription')) {
            // Get options
            $options = $orderItem->getProductOptions();
            // Get existing additional_options
            if (isset($options['additional_options']) && is_array($options['additional_options'])) {
                $additionalOptions = $options['additional_options'];
            } else {
                $additionalOptions = [];
            }
            // Add our details
            $additionalOptions[] = [
                'label' => __('Product Subscription Id'),
                'value' => $orderItem->getData('subscription_id'),
            ];
            $additionalOptions[] = [
                'label' => __('Subscription Interval'),
                'value' => $orderItem->getData('subscription_interval'),
            ];
            // Set new additional_options on order item
            $options['additional_options'] = $additionalOptions;
            $orderItem->setProductOptions($options);
        }
    }

    protected function createSubscriptionFromQuoteItem($spCustomerId, $order, $quoteItem, $shippingAddress, $interval)
    {
        //$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        //$subscriptionService = $objectManager->create('SubscribePro\Service\Subscription\SubscriptionService');

        //$subscriptionHelper = Mage::helper('autoship/platform_subscription');

        //$paymentHelper = Mage::helper('autoship/payment');
        // Get quote
        $quote = $quoteItem->getQuote();
        // Lookup which product is "relevant" to create subscription
        $product = $quoteItem->getProduct();

        // Empty subscription object
        $subscription = $this->subscriptionPlatform->createSubscription();
        // Customer
        $subscription->setCustomerId($spCustomerId);
        // Send notification
        $subscription->setSendCustomerNotificationEmail(true);
        // Payment
        $paymentMethodCode = $quote->getPayment()->getMethod();
//        if ($paymentHelper->isAnySubscribeProPayMethod($paymentMethodCode)) {
//            $spPaymentProfileId = $order->getPayment()->getAdditionalInformation('payment_profile_id');
//            if (!strlen($spPaymentProfileId)) {
//                Mage::throwException('Failed to find Subscribe Pro payment profile ID!');
//            }
//            $subscription->setPaymentProfileId($spPaymentProfileId);
//        }
//        else {
        $subscription->setPaymentMethodCode($quote->getPayment()->getMethod());
        //}
        // Product
        $subscription->setProductSku($product->getSku());
        $subscription->setQty($quoteItem->getQty());
        // Save coupon code on subscription, if config setting enabled
//        if(Mage::getStoreConfig('autoship_subscription/options/allow_coupon', $quote->getStore()) == 1) {
//            $subscription->setCouponCode($quote->getCouponCode());
//        }
        $subscription->setUseFixedPrice(false);
        // Schedule
        $subscription->setFirstOrderAlreadyCreated(true);
        $subscription->setNextOrderDate(date('Y-m-d'));
        $subscription->setInterval($interval);
        // Magento specific
        $subscription->setMagentoStoreCode($quote->getStore()->getCode());
//        $subscription->setPlatformSpecificFields(array(
//            'magento1' => array(
//                'magento_website_id' => $quote->getStore()->getWebsiteId(),
//                'magento_store_code' => $quote->getStore()->getCode(),
//                'magento_product_options' => $this->getProductOptionsFromQuoteItem($quoteItem),
//                'magento_order_details' => $this->getOrderDetails($order),
//            ),
//        ));
        // Shipping
//        if ($shippingAddress instanceof Mage_Sales_Model_Quote_Address) {
//            $subscription->setRequiresShipping(true);
//            $subscription->setMagentoShippingMethodCode($shippingAddress->getData('shipping_method'));
//            // Shipping Address
//            $subscription->getShippingAddress()->setFirstName($shippingAddress->getFirstname());
//            $subscription->getShippingAddress()->setLastName($shippingAddress->getLastname());
//            $subscription->getShippingAddress()->setCompany($shippingAddress->getCompany());
//            $subscription->getShippingAddress()->setStreet1($shippingAddress->getStreet1());
//            $subscription->getShippingAddress()->setStreet2($shippingAddress->getStreet2());
//            $subscription->getShippingAddress()->setCity($shippingAddress->getCity());
//            $subscription->getShippingAddress()->setRegion($shippingAddress->getRegionCode());
//            $subscription->getShippingAddress()->setPostcode($shippingAddress->getPostcode());
//            $subscription->getShippingAddress()->setCountry($shippingAddress->getCountryId());
//            $subscription->getShippingAddress()->setPhone($shippingAddress->getTelephone());
//        }
//        else {
        $subscription->setRequiresShipping(false);
        $subscription->setShippingAddress(null);
        //  }

        // Return the new subscription model
        return $subscription;
    }
}
