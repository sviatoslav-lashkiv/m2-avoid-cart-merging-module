<?php
/**
 * Copyright (c) 2019. All rights reserved.
 * @author: Sviatoslav Lashkiv
 * @mail:   ss.lashkiv@gmail.com
 * @github: https://github.com/sviatoslav-lashkiv
 */

namespace MageCloud\AvoidCartMerging\Model\Source;

class AllowedPages implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'customer_account_login',
                'label' => __('Customer Login (Default)')
            ],
            [
                'value' => 'customer_ajax_login',
                'label' => __('Checkout Login (Ajax)')
            ]
        ];
    }
}