<?php

/**
 * @category    Tun2U
 * @package     Tun2U_VivaPayments
 * @author      Tun2U Team <dev@tun2u.com>
 * @copyright   Copyright (c) 2024 Tun2U (https://www.tun2u.com)
 * @license     https://opensource.org/licenses/gpl-3.0.html  GNU General Public License (GPL 3.0)
 */

declare(strict_types=1);

namespace Tun2U\VivaPayments\Controller\Viva;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Action as AppAction;
use Exception;

class Callback extends AppAction implements CsrfAwareActionInterface
{
    /**
     * @var \Tun2U\VivaPayments\Model\PaymentMethod
     */
    protected $_paymentMethod;

    /**
     * @var \Tun2U\VivaPayments\Model\PaymentMethod
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_order = null;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $_orderSender;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $_messageManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Multishipping\Model\Checkout\Type\Multishipping
     */
    private $multishipping;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $_client;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Tun2U\VivaPayments\Model\PaymentMethod $paymentMethod
     * @param Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Multishipping\Model\Checkout\Type\Multishipping $multishipping
     * @param \Magento\Framework\UrlInterface $urlBuilder
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Tun2U\VivaPayments\Model\PaymentMethod $paymentMethod,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Multishipping\Model\Checkout\Type\Multishipping $multishipping,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {

        $this->_paymentMethod = $paymentMethod;
        $this->_orderFactory = $orderFactory;
        $this->_orderRepository = $orderRepository;
        $this->_client = $this->_paymentMethod->getClient();
        $this->_orderSender = $orderSender;
        $this->_messageManager = $messageManager;
        $this->_logger = $logger;
        $this->_checkoutSession = $checkoutSession;
        $this->_scopeConfig = $scopeConfig;
        $this->multishipping = $multishipping;
        $this->_urlBuilder = $urlBuilder;

        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $this->_success();
            $this->paymentAction();
        } catch (Exception $e) {
            return $this->_failure();
        }
    }

    protected function _getUrl($route, $params = [])
    {
        return $this->_urlBuilder->getUrl($route, $params);
    }

    public function getOrderId()
    {
        return $this->_checkoutSession->getLastRealOrderId();
    }

    protected function paymentAction()
    {
        if (
            $this->_checkoutSession->getCheckoutState() === \Magento\Multishipping\Model\Checkout\Type\Multishipping\State::STEP_SUCCESS
        ) {
            $orderIds = $this->multishipping->getOrderIds();
            if ($orderIds) {
                foreach ($orderIds as $orderId) {
                    $order = $this->_orderRepository->get($orderId);
                    $this->processOrder($order);
                }
                $redirectUrl = $this->_getUrl('multishipping/checkout/success');
                $this->_redirect($redirectUrl);
            }
        } else {
            $order_id = $this->getOrderId();
            $_order = $this->_loadOrder($order_id);
            $this->processOrder($_order);
            $redirectUrl = $this->_paymentMethod->getSuccessUrl();
            $this->_redirect($redirectUrl);
        }
    }

    private function processOrder($order)
    {
        $message = null;
        $payment_order = $this->getRequest()->getParam('s');
        $transactionId = $this->getRequest()->getParam('t');

        $OrderCode = $payment_order;

        // TODO get collection
        $update_order = $this->_objectManager->create('Tun2U\VivaPayments\Model\VivaPayments')->load($OrderCode, 'ordercode');

        $MerchantID = $this->_scopeConfig->getValue('payment/paymentmethod/merchantid', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $APIKey =  $this->_scopeConfig->getValue('payment/paymentmethod/merchantpass', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $request = $this->_scopeConfig->getValue('payment/paymentmethod/transaction_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $getargs = '?ordercode=' . urlencode($OrderCode);

        $session = curl_init($request);

        curl_setopt($session, CURLOPT_HTTPGET, true);
        curl_setopt($session, CURLOPT_URL, $request . $getargs);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_USERPWD, $MerchantID . ':' . $APIKey);
        $curlversion = curl_version();
        if (!preg_match("/NSS/", $curlversion['ssl_version'])) {
            curl_setopt($session, CURLOPT_SSL_CIPHER_LIST, "TLSv1");
        }

        $response = curl_exec($session);
        curl_close($session);
        try {

            if (is_object(json_decode($response))) {
                $resultObj = json_decode($response);
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        if ($resultObj->ErrorCode == 0) {
            if (sizeof($resultObj->Transactions) > 0) {
                foreach ($resultObj->Transactions as $t) {
                    if (!empty($transactionId) && $t->TransactionId == $transactionId) {
                        $currentTransactionObject = $t;
                        break;
                    }
                }
                if (empty($currentTransactionObject)) {
                    usort($resultObj->Transactions, function ($a, $b) {
                        return (strtotime($a->InsDate) - strtotime($b->InsDate)) < 0;
                    });
                    $currentTransactionObject = $resultObj->Transactions[0];
                }
                $TransactionId = $currentTransactionObject->TransactionId;
                $Amount = $currentTransactionObject->Amount;
                $StatusId = $currentTransactionObject->StatusId;
                $CustomerTrns = $currentTransactionObject->CustomerTrns;
                if (isset($StatusId) && strtoupper($StatusId) == 'F') {
                    $message = "Transactions completed Successfully";
                    $update_order->setOrderState('paid')->save();
                } else {
                    $update_order->setOrderState('failed')->save();
                    $message = 'Transaction was not completed successfully';
                }
            } else {
                $update_order->setOrderState('failed')->save();
                $message = 'No transactions found. Make sure the order code exists and is created by your account.';
            }
        } else {
            $update_order->setOrderState('failed')->save();
            $message = 'The following error occured: <strong>' . $resultObj->ErrorCode . '</strong>, ' . $resultObj->ErrorText;
        }

        if (isset($StatusId) && strtoupper($StatusId) == 'F') {
            //BOF Order Status
            $orderComment = 'Viva Wallet Smart Checkout Confirmed Transaction<br />';
            $orderComment .= 'TxID: ' . $transactionId . '<br />';

            $newstatus =  $this->_scopeConfig->getValue('payment/paymentmethod/order_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            if (!isset($newstatus) || $newstatus == '') {
                $newstatus = 'pending';
            }

            if ($newstatus == 'complete') {
                $order->setData('state', "complete");
                $order->setStatus("complete");
                $order->setBaseTotalPaid($Amount);
                $order->setTotalPaid($Amount);
                $history = $order->addStatusHistoryComment($orderComment, false);
                $history->setIsCustomerNotified(true);
            } else {
                $newstate = $newstatus;
                $order->setData('state', $newstate);
                $order->setStatus($newstate);
                $order->setBaseTotalPaid($Amount);
                $order->setTotalPaid($Amount);
                $history = $order->addStatusHistoryComment($orderComment, false);
                $history->setIsCustomerNotified(true);
            }
            //EOF Order Status

            $order->setCanSendNewEmailFlag(true)->setEmailSent(true)->save();
            $this->_orderSender->send($order, true);
            $this->_registerPaymentCapture($order, $TransactionId, $Amount, $message);
        } else {
            $checkoutHelper = $this->_objectManager->create('Tun2U\VivaPayments\Helper\Checkout');
            $checkoutHelper->cancelCurrentOrder($message);
            // https://github.com/magento/magento2/pull/12668/commits/2c1d6a4d115f1e97787349849d215e6c73ac1335
            $checkoutHelper->restoreQuote();
            $message = __('Your transaction failed or has been cancelled!');
            $this->_messageManager->addErrorMessage($message);
            $this->_redirect('checkout/cart');
        }
    }

    protected function _registerPaymentCapture($order, $transactionId, $amount, $message)
    {
        $payment = $order->getPayment();


        $payment->setTransactionId($transactionId)
            ->setPreparedMessage($this->_createVivaPaymentsComment($order, $message))
            ->setShouldCloseParentTransaction(false)
            ->setIsTransactionClosed(0)
            ->registerCaptureNotification(
                $amount,
                true
            );

        $order->save();

        $invoice = $payment->getCreatedInvoice();
        if ($invoice && !$order->getEmailSent()) {
            $this->_orderSender->send($order);
            $order->addStatusHistoryComment(
                __('You notified customer about invoice #%1.', $invoice->getIncrementId())
            )->setIsCustomerNotified(
                true
            )->save();
        }
    }

    protected function _loadOrder($order_id)
    {
        $order = $this->_orderFactory->create()->loadByIncrementId($order_id);

        if (!$order && $order->getId()) {
            throw new Exception('Could not find Magento order with id $order_id');
        }
        return $order;
    }

    protected function _success()
    {
        $this->getResponse()
            ->setStatusHeader(200);
    }

    protected function _failure()
    {
        $this->getResponse()
            ->setStatusHeader(400);
    }

    protected function _createVivaPaymentsComment($order, $message = '')
    {
        if ($message != '') {
            $message = $order->addStatusHistoryComment($message);
            $message->setIsCustomerNotified(null);
        }
        return $message;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
