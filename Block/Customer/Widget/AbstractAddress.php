<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Block\Customer\Widget;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Swarming\SubscribePro\Platform\Manager\Customer as PlatformCustomer;
use Swarming\SubscribePro\Platform\Tool\Oauth as PlatformOauthTool;

abstract class AbstractAddress extends Template
{
    /**
     * @var PlatformCustomer
     */
    protected $platformCustomerManager;
    /**
     * @var CustomerSession
     */
    protected $customerSession;
    /**
     * @var PlatformOauthTool
     */
    protected $oauthTool;
    /**
     * @var AddressRepositoryInterface
     */
    protected $addressRepository;

    public function __construct(
        Context $context,
        PlatformCustomer $platformCustomer,
        CustomerSession $customerSession,
        PlatformOauthTool $oauth,
        AddressRepositoryInterface $addressRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->platformCustomerManager = $platformCustomer;
        $this->customerSession = $customerSession;
        $this->oauthTool = $oauth;
        $this->addressRepository = $addressRepository;
    }

    /**
     * @return bool
     */
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

    /**
     * @return string
     */
    public function getAddressWidgetUrl(): string
    {
        $store = $this->_storeManager->getStore();
        $path = 'swarming_subscribepro/advanced/hosted_address_book_widget_url';
        $value = $this->_scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return ($value)?? '';
    }

    /**
     * @return string
     */
    public function getAddressWidgetJson(): string
    {
        $store = $this->_storeManager->getStore();
        $path = 'swarming_subscribepro/advanced/custom_json_hosted_address_book_widget';
        $value = $this->_scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return ($value)?? '';
    }

    /**
     * @return int|null
     */
    public function getPlatformCustomerId(): ?int
    {
        try {
            $customerId = $this->getCustomerId();
            if (!$customerId) {
                return null;
            }
            return $this->platformCustomerManager->getCustomerById(
                $customerId,
                false,
                $this->customerSession->getCustomer()->getWebsiteId()
            )->getId();
        } catch (NoSuchEntityException $e) {
            $this->_logger->critical($e->getMessage());
            return null;
        }
    }

    /**
     * @return string
     */
    public function getWidgetAccessTokenForCurrentCustomer(): string
    {
        $platformCustomerId = $this->getPlatformCustomerId();
        if (!$platformCustomerId) {
            return '';
        }
        $accessToken = $this->oauthTool->getWidgetAccessTokenByCustomerId($this->getPlatformCustomerId());

        return $accessToken['access_token'];
    }

    /**
     * @return int
     */
    public function getCustomerId(): int
    {
        return (int) $this->customerSession->getCustomerId();
    }
}
