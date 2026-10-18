<?php

namespace App\Services;

use App\Exceptions\DomainRuleViolation;
use App\Models\Customer;
use Illuminate\Database\QueryException;

class CustomerService
{
    /**
     * Customers with quotes or orders are kept, so business records never lose their owner.
     * The restricting foreign keys are the final guard if a document is created concurrently.
     */
    public function delete(Customer $customer): void
    {
        if ($customer->quotes()->exists() || $customer->orders()->exists()) {
            throw DomainRuleViolation::customerHasDocuments();
        }

        try {
            $customer->delete();
        } catch (QueryException $e) {
            // SQLSTATE class 23 = integrity constraint violation (here: a restricting foreign key).
            if (str_starts_with((string) $e->getCode(), '23')) {
                throw DomainRuleViolation::customerHasDocuments();
            }

            throw $e;
        }
    }
}
