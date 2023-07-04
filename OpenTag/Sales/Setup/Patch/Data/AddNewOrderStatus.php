<?php
declare(strict_types=1);

namespace OpenTag\Sales\Setup\Patch\Data;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Model\Order\Config as OrderConfig;
use Magento\Sales\Model\Order\Status;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\Status as StatusResource;
use Magento\Sales\Model\ResourceModel\Order\StatusFactory as StatusResourceFactory;
use OpenTag\Sales\Api\OpenTagInterface;

class AddNewOrderStatus implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @var StatusFactory
     */
    private StatusFactory $statusFactory;

    /**
     * @var StatusResourceFactory
     */
    private StatusResourceFactory $statusResourceFactory;

    /**
     * @var OrderConfig
     */
    private OrderConfig $orderConfig;

    /**
     * @param StatusResourceFactory $statusResourceFactory
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param StatusFactory $statusFactory
     * @param OrderConfig $orderConfig
     */
    public function __construct(
        StatusResourceFactory    $statusResourceFactory,
        ModuleDataSetupInterface $moduleDataSetup,
        StatusFactory            $statusFactory,
        OrderConfig              $orderConfig
    ) {
        $this->statusResourceFactory = $statusResourceFactory;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->statusFactory = $statusFactory;
        $this->orderConfig = $orderConfig;
    }

    /**
     * Create Open Tag Status
     *
     * @return void
     * @throws \Exception
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        /** @var StatusResource $statusResource */
        $statusResource = $this->statusResourceFactory->create();
        /** @var Status $status */
        $status = $this->statusFactory->create();
        $status->setData([
            'status' => OpenTagInterface::ORDER_STATUS_OPENTAG_CODE,
            'label' => OpenTagInterface::ORDER_STATUS_OPENTAG_LABEL,
        ]);
        try {
            $statusResource->save($status);
        } catch (AlreadyExistsException $exception) {
            return;
        }
        foreach ($this->orderConfig->getStates() as $stateCode => $state){
            if (!in_array($state,OpenTagInterface::FINISHED_ORDER_STATE_LIST)){
                $status->assignState($stateCode, false, true);
            }
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * @return array|string[]
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }
}
