<?php

namespace Catgento\Redsys\Model;

/**
 * Class Currency
 * @package Catgento\Redsys\Model
 */
class Currency
{

    protected $currencies = [
        'AUD' => '036',
        'CAD' => '124',
        'USD' => '840',
        'GBP' => '826',
        'CHF' => '756',
        'JPY' => '392',
        'CNY' => '156',
        'EUR' => '978',
    ];

    /**
     * @param OrderInterface $order
     * @return string
     */
    public function getCurrency($order)
    {
        $orderCurrency = $order->getOrderCurrency()->getCurrencyCode();
        if (isset($this->currencies[$orderCurrency])) {
            return $this->currencies[$orderCurrency];
        }
        return ConfigInterface::REDSYS_DEFAULT_CURRENCY;
    }

    /**
     * @param OrderInterface $order
     * @return string
     */
    public function getCurrencyFromCode($currencyCode)
    {
        $currencyCode = array_search($currencyCode, $this->currencies);
        if (!$currencyCode) {
            $currencyCode = array_search(ConfigInterface::REDSYS_DEFAULT_CURRENCY, $this->currencies);
        }

        return $currencyCode;
    }

}
