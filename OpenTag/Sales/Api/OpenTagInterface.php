<?php
declare(strict_types=1);

namespace OpenTag\Sales\Api;

use Magento\Sales\Model\Order;

interface OpenTagInterface
{
    public const FINISHED_ORDER_STATE_LIST = [
        Order::STATE_COMPLETE,
        Order::STATE_CANCELED,
        Order::STATE_CLOSED
    ];

    public const SUPER_ADMIN_ID = 1;

    public const ORDER_STATUS_OPENTAG_CODE = 'opentag';

    public const ORDER_STATUS_OPENTAG_LABEL = 'Open Tag status';
}
