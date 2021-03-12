<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Block\Customer\Widget;

class Delete extends Address
{
    public function getDeleteUrl(): string
    {
        return $this->getUrl('customer/address/delete');
    }
}
