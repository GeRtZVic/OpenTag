<?php
declare(strict_types=1);

namespace OpenTag\Sales\Observer;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use OpenTag\Sales\Api\OpenTagInterface;
use OpenTag\Sales\Setup\Patch\Data\AddNewOrderStatus;
use Psr\Log\LoggerInterface;

class PreventViewOpenTagOrders implements ObserverInterface
{
    private RequestInterface $request;

    private ActionFlag $actionFlag;

    private Session $session;

    private UrlInterface $url;

    private OrderRepositoryInterface $orderRepository;

    private ManagerInterface $manager;

    private LoggerInterface $logger;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param RequestInterface $request
     * @param ActionFlag $actionFlag
     * @param LoggerInterface $logger
     * @param ManagerInterface $manager
     * @param Session $session
     * @param UrlInterface $url
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        RequestInterface         $request,
        ManagerInterface         $manager,
        LoggerInterface          $logger,
        UrlInterface             $url,
        ActionFlag               $actionFlag,
        Session                  $session,
    )
    {
        $this->orderRepository = $orderRepository;
        $this->actionFlag = $actionFlag;
        $this->request = $request;
        $this->session = $session;
        $this->manager = $manager;
        $this->logger = $logger;
        $this->url = $url;
    }


    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $orderId = $this->request->getParam('order_id', null);
        if ($orderId === null) {
            return;
        }
        try {
            $order = $this->orderRepository->get($orderId);
            $isOrderStatusOpenTag = $order->getStatus() !== OpenTagInterface::ORDER_STATUS_OPENTAG_CODE;
            $isAllowedOrderView = (int)$this->session->getUser()->getId() === OpenTagInterface::SUPER_ADMIN_ID;
            if ($isOrderStatusOpenTag || $isAllowedOrderView) {
                return;
            }
            $this->actionFlag->set('', ActionInterface::FLAG_NO_DISPATCH, true);
            $redirectUrl = $this->url->getUrl('sales/order/index');

            $controller = $observer->getControllerAction();
            $controller->getResponse()->setRedirect($redirectUrl);
            $this->manager->addErrorMessage(__('You dont have access permissions.'));

            return $this;
        } catch (\Exception $exception) {
            $this->logger->debug($exception->getMessage());
        }
    }
}
