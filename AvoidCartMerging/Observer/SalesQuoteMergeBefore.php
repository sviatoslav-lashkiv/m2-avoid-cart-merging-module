<?php
/**
 * Copyright (c) 2019. All rights reserved.
 * @author: Sviatoslav Lashkiv
 * @mail:   ss.lashkiv@gmail.com
 * @github: https://github.com/sviatoslav-lashkiv
 */

namespace MageCloud\AvoidCartMerging\Observer;

class SalesQuoteMergeBefore implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * SalesQuoteMergeBefore constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\RequestInterface $request,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->logger = $logger;
    }

    /**
     * Current controller name
     *
     * @return bool|string
     */
    protected function getControllerName()
    {
        try {
            $moduleName     = $this->request->getModuleName();       // customer
            $controllerName = $this->request->getControllerName();   // ajax
            $actionName     = $this->request->getActionName();       // login

            if(empty($moduleName) || empty($controllerName) || empty($actionName)) return false;

        } catch (\Exception $e)  {
            $this->logger->error($e->getMessage());
        }

        return "{$moduleName}_{$controllerName}_{$actionName}";
    }

    /**
     * Avoid shopping carts merging on customer login
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            // Admin -> Stores -> Configurations -> Sales -> Checkout -> Shopping Cart -> Avoid merging cart after login
            $moduleEnabled = $this->scopeConfig->isSetFlag(
                'checkout/cart/avoid_cart_merging_status',
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES
            );

            // Admin -> Stores -> Configurations -> Sales -> Checkout -> Shopping Cart -> Allowed login pages to skip cart merging
            $allowedPages = explode(',', $this->scopeConfig->getValue(
                'checkout/cart/avoid_cart_merging_allowed_pages',
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES
            ));


            // Checking if the module is enabled and the allowed pages are selected.
            if ($moduleEnabled && isset($allowedPages) && count($allowedPages) > 0) {

                $controllerName = $this->getControllerName();

                // Checking if current controller name exist in the allowed pages list
                if (!empty($controllerName) && in_array($this->getControllerName(), $allowedPages)) {

                    // Checking if customer has items in previous cart quote
                    if ($observer->getSource()->hasItems() && is_object($observer->getQuote()) && $observer->getQuote()->hasItems()) {
                        $observer->getQuote()->removeAllItems();
                    }

                }
            }

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
