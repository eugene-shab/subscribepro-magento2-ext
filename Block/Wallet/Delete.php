<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Block\Wallet;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Vault\Block\Customer\CreditCards;
use Magento\Vault\Model\CustomerTokenManagement;
use Swarming\SubscribePro\Platform\Manager\Customer as PlatformCustomer;
use Swarming\SubscribePro\Platform\Tool\Oauth as PlatformOauthTool;
use Magento\Customer\Model\Session as CustomerSession;

class Delete extends CreditCards
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
        CustomerTokenManagement $customerTokenManagement,
        PlatformCustomer $platformCustomer,
        CustomerSession $customerSession,
        PlatformOauthTool $oauth,
        array $data = []
    ) {
        parent::__construct($context, $customerTokenManagement, $data);
        $this->platformCustomerManager = $platformCustomer;
        $this->customerSession = $customerSession;
        $this->oauthTool = $oauth;
    }

    public function isWalletEnabled()
    {
        $store = $this->_storeManager->getStore();
        $path = 'swarming_subscribepro/advanced/enable_hosted_wallet_widget';

        return $this->_scopeConfig->isSetFlag(
            $path,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getWalletWidgetUrl()
    {
        $store = $this->_storeManager->getStore();
        $path = 'swarming_subscribepro/advanced/hosted_wallet_widget_url';
        return $this->_scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getWalletWidgetJson()
    {
        $store = $this->_storeManager->getStore();
        $path = 'swarming_subscribepro/advanced/custom_json_hosted_wallet_widget';
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
