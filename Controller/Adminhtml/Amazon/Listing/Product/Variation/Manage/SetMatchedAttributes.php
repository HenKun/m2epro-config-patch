<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Manage;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class SetMatchedAttributes extends Main
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon\Vocabulary */
    protected $vocabularyHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\Vocabulary $vocabularyHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->vocabularyHelper = $vocabularyHelper;
    }

    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');
        $variationAttributes = $this->getRequest()->getParam('variation_attributes');

        if (empty($productId) || empty($variationAttributes)) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        $matchedAttributes = array_combine(
            $variationAttributes['magento_attributes'],
            $variationAttributes['amazon_attributes']
        );

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $productId);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        $typeModel = $amazonListingProduct->getVariationManager()->getTypeModel();

        if (!empty($variationAttributes['virtual_magento_attributes'])) {
            $typeModel->setVirtualProductAttributes(
                array_combine(
                    $variationAttributes['virtual_magento_attributes'],
                    $variationAttributes['virtual_magento_option']
                )
            );
        } elseif (!empty($variationAttributes['virtual_amazon_attributes'])) {
            $typeModel->setVirtualChannelAttributes(
                array_combine(
                    $variationAttributes['virtual_amazon_attributes'],
                    $variationAttributes['virtual_amazon_option']
                )
            );
        }

        $typeModel->setMatchedAttributes($matchedAttributes);
        $typeModel->getProcessor()->process();

        $result = [
            'success' => true,
        ];

        if ($listingProduct->getMagentoProduct()->isGroupedType()) {
            $this->setJsonContent($result);

            return $this->getResult();
        }

        if ($this->vocabularyHelper->isAttributeAutoActionDisabled()) {
            $this->setJsonContent($result);

            return $this->getResult();
        }

        $attributesForAddingToVocabulary = [];

        foreach ($matchedAttributes as $productAttribute => $channelAttribute) {
            if ($productAttribute == $channelAttribute) {
                continue;
            }

            if ($this->vocabularyHelper->isAttributeExistsInLocalStorage($productAttribute, $channelAttribute)) {
                continue;
            }

            if ($this->vocabularyHelper->isAttributeExistsInServerStorage($productAttribute, $channelAttribute)) {
                continue;
            }

            $attributesForAddingToVocabulary[$productAttribute] = $channelAttribute;
        }

        if ($this->vocabularyHelper->isAttributeAutoActionNotSet()) {
            if (!empty($attributesForAddingToVocabulary)) {
                $result['vocabulary_attributes'] = $attributesForAddingToVocabulary;
            }

            $this->setJsonContent($result);

            return $this->getResult();
        }

        foreach ($attributesForAddingToVocabulary as $productAttribute => $channelAttribute) {
            $this->vocabularyHelper->addAttribute($productAttribute, $channelAttribute);
        }

        $this->setJsonContent($result);

        return $this->getResult();
    }
}
