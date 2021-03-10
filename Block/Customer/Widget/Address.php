<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Block\Customer\Widget;

use Magento\Framework\Exception\LocalizedException;

class Address extends AbstractAddress
{
    public function getCurrentAddress(): array
    {
        $addressId = $this->getRequest()->getParam('id');
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

            return $result;
        } catch (LocalizedException $e) {
            $this->_logger->critical($e->getMessage());
            return [];
        }
    }
}
