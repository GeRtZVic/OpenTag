<?php
declare(strict_types=1);

namespace OpenTag\Sales\Controller\Adminhtml\Status;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use OpenTag\Sales\Api\OpenTagInterface;

class Index extends Action
{
    const ADMIN_RESOURCE = 'OpenTag_Sales::changestatus_order';
    private OrderRepositoryInterface $orderRepository;
    private RequestInterface $request;
    private ManagerInterface $manager;
    private RedirectFactory $redirectFactory;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param RequestInterface $request
     * @param ManagerInterface $manager
     * @param RedirectFactory $redirectFactory
     * @param Context $context
     */
    public function __construct(
        OrderRepositoryInterface  $orderRepository,
        RequestInterface          $request,
        ManagerInterface          $manager,
        RedirectFactory           $redirectFactory,
        Context                   $context,
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->request = $request;
        $this->manager = $manager;
        $this->redirectFactory = $redirectFactory;
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        try {
            $orderEntity = $this->orderRepository->get($this->request->getParam('order_id'));
            if ($orderEntity->getStatus() !== OpenTagInterface::ORDER_STATUS_OPENTAG_CODE) {
                $orderEntity->setStatus(OpenTagInterface::ORDER_STATUS_OPENTAG_CODE);
                $this->orderRepository->save($orderEntity);
                $this->manager->addSuccessMessage("Order successfully changed status to " .
                    OpenTagInterface::ORDER_STATUS_OPENTAG_LABEL);
            } else {
                $this->manager->addErrorMessage("This Order already has status" .
                    OpenTagInterface::ORDER_STATUS_OPENTAG_LABEL);
            }
        } catch (InputException|NoSuchEntityException $e) {
            $this->manager->addErrorMessage($e->getMessage());
        }

        return $this->resultRedirectFactory->create()->setPath(
            'sales/order/view',
            [
                'order_id' => $this->request->getParam('order_id')
            ]
        );
    }
}
