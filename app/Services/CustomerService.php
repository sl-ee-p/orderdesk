<?php

namespace App\Services;

use App\Exceptions\DomainRuleViolation;
use App\Models\Customer;

class CustomerService
{
    /**
     * Customers with quotes or orders are kept, so business records never lose their owner.
     */
    public function delete(Customer $customer): void
    {
        if ($customer->quotes()->exists() || $customer->orders()->exists()) {
            throw DomainRuleViolation::customerHasDocuments();
        }

        $customer->delete();
    }
}
