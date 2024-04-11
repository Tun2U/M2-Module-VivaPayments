<?php

/**
 * @category    Tun2U
 * @package     Tun2U_VivaPayments
 * @author      Tun2U Team <dev@tun2u.com>
 * @copyright   Copyright (c) 2024 Tun2U (https://www.tun2u.com)
 * @license     https://opensource.org/licenses/gpl-3.0.html  GNU General Public License (GPL 3.0)
 */

namespace Tun2U\VivaPayments\Model;

class VivaPayments extends \Magento\Framework\Model\AbstractModel
{

    public function _construct()
    {
        $this->_init('Tun2U\VivaPayments\Model\ResourceModel\VivaPayments');
    }
}
