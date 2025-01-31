<?php

namespace Ess\M2ePro\Model\ChangeTracker\Base;

use Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger;
use Ess\M2ePro\Model\ChangeTracker\Common\PriceCondition\PriceConditionFactory;
use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder;
use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\QueryBuilderFactory;
use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder;

abstract class BasePriceTracker implements TrackerInterface
{
    /** @var string */
    private $channel;
    /** @var \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder */
    protected $queryBuilder;
    /** @var \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger */
    protected $logger;
    /** @var \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder */
    protected $attributesQueryBuilder;
    /** @var \Ess\M2ePro\Model\ChangeTracker\Common\PriceCondition\AbstractPriceCondition */
    private $priceConditionBuilder;

    /**
     * @param string $channel
     * @param \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\QueryBuilderFactory $queryBuilderFactory
     * @param \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder $attributesQueryBuilder
     * @param \Ess\M2ePro\Model\ChangeTracker\Common\PriceCondition\PriceConditionFactory $conditionFactory
     * @param \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger $logger
     */
    public function __construct(
        string $channel,
        QueryBuilderFactory $queryBuilderFactory,
        ProductAttributesQueryBuilder $attributesQueryBuilder,
        PriceConditionFactory $conditionFactory,
        TrackerLogger $logger
    ) {
        $this->channel = $channel;
        $this->queryBuilder = $queryBuilderFactory->make();
        $this->attributesQueryBuilder = $attributesQueryBuilder;
        $this->priceConditionBuilder = $conditionFactory->create($channel);
        $this->logger = $logger;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return TrackerInterface::TYPE_PRICE;
    }

    /**
     * @return string
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * @return string
     */
    abstract protected function getOnlinePriceCondition(): string;

    /**
     * @return \Magento\Framework\DB\Select
     */
    public function getDataQuery(): \Magento\Framework\DB\Select
    {
        $query = $this->getSelectQuery();

        $mainQuery = $this->queryBuilder
            ->makeSubQuery()
            ->distinct()
            ->addSelect('listing_product_id', 'base.listing_product_id')
            ->from('base', $query);

        $mainQuery->andWhere('calculated_price IS NOT NULL')
            ->andWhere('calculated_price != online_price')
            ->andWhere('base.status = ?', \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED)
            ->andWhere('base.revise_update_price = 1');

        $message = sprintf(
            'Data query %s %s',
            $this->getType(),
            $this->getChannel()
        );
        $this->logger->debug($message, [
            'query' => (string) $mainQuery->getQuery(),
            'type' => $this->getType(),
            'channel' => $this->getChannel(),
        ]);

        return $mainQuery->getQuery();
    }

    /**
     * @return \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder
     */
    protected function productSubQuery(): SelectQueryBuilder
    {
        $query = $this->queryBuilder->makeSubQuery();
        $query->distinct();

        $query->addSelect('listing_product_id', 'lp.id');

        $productIdExpression = 'IFNULL(lpvo.product_id, lp.product_id)';
        $query->addSelect('product_id', $productIdExpression);

        $query
            ->addSelect('status', 'lp.status')
            ->addSelect('store_id', 'l.store_id')
            ->addSelect('sync_template_id', 'c_l.template_synchronization_id')
            ->addSelect('selling_template_id', 'c_l.template_selling_format_id')
        ;

        $query->addSelect('online_price', $this->getOnlinePriceCondition());

        /* Select base attributes */
        $attributes = [
            ['name' => 'price', 'alias' => 'price',],
            ['name' => 'special_price', 'alias' => 'special_price',],
            ['name' => 'special_from_date', 'alias' => 'special_from_date',],
            ['name' => 'special_to_date', 'alias' => 'special_to_date',],
        ];

        foreach ($attributes as $attribute) {
            $alias = $attribute['alias'];
            $attributeQuery = $this->attributesQueryBuilder->getQueryForAttribute(
                $attribute['name'],
                'l.store_id',
                $productIdExpression
            );

            $query->addSelect($alias, $attributeQuery);
        }

        $query
            ->from(
                'c_lp',
                $this->setChannelToTableName('m2epro_%s_listing_product')
            )
            ->innerJoin(
                'lp',
                'm2epro_listing_product',
                'lp.id = c_lp.listing_product_id'
            )
            ->innerJoin(
                'l',
                'm2epro_listing',
                'lp.listing_id = l.id'
            )
            ->innerJoin(
                'c_l',
                $this->setChannelToTableName('m2epro_%s_listing'),
                'c_l.listing_id = lp.listing_id'
            )
            ->leftJoin(
                'lpv',
                'm2epro_listing_product_variation',
                'lpv.listing_product_id = lp.id'
            )
            ->leftJoin(
                'lpvo',
                'm2epro_listing_product_variation_option',
                'lpvo.listing_product_variation_id = lpv.id'
            )
        ;

        /* Не включаем в выборку grouped and bundle товары */
        $query->andWhere('lpvo.product_type != ?', 'grouped');
        $query->andWhere('lpvo.product_type != ?', 'bundle');

        $query->addGroup('lp.id');
        $query->addGroup('IFNULL(lpvo.product_id, lp.product_id)');

        return $query;
    }

    /**
     * @return SelectQueryBuilder
     */
    protected function synchronizationPolicySubQuery(): SelectQueryBuilder
    {
        return $this->queryBuilder
            ->makeSubQuery()
            ->addSelect('template_synchronization_id', 'ts.template_synchronization_id')
            ->addSelect('revise_update_price', 'ts.revise_update_price')
            ->addSelect(
                'revise_threshold',
                'IF(
                            ts.revise_update_qty_max_applied_value_mode = 1,
                            ts.revise_update_qty_max_applied_value,
                            999999
                        )'
            )
            ->from(
                'ts',
                $this->setChannelToTableName('m2epro_%s_template_synchronization')
            )
            ;
    }

    /**
     * @inheridoc
     */
    protected function getSelectQuery(): SelectQueryBuilder
    {
        $query = $this->queryBuilder;

        /* Required selects */
        $query->addSelect('listing_product_id', 'product.listing_product_id');
        $query->addSelect('calculated_price', $this->priceConditionBuilder->getCondition());

        $query->addSelect('product_id', 'product.product_id')
            ->addSelect('status', 'product.status')
            ->addSelect('store_id', 'product.store_id')
            ->addSelect('sync_template_id', 'product.sync_template_id')
            ->addSelect('selling_template_id', 'product.selling_template_id')
            ->addSelect('online_price', 'product.online_price')
            ->addSelect('revise_update_price', 'sync_policy.revise_update_price')
        ;

        /* Tables */
        $query
            ->from('product', $this->productSubQuery())
            ->leftJoin(
                'sync_policy',
                $this->synchronizationPolicySubQuery(),
                'sync_policy.template_synchronization_id = product.sync_template_id'
            )
        ;

        /* Groups */
        $query
            ->addGroup('product.listing_product_id')
            ->addGroup('product.product_id')
        ;

        return $query;
    }

    /**
     * @param string $tableName
     *
     * @return string
     */
    protected function setChannelToTableName(string $tableName): string
    {
        return sprintf($tableName, $this->getChannel());
    }

    protected function getPriceColumnCondition(int $mode, $modeAttribute): string
    {
        if ($mode === \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL) {
            return '(CASE
            WHEN product.special_price IS NOT NULL
                AND product.special_from_date IS NOT NULL
                AND product.special_to_date IS NOT NULL
                AND NOW() BETWEEN product.special_from_date AND product.special_to_date
            THEN product.special_price
            WHEN product.special_price IS NOT NULL
                AND product.special_from_date IS NOT NULL
                AND product.special_from_date + INTERVAL 1 YEAR > NOW()
            THEN product.special_price
            ELSE product.price
          END)';
        }

        if ($mode === \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE) {
            $attributeQuery = $this->attributesQueryBuilder
                ->getQueryForAttribute(
                    $modeAttribute,
                    'product.store_id',
                    'product.product_id'
                );
            return "($attributeQuery)";
        }

        return 'product.price';
    }
}
