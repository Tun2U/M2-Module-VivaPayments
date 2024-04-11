<?php

/**
 * @category    Tun2U
 * @package     Tun2U_VivaPayments
 * @author      Tun2U Team <dev@tun2u.com>
 * @copyright   Copyright (c) 2024 Tun2U (https://www.tun2u.com)
 * @license     https://opensource.org/licenses/gpl-3.0.html  GNU General Public License (GPL 3.0)
 */

namespace Tun2U\VivaPayments\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Config\Source\Order\Status;

/**
 * Order Status source model
 */
class Pendingpayment extends Status
{
    /**
     * @var string[]
     */
    //protected $_stateStatuses = [Order::STATE_PENDING_PAYMENT];
    //BOF Order Status
    protected $_stateStatuses = [Order::STATE_PENDING_PAYMENT, Order::STATE_PROCESSING, Order::STATE_COMPLETE, Order::STATE_CLOSED, Order::STATE_CANCELED, Order::STATE_HOLDED, Order::STATE_PAYMENT_REVIEW];
    //EOF Order Status
}
