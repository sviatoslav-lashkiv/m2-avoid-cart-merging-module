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
            $this->logger->info('check SalesQuoteMergeBefore');

            $moduleEnabled = $this->scopeConfig->isSetFlag(
                'checkout/cart/avoid_cart_merging_status',
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES
            );

            $allowedPages = explode(',', $this->scopeConfig->getValue(
                'checkout/cart/avoid_cart_merging_allowed_pages',
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES
            ));

            if ($moduleEnabled && isset($allowedPages) && count($allowedPages) > 0) {

                $controllerName = $this->getControllerName();
                if (!empty($controllerName) && in_array($this->getControllerName(), $allowedPages)) {
                    if ($observer->getSource()->hasItems()) {
                        //$currentQuote = $observer->getSource();

                        if (is_object($observer->getQuote()) && $observer->getQuote()->getId()) {
                            $this->logger->info('SalesQuoteMergeBefore | remove all items');
                            $observer->getQuote()->removeAllItems();
                        }
                    }
                }
            }

            $this->logger->info('SalesQuoteMergeBefore | current quoteId: ' . $observer->getSource()->getId() . ' | items count: ' . count($observer->getSource()->getAllVisibleItems()));
            $this->logger->info('SalesQuoteMergeBefore | old quoteId: ' . $observer->getQuote()->getId() . ' | items count: ' . count($observer->getQuote()->getAllVisibleItems()));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}