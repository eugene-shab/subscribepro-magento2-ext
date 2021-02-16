<?php

namespace Swarming\SubscribePro\Controller\Customer;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Swarming\SubscribePro\Model\ApplePay\OrderService;
use Swarming\SubscribePro\Model\Config\General;

class Subscriptions extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var General
     */
    protected $generalConfig;

    private OrderService $orderService;

    /**
     * @param Context $context
     * @param General $generalConfig
     * @param OrderService $orderService
     */
    public function __construct(
        Context $context,
        General $generalConfig,
        OrderService $orderService
    ) {
        $this->generalConfig = $generalConfig;
        $this->orderService = $orderService;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
       /// $customer = $objectManager->create('Swarming\SubscribePro\Platform\Manager\Customer')->getCustomer('yevhen@toptal.com');
        $quote = $objectManager->create('Magento\Quote\Model\Quote')->load(38);
        $order = $objectManager->create('Magento\Sales\Model\Order')->load(85);
        $orderService = $this->orderService->createSubscriptions($quote, $order);
        echo "<pre>";
        var_dump('!!!!!');
        die('END');
        if (!$this->generalConfig->isEnabled()) {
            /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
            $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            return $resultForward->forward('noroute');
        }

        /** @var \Magento\Framework\View\Result\Page $page */
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->getConfig()->getTitle()->set(__('My Product Subscriptions'));

        return $page;
    }
}
