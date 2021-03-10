<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Block\Customer\Widget;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Swarming\SubscribePro\Platform\Manager\Customer as PlatformCustomer;
use Swarming\SubscribePro\Platform\Tool\Oauth as PlatformOauthTool;

class Address extends Template
{
    /**
     * @var PlatformCustomer
     */
    private $platformCustomerManager;
    /**
     * @var CustomerSession
     */
    private $customerSession;
    /**
     * @var PlatformOauthTool
     */
    private $oauthTool;

    public function __construct(
        Context $context,
        PlatformCustomer $platformCustomer,
        CustomerSession $customerSession,
        PlatformOauthTool $oauth,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->platformCustomerManager = $platformCustomer;
        $this->customerSession = $customerSession;
        $this->oauthTool = $oauth;
    }

    public function isAddressBookEnabled(): bool
    {
        $store = $this->_storeManager->getStore();
        $path = 'swarming_subscribepro/advanced/enable_hosted_address_book_widget';

        return $this->_scopeConfig->isSetFlag(
            $path,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getAddressWidgetUrl()
    {
        $store = $this->_storeManager->getStore();
        $path = 'swarming_subscribepro/advanced/hosted_address_book_widget_url';
        return $this->_scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getAddressWidgetJson()
    {
        $store = $this->_storeManager->getStore();
        $path = 'swarming_subscribepro/advanced/custom_json_hosted_address_book_widget';
        return $this->_scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getPlatformCustomerId()
    {
        try {
            return $this->platformCustomerManager->getCustomerById(
                $this->getCustomerId(),
                false,
                $this->customerSession->getCustomer()->getWebsiteId()
            )->getId();
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * @return array
     */
    public function getWidgetAccessTokenForCurrentCustomer(): string
    {
        $accessToken = $this->oauthTool->getWidgetAccessTokenByCustomerId($this->getPlatformCustomerId());
        return $accessToken['access_token'];
    }

    public function getCustomerId(): int
    {
        return (int) $this->customerSession->getCustomerId();
    }
}
