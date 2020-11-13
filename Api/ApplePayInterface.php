<?php

namespace Swarming\SubscribePro\Api;

interface ApplePayInterface
{
    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function onPaymentAuthorized();
}
