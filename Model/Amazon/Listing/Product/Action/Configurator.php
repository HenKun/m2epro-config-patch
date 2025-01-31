<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator
 */
class Configurator extends \Ess\M2ePro\Model\Listing\Product\Action\Configurator
{
    const DATA_TYPE_QTY            = 'qty';
    const DATA_TYPE_REGULAR_PRICE  = 'regular_price';
    const DATA_TYPE_BUSINESS_PRICE = 'business_price';
    const DATA_TYPE_IMAGES         = 'images';
    const DATA_TYPE_DETAILS        = 'details';

    //########################################

    /**
     * @return array
     */
    public function getAllDataTypes()
    {
        return [
            self::DATA_TYPE_QTY,
            self::DATA_TYPE_REGULAR_PRICE,
            self::DATA_TYPE_BUSINESS_PRICE,
            self::DATA_TYPE_DETAILS,
            self::DATA_TYPE_IMAGES,
        ];
    }

    //########################################

    /**
     * @return bool
     */
    public function isQtyAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_QTY);
    }

    /**
     * @return $this
     */
    public function allowQty()
    {
        return $this->allow(self::DATA_TYPE_QTY);
    }

    /**
     * @return $this
     */
    public function disallowQty()
    {
        return $this->disallow(self::DATA_TYPE_QTY);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isRegularPriceAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_REGULAR_PRICE);
    }

    /**
     * @return $this
     */
    public function allowRegularPrice()
    {
        return $this->allow(self::DATA_TYPE_REGULAR_PRICE);
    }

    /**
     * @return $this
     */
    public function disallowRegularPrice()
    {
        return $this->disallow(self::DATA_TYPE_REGULAR_PRICE);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isBusinessPriceAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_BUSINESS_PRICE);
    }

    /**
     * @return $this
     */
    public function allowBusinessPrice()
    {
        return $this->allow(self::DATA_TYPE_BUSINESS_PRICE);
    }

    /**
     * @return $this
     */
    public function disallowBusinessPrice()
    {
        return $this->disallow(self::DATA_TYPE_BUSINESS_PRICE);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isDetailsAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_DETAILS);
    }

    /**
     * @return $this
     */
    public function allowDetails()
    {
        return $this->allow(self::DATA_TYPE_DETAILS);
    }

    /**
     * @return $this
     */
    public function disallowDetails()
    {
        return $this->disallow(self::DATA_TYPE_DETAILS);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isImagesAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_IMAGES);
    }

    /**
     * @return $this
     */
    public function allowImages()
    {
        return $this->allow(self::DATA_TYPE_IMAGES);
    }

    /**
     * @return $this
     */
    public function disallowImages()
    {
        return $this->disallow(self::DATA_TYPE_IMAGES);
    }

    //########################################
}
