<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Block\Customer\Widget;

use Magento\Framework\Exception\LocalizedException;
use Swarming\SubscribePro\Setup\UpgradeData;

class Address extends AbstractAddress
{
    public function getCurrentAddress(): array
    {
        $addressId = $this->getRequest()->getParam('id');
        if (!$addressId) {
            return [];
        }
        try {
            $address = $this->addressRepository->getById($addressId);
            $result =  [
                'firstname' => $address->getFirstname(),
                'lastname' => $address->getLastname(),
                'company' => $address->getCompany(),
                'street1' => (($address->getStreet()[0]) ?? ''),
                'street2' => (($address->getStreet()[1]) ?? ''),
                'city' => $address->getCity(),
                'region' => $address->getRegion()->getRegionCode(),
                'region_id' => $address->getRegionId(),
                'postcode' => $address->getPostcode(),
                'country' => $address->getCountryId(),
                'phone' => $address->getTelephone()
            ];
            $platformAddressIdData = $address->getCustomAttribute(UpgradeData::PLATFORM_ADDRESS_ID_FIELD);
            if ($platformAddressIdData) {
                $result['id'] = (int) $platformAddressIdData->getValue();
            }

            return $result;
        } catch (LocalizedException $e) {
            $this->_logger->critical($e->getMessage());
            return [];
        }
    }

    public function getCustomerDefaultBillingAddress(): array
    {
        $addressId = $this->getRequest()->getParam('id');
        if (!$addressId) {
            return [];
        }
        try {
            $customer = $this->customerSession->getCustomer();
            $defaultBillingAddress = $customer->getDefaultBillingAddress();
            $result =  [
                'firstname' => $defaultBillingAddress->getFirstname(),
                'lastname' => $defaultBillingAddress->getLastname(),
                'company' => $defaultBillingAddress->getCompany(),
                'street1' => (($defaultBillingAddress->getStreet()[0]) ?? ''),
                'street2' => (($defaultBillingAddress->getStreet()[1]) ?? ''),
                'city' => $defaultBillingAddress->getCity(),
                'region' => $defaultBillingAddress->getRegion(),
                'region_id' => $defaultBillingAddress->getRegionCode(),
                'postcode' => $defaultBillingAddress->getPostcode(),
                'country' => $defaultBillingAddress->getCountryId(),
                'phone' => $defaultBillingAddress->getTelephone()
            ];
            $platformAddressId = $defaultBillingAddress->getData(UpgradeData::PLATFORM_ADDRESS_ID_FIELD);
            if ($platformAddressId) {
                $result['id'] = (int) $platformAddressId;
            }

            return $result;
        } catch (LocalizedException $e) {
            $this->_logger->critical($e->getMessage());
            return [];
        }
    }
}
