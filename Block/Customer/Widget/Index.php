<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Block\Customer\Widget;

class Index extends Address
{
    public function getDeleteUrl(): string
    {
        return $this->getUrl('customer/address/delete');
    }

    public function getChangedAddressData()
    {
        return ($this->customerSession->getSpAddressData())?? [];
    }

    public function getRemoveAddressFromSessionUrl(): string
    {
        return $this->getUrl('subscribepro/customer/RemoveAddressFromSession');
    }
}
