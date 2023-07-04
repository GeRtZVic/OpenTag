<?php
declare(strict_types=1);

namespace OpenTag\Sales\Cron;

use Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use OpenTag\Sales\Api\OpenTagInterface;
use Psr\Log\LoggerInterface;

class ChangeOrdersStatusIfOrderNotOpenTag
{
    private OrderRepositoryInterface $orderRepository;

    private SearchCriteriaBuilder $searchCriteriaBuilder;

    private TimezoneInterface $timezone;

    private LoggerInterface $logger;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TimezoneInterface $timezone
     * @param LoggerInterface $logger
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder    $searchCriteriaBuilder,
        TimezoneInterface        $timezone,
        LoggerInterface          $logger
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        $this->timezone = $timezone;
        $this->logger = $logger;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        while ($orderList = $this->getOrderList()) {
            foreach ($orderList as $order) {
                $order->setStatus(OpenTagInterface::ORDER_STATUS_OPENTAG_CODE);
                $this->orderRepository->save($order);
            }
        }
    }

    /**
     * @return array|OrderInterface[]
     */
    private function getOrderList(): array
    {
        $maxOrderUpdateAt = $this->timezone
            ->date(null, null, false)
            ->modify("-24 hours ");
        $orderList = [];
        try {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(OrderInterface::CREATED_AT, $maxOrderUpdateAt, 'to')
                ->addFilter(
                    OrderInterface::STATUS,
                    OpenTagInterface::ORDER_STATUS_OPENTAG_CODE,
                    'neq'
                )
                ->addFilter(
                    OrderInterface::STATE,
                    [
                        Order::STATE_COMPLETE,
                        Order::STATE_CANCELED,
                        Order::STATE_CLOSED
                    ],
                    'nin'
                )
                ->setPageSize(10)
                ->create();
            $orderList = $this->orderRepository->getList($searchCriteria)->getItems();
        } catch (Exception $exception) {
            $this->logger->debug($exception->getMessage());
        }

        return $orderList;
    }
}
