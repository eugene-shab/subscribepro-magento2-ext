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
                'firstName' => $address->getFirstname(),
                'lastName' => $address->getLastname(),
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

    public function getCustomerDefaultAddressByType($addressType = 'billing'): array
    {
        $addressId = $this->getRequest()->getParam('id');
        if (!$addressId) {
            return [];
        }
        try {
            $customer = $this->customerSession->getCustomer();

            $defaultAddress = ($addressType === 'billing') ?
                $customer->getDefaultBillingAddress() : $customer->getDefaultShippingAddress();

            $result =  [
                'firstName' => $defaultAddress->getFirstname(),
                'lastName' => $defaultAddress->getLastname(),
                'company' => $defaultAddress->getCompany(),
                'street1' => (($defaultAddress->getStreet()[0]) ?? ''),
                'street2' => (($defaultAddress->getStreet()[1]) ?? ''),
                'city' => $defaultAddress->getCity(),
                'region' => $defaultAddress->getRegion(),
                'region_id' => $defaultAddress->getRegionCode(),
                'postcode' => $defaultAddress->getPostcode(),
                'country' => $defaultAddress->getCountryId(),
                'phone' => $defaultAddress->getTelephone()
            ];
            $platformAddressId = $defaultAddress->getData(UpgradeData::PLATFORM_ADDRESS_ID_FIELD);
            if ($platformAddressId) {
                $result['id'] = (int) $platformAddressId;
            }

            return $result;
        } catch (LocalizedException $e) {
            $this->_logger->critical($e->getMessage());
            return [];
        }
    }

    public function getAddressSaveSessionUrl(): string
    {
        return $this->getUrl('subscribepro/customer/addressSession');
    }
}
