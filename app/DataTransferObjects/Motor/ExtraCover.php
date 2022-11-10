<?php

namespace App\DataTransferObjects\Motor;

use Spatie\DataTransferObject\DataTransferObject;

class ExtraCover extends DataTransferObject
{
    /** @var bool */
    public $selected;

    /** @var bool */
    public $readonly;

    /** @var string */
    public $extra_cover_code;

    /** @var string */
    public $extra_cover_description;

    /** @var int|float */
    public $sum_insured;

    /** @var int|float */
    public $premium;

    /** @var \App\DataTransferObjects\Motor\CartList[]|null */
    public $cart_list;

    /** @var \App\DataTransferObjects\Motor\OptionList|null */
    public $option_list;

    /** @var int|null */
    public $sequence;

    /** @var int|null */
    public $unit;
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

    /** @var string */
    public $description;

    /** @var array */
    public $values;

    /** @var boolean */
    public $any_value;

    /** @var int|null */
    public $increment;
}