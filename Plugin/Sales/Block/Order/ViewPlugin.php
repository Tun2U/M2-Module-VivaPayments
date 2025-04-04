<?php

/**
 * @category    Tun2U
 * @package     Tun2U_VivaPayments
 * @author      Tun2U Team <dev@tun2u.com>
 * @copyright   Copyright (c) 2024 Tun2U (https://www.tun2u.com)
 * @license     https://opensource.org/licenses/gpl-3.0.html  GNU General Public License (GPL 3.0)
 */

namespace Tun2U\VivaPayments\Plugin\Sales\Block\Order;

class ViewPlugin
{
    /**
     * @var \Magento\Payment\Block\Info
     */
    private $defaultInfoBlock;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    private $layout;

    /**
     * @param \Magento\Framework\View\LayoutInterface $layout
     */
    public function __construct(
        \Magento\Framework\View\LayoutInterface $layout
    ) {
        $this->layout = $layout;
    }

    /**
     * Around plugin for getPaymentInfoHtml to fix null payment info
     *
     * @param \Magento\Sales\Block\Order\View $subject
     * @param callable $proceed
     * @return string
     */
    public function aroundGetPaymentInfoHtml(
        \Magento\Sales\Block\Order\View $subject,
        callable $proceed
    ) {
        try {
            // Attempt to get the payment info html using the original method
            return $proceed();
        } catch (\TypeError $e) {
            // If a TypeError occurs (like null payment info), return an empty string
            // This prevents the order view page from breaking
            return '';
        }
    }
}
