<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Block\Customer\Widget;

class DefaultAddressChanged extends Address
{
    public function getChangedAddressData()
    {
        return ($this->customerSession->getSpAddressData())?? [];
    }
}
