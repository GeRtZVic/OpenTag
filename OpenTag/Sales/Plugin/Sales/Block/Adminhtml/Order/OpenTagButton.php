<?php
declare(strict_types=1);

namespace OpenTag\Sales\Plugin\Sales\Block\Adminhtml\Order;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Block\Adminhtml\Order\View;
use OpenTag\Sales\Api\OpenTagInterface;
use Weidinger\NavisionManagement\Controller\Adminhtml\Export\Index;

class OpenTagButton
{
    private OrderRepositoryInterface $orderRepository;

    private AuthorizationInterface $authorization;

    private RequestInterface $request;

    private UrlInterface $url;

    private Session $session;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param AuthorizationInterface $authorization
     * @param RequestInterface $request
     * @param Session $session
     * @param UrlInterface $url
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        AuthorizationInterface   $authorization,
        RequestInterface         $request,
        Session                  $session,
        UrlInterface             $url
    ) {
        $this->orderRepository = $orderRepository;
        $this->authorization = $authorization;
        $this->request = $request;
        $this->session = $session;
        $this->url = $url;
    }

    /**
     * Adding button on view page
     *
     * @param View $subject
     * @return void
     */
    public function beforeSetLayout(View $subject)
    {
        if (!$this->authorization->isAllowed(Index::ADMIN_RESOURCE)) {
            return;
        }
        $orderEntity = $this->orderRepository->get($this->request->getParam('order_id'));
        $isOrderCompleted = in_array($orderEntity->getState(), OpenTagInterface::FINISHED_ORDER_STATE_LIST);
        $isStatusOpenTag = $orderEntity->getStatus() === OpenTagInterface::ORDER_STATUS_OPENTAG_CODE;
        $isSuperUser = (int)$this->session->getUser()->getId() === OpenTagInterface::SUPER_ADMIN_ID;
        if (!$isOrderCompleted && !$isStatusOpenTag && $isSuperUser) {
            $subject->addButton(
                'open_tag_button_status',
                [
                    'label' => __('Open tag change status'),
                    'class' => __('open-tag-custom-button'),
                    'id' => 'open-tag-order-button',
                    'onclick' => 'setLocation(\'' . $this->getExportUrl() . '\')'
                ]
            );
        }
    }

    /**
     * Get order export link
     *
     * @return string
     */
    protected function getExportUrl(): string
    {
        return $this->url->getUrl(
            'changestatus/status/index',
            ['order_id' => $this->request->getParam('order_id')]
        );
    }
}
