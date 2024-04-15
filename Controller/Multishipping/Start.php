<?php

/**
 * @category    Tun2U
 * @package     Tun2U_VivaPayments
 * @author      Tun2U Team <dev@tun2u.com>
 * @copyright   Copyright (c) 2024 Tun2U (https://www.tun2u.com)
 * @license     https://opensource.org/licenses/gpl-3.0.html  GNU General Public License (GPL 3.0)
 */

namespace Tun2U\VivaPayments\Controller\Multishipping;

class Start extends \Magento\Framework\App\Action\Action
{
    protected $_checkoutSession;

    protected $_resultPageFactory;

    protected $_paymentMethod;

    protected $_session;

    protected $_orderRepository;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Tun2U\VivaPayments\Model\PaymentMethod $paymentMethod
     * @param \Magento\Framework\Session\SessionManagerInterface $session
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Tun2U\VivaPayments\Model\PaymentMethod $paymentMethod,
        \Magento\Framework\Session\SessionManagerInterface $session,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_paymentMethod = $paymentMethod;
        $this->_session = $session;
        $this->_orderRepository = $orderRepository;
        parent::__construct($context);
    }

    /**
     * Start checkout by requesting checkout code and dispatching customer to Coinbase.
     */
    public function execute()
    {
        $grandTotal = $this->getRequest()->getParam('grandTotal');
        $orderIds = $this->_session->getOrderIds();
        $order = $this->getOrder();
        if ($orderIds) {
            $order = $this->_orderRepository->get(array_keys($orderIds)[0]);
        }
        $html = $this->_paymentMethod->getPostHTML($order, $grandTotal);
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
