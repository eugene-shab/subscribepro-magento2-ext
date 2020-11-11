<?php

namespace Swarming\SubscribePro\Block\ApplePay;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Model\MethodInterface;
use Swarming\SubscribePro\Model\ApplePay\Auth;
use Swarming\SubscribePro\Model\Config\Platform as PlatformConfig;

abstract class AbstractButton extends Template
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var MethodInterface
     */
    protected $payment;

    /**
     * @var Auth
     */
    protected $auth;

    /**
     * @var PlatformConfig
     */
    protected $platformConfig;

    /**
     * Button constructor.
     * @param Context $context
     * @param Session $checkoutSession
     * @param MethodInterface $payment
     * @param Auth $auth
     * @param PlatformConfig $platformConfig
     * @param array $data
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        MethodInterface $payment,
        Auth $auth,
        PlatformConfig $platformConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->payment = $payment;
        $this->auth = $auth->get();
        $this->platformConfig = $platformConfig;
    }

    /**
     * @inheritdoc
     */
    protected function _toHtml(): string // @codingStandardsIgnoreLine
    {
        if ($this->isActive()) {
            return parent::_toHtml();
        }

        return '';
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->payment->isAvailable($this->checkoutSession->getQuote());
    }

    /**
     * Merchant name to display in popup
     *
     * @return string
     */
    public function getMerchantName(): string
    {
        return $this->auth->getDisplayName();
    }

    /**
     * Merchant domain name
     *
     * @return string
     */
    public function getMerchantDomainName(): string
    {
        return $this->auth->getMerchantDomainName();
    }

    /**
     * Subscribe Pro's API token
     *
     * @return string|null
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getClientToken()
    {
        return $this->auth->getClientToken();
    }

    /**
     * URL To success page
     *
     * @return string
     */
    public function getActionSuccess(): string
    {
        return $this->auth->getActionSuccess();
    }

    /**
     * Is customer logged in flag
     *
     * @return bool
     */
    public function isCustomerLoggedIn(): bool
    {
        return $this->auth->isLoggedIn();
    }

    /**
     * Cart grand total
     *
     * @return float|null
     */
    public function getAmount()
    {
        return $this->checkoutSession->getQuote()->getBaseGrandTotal();
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStorecode(): string
    {
        return $this->auth->getStoreCode();
    }

    public function getCreateSessionUrl()
    {
        return rtrim($this->platformConfig->getBaseUrl(), '/') . '/services/v2/vault/applepay/create-session.json';
    }
//
//    public function getOnShippingContactSelectedUrl()
//    {
//        return $this->getUrl('swarming_subscribepro/applepay/onshippingcontactselected');
//    }
//
//    public function getOnShippingMethodSelectedUrl()
//    {
//        return $this->getUrl('swarming_subscribepro/applepay/onshippingmethodselected');
//    }
//
//    public function getOnPaymentAuthorizedUrl()
//    {
//        return $this->getUrl('swarming_subscribepro/applepay/onpaymentauthorized');
//    }
}
