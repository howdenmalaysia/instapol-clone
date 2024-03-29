<?php

namespace App\DataTransferObjects\Motor;

use Spatie\DataTransferObject\DataTransferObject;

class ExtraCover extends DataTransferObject
{
    /** @var bool|null */
    public $selected;

    /** @var bool|null */
    public $readonly;

    /** @var string */
    public $extra_cover_code;

    /** @var string */
    public $extra_cover_description;

    /** @var int|float */
    public $sum_insured;

    /** @var int|float */
    public $premium;

    /** @var int|null */
    public $cart_day;

    /** @var int|null */
    public $cart_amount;

    /** @var \App\DataTransferObjects\Motor\CartList[]|null */
    public $cart_list;

    /** @var \App\DataTransferObjects\Motor\OptionList|null */
    public $option_list;

    /** @var int|null */
    public $sequence;

    /** @var int|null */
    public $unit;
    
    /** @var string|null */
    public $plan_type;
}

class CartList extends DataTransferObject
{
    /** @var int */
    public $cart_day;

    /** @var array */
    public $cart_amount_list;
}

class OptionList extends DataTransferObject
{
    /** @var string */
    public $name;

    /** @var string|null */
    public $description;

    /** @var array */
    public $values;

    /** @var boolean */
    public $any_value;

    /** @var int|null */
    public $increment;
}