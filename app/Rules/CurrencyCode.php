<?php

namespace App\Rules;

use Money\Currency;
use Money\Currencies\ISOCurrencies;
use Illuminate\Contracts\Validation\Rule;

class CurrencyCode implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $valid_currencies = new ISOCurrencies();

        return $valid_currencies->contains(new Currency($value));
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be a valid currency code.';
    }
}
