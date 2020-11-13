<?php

namespace Swarming\SubscribePro\Model\ApplePay;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Directory\Helper\Data;
use Magento\Directory\Model\Region;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\Response;
use Magento\Customer\Model\Address;
use Psr\Log\LoggerInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use SubscribePro\Service\Token\TokenInterface;
use Swarming\SubscribePro\Api\ApplePayInterface;
use Swarming\SubscribePro\Platform\Manager\ApplePay as PlatformApplePayManager;
use Swarming\SubscribePro\Platform\Manager\Customer as PlatformCustomerManager;

class ApplePay implements ApplePayInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var JsonFactory
     */
    protected $jsonResultFactory;

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @var Data
     */
    protected $directoryDataHelper;

    /**
     * @var Region
     */
    protected $regionModel;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var AddressFactory
     */
    protected $addressFactory;

    /**
     * @var PlatformApplePayManager
     */
    protected $applePayManager;

    /**
     * @var PlatformCustomerManager
     */
    protected $platformCustomerManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param RequestInterface $request
     * @param JsonFactory $jsonResultFactory
     * @param Json $jsonSerializer
     * @param Data $directoryDataHelper
     * @param Region $regionModel
     * @param CheckoutSession $checkoutSession
     * @param Session $customerSession
     * @param AddressFactory $addressFactory
     * @param PlatformApplePayManager $applePayManager
     * @param PlatformCustomerManager $platformCustomerManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestInterface $request,
        JsonFactory $jsonResultFactory,
        Json $jsonSerializer,
        Data $directoryDataHelper,
        Region $regionModel,
        CheckoutSession $checkoutSession,
        Session $customerSession,
        AddressFactory $addressFactory,
        PlatformApplePayManager $applePayManager,
        PlatformCustomerManager $platformCustomerManager,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->directoryDataHelper = $directoryDataHelper;
        $this->regionModel = $regionModel;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->addressFactory = $addressFactory;
        $this->applePayManager = $applePayManager;
        $this->platformCustomerManager = $platformCustomerManager;
        $this->logger = $logger;
    }

    /**
     * @return string
     */
    public function onPaymentAuthorized()
    {
        $this->logger->info('OnPaymentAuthorized controller.');
        $requestBody = $this->jsonSerializer->unserialize($this->request->getContent());

        // Make sure we have payment and customer data in the request
        $token = isset($requestBody['token']) ? $requestBody['token'] : null;
        $shippingContact = isset($requestBody['shippingContact']) ? $requestBody['shippingContact'] : null;
        if (!$token || empty($token['paymentData']) || !is_array($token['paymentData'])) {
            return $this->getResult(__('Missing ApplePay payment data in request body'), Exception::HTTP_BAD_REQUEST);
        } elseif (empty($shippingContact['emailAddress']) || empty($shippingContact['givenName']) || empty($shippingContact['familyName'])) {
            return $this->getResult(__('Missing customer information in ApplePay payment data'), Exception::HTTP_BAD_REQUEST);
        } elseif (empty($requestBody['billingContact'])) {
            return $this->getResult(__('Missing billing contact information in ApplePay payment data'), Exception::HTTP_BAD_REQUEST);
        }


        $magentoCustomer = $this->customerSession->getCustomer();
        $quote = $this->checkoutSession->getQuote();

        // Get billing address details to save with payment profile
        $magentoAddress = $this->convertAddressToMagentoAddress($requestBody['billingContact']);
        $magentoAddress->setCustomer($magentoCustomer);
//        $quoteAddress = $quote->getBillingAddress()->importCustomerAddressData($magentoAddress->getDataModel());

        if ($this->customerSession->isLoggedIn()) {
            // Look up or create customer in SP
            $spCustomer = $this->platformCustomerManager->getCustomer($magentoCustomer->getEmail(), true);

            if (empty($spCustomer)) {
                return null;
            }

            $paymentProfile = $this->createPaymentProfile($spCustomer->getId(), $magentoCustomer, $magentoAddress, $requestBody['token']['paymentData']);
            return $this->getResult($paymentProfile->getId());
        } else {
            $paymentToken = $this->createPaymentToken($magentoAddress, $requestBody['token']['paymentData']);
            return $this->getResult($paymentToken->getId());
        }
    }

    /**
     * @param int $platformCustomerId
     * @param Customer $magentoCustomer
     * @param Address $quoteAddress
     * @param array $applePayPayment
     * @return PaymentProfileInterface
     */
    protected function createPaymentProfile($platformCustomerId, Customer $magentoCustomer, Address $quoteAddress, array $applePayPayment)
    {
        return $this->applePayManager->createApplePayProfile($platformCustomerId, $magentoCustomer, $quoteAddress, $applePayPayment);
    }

    /**
     * @param Address $quoteAddress
     * @param array $applePayPayment
     * @return TokenInterface
     */
    protected function createPaymentToken(Address $quoteAddress, array $applePayPayment)
    {
        return $this->applePayManager->createApplePayToken($quoteAddress, $applePayPayment);
    }

    /**
     * @param array $address
     * @return Address
     */
    protected function convertAddressToMagentoAddress(array $address)
    {
        $countryId = strtoupper($address['countryCode']);
        $countryName = $address['country'];
        if (!empty($countryId)) {
            $countryCollection = $this->directoryDataHelper->getCountryCollection();
            foreach ($countryCollection as $country) {
                if ($countryName == $country->getName()) {
                    $countryId = strtoupper($country->getCountryId());
                    break;
                }
            }
        }

        $magentoAddress = $this->addressFactory->create();

        $magentoAddress->setFirstname($address['givenName']);
        $magentoAddress->setLastname($address['familyName']);
        $magentoAddress->setStreet($address['addressLines']);
        $magentoAddress->setCity($address['locality']);
        $magentoAddress->setCountryId($countryId);
        $magentoAddress->setPostcode($address['postalCode']);
        $magentoAddress->setTelephone(isset($address['phoneNumber']) ? $address['phoneNumber'] : '0000000000');

        if ($this->directoryDataHelper->isRegionRequired($countryId) && isset($address['administrativeArea'])) {
            $region = $this->regionModel->loadByCode($address['administrativeArea'], $countryId) ?:
                $this->regionModel->loadByName($address['administrativeArea'], $countryId);
            if ($region) {
                $magentoAddress->setRegionId($region->getId());
                $magentoAddress->setRegion($region->getName());
            }
        }

        return $magentoAddress;
    }

    /**
     * @param string $message
     * @param int $code
     * @return bool|string
     */
    protected function getResult($message, $code = Response::HTTP_OK)
    {
        return $this->jsonSerializer->serialize([
            'message' => $message,
            'status' => $code,
        ]);
    }
}
