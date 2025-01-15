<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\SalesRule\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Multicoupon\Model\Config\Config;

/**
 * Class for process order for resetting shipping flag.
 */
class ProcessOrderCreationDataObserver implements ObserverInterface
{
    /**
     * @param Config $multiCouponConfig
     */
    public function __construct(private Config $multiCouponConfig)
    {
    }

    /**
     * Checking shipping method and resetting it if needed.
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrderCreateModel();
        $request = $observer->getEvent()->getRequest();
        if (array_key_exists('order', $request)) {
            $quote = $order->getQuote();
            $isVirtualQuote = $quote->isVirtual();
            $quoteShippingMethod = $observer->getEvent()->getShippingMethod();
            $checkIfCouponExists = array_key_exists('coupon', $request['order']);
            $noOfCouponsAvailable = $this->multiCouponConfig->getMaximumNumberOfCoupons();
            if (!$isVirtualQuote && !empty($quoteShippingMethod) && $checkIfCouponExists && $noOfCouponsAvailable <= 1) {
                    $shippingAddress = $quote->getShippingAddress();
                    $shippingAddress->setShippingMethod($quoteShippingMethod);
            }
        }
        return $this;
    }
}
