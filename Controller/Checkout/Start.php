<?php

/**
 * @category    Tun2U
 * @package     Tun2U_VivaPayments
 * @author      Tun2U Team <dev@tun2u.com>
 * @copyright   Copyright (c) 2024 Tun2U (https://www.tun2u.com)
 * @license     https://opensource.org/licenses/gpl-3.0.html  GNU General Public License (GPL 3.0)
 */

namespace Tun2U\VivaPayments\Controller\Checkout;

class Start extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var \Tun2U\VivaPayments\Model\PaymentMethod
     */
    protected $_paymentMethod;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Tun2U\VivaPayments\Model\PaymentMethod $paymentMethod
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Tun2U\VivaPayments\Model\PaymentMethod $paymentMethod
    ) {
        $this->_paymentMethod = $paymentMethod;
        $this->_checkoutSession = $checkoutSession;
        $this->_resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Start checkout by requesting checkout code and dispatching customer to Coinbase.
     */
    public function execute()
    {
        $html = $this->_paymentMethod->getPostHTML($this->getOrder());
        echo $html;
    }

    /**
     * Get order object.
     *
     * @return \Magento\Sales\Model\Order
     */
    protected function getOrder()
    {
        return $this->_checkoutSession->getLastRealOrder();
    }
}
