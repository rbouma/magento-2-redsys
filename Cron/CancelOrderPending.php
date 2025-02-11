<?php

namespace Catgento\Redsys\Cron;

use Catgento\Redsys\Logger\Logger;
use Catgento\Redsys\Model\ConfigInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * Class CancelOrderPending
 */
class CancelOrderPending
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var FilterBuilder
     */
    private $filterBuilder;
    /**
     * @var FilterGroup
     */
    private $filterGroup;

    /**
     * CancelOrderPending constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param FilterGroup $filterGroup
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder    $searchCriteriaBuilder,
        FilterBuilder            $filterBuilder,
        FilterGroup              $filterGroup,
        ScopeConfigInterface     $scopeConfig,
        Logger                   $logger
    )
    {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroup = $filterGroup;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * @throws \Exception
     */
    public function execute()
    {
        $enabled = $this->scopeConfig->getValue(ConfigInterface::XML_PATH_CANCEL_PENDING_ORDERS);
        $methodCode = 'redsys';

        if ($enabled) {
            $today = date("Y-m-d h:i:s");
            $to = strtotime('-10 min', strtotime($today));
            $to = date('Y-m-d h:i:s', $to);

            $filterGroupDate = $this->filterGroup;
            $filterGroupStatus = clone($filterGroupDate);
            $filterGroupMethod = clone($filterGroupDate);

            $this->logger->info('Retrieving orders to cancel');

            $filterDate = $this->filterBuilder
                ->setField('updated_at')
                ->setConditionType('to')
                ->setValue($to)
                ->create();
            $filterStatus = $this->filterBuilder
                ->setField('status')
                ->setConditionType('eq')
                ->setValue('pending')
                ->create();

            $filterMethod = $this->filterBuilder->setField('extension_attribute_payment_method.method')
                ->setConditionType('eq')
                ->setValue($methodCode)
                ->create();

            $filterGroupDate->setFilters([$filterDate]);
            $filterGroupStatus->setFilters([$filterStatus]);
            $filterGroupMethod->setFilters([$filterMethod]);

            $searchCriteria = $this->searchCriteriaBuilder->setFilterGroups(
                [$filterGroupDate, $filterGroupStatus, $filterGroupMethod]
            );
            $searchResults = $this->orderRepository->getList($searchCriteria->create());

            /** @var Order $order */
            foreach ($searchResults->getItems() as $order) {
                $this->logger->info('Canceling order (idle for more than 10 minutes): ' . $order->getIncrementId());
                $comment = __('Order cancelled because it was idle for more than 10 minutes');
                $order->cancel();
                $order->addStatusHistoryComment($comment)
                    ->setIsCustomerNotified(false);
                $order->save();
            }
        }
    }
}
