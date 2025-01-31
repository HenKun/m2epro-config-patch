<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\Description\Specific;

/**
 * Class \Ess\M2ePro\Model\Amazon\Template\Description\Specific\Source
 */
class Source extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Magento\Product $magentoProduct
     */
    private $magentoProduct = null;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Template\Description\Specific $descriptionSpecificTemplateModel
     */
    private $descriptionSpecificTemplateModel = null;

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return $this
     */
    public function setMagentoProduct(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $this->magentoProduct = $magentoProduct;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product
     */
    public function getMagentoProduct()
    {
        return $this->magentoProduct;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Amazon\Template\Description\Specific $instance
     * @return $this
     */
    public function setDescriptionSpecificTemplate(\Ess\M2ePro\Model\Amazon\Template\Description\Specific $instance)
    {
        $this->descriptionSpecificTemplateModel = $instance;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\Description\Specific
     */
    public function getDescriptionSpecificTemplate()
    {
        return $this->descriptionSpecificTemplateModel;
    }

    //########################################

    /**
     * @return string
     */
    public function getPath()
    {
        $xpath = $this->getDescriptionSpecificTemplate()->getXpath();
        $xpathParts = explode('/', $xpath);

        $path = '';
        $isFirst = true;

        foreach ($xpathParts as $part) {
            [$tag,$index] = explode('-', $part);

            if (!$tag) {
                continue;
            }

            $isFirst || $path .= '{"childNodes": ';
            $path .= "{\"$tag\": {\"$index\": ";
            $isFirst = false;
        }

        $templateObj = $this->getDescriptionSpecificTemplate();

        if ($templateObj->isModeNone()) {
            $path .= '[]';
            $path .= str_repeat('}', substr_count($path, '{'));

            return $path;
        }

        $path .= '%data%';
        $path .= str_repeat('}', substr_count($path, '{'));

        $path = str_replace(
            '%data%',
            '{"value": '
            . $this->getHelper('Data')->jsonEncode($this->getValue())
            . ',"attributes": ' .$this->getValueAttributes(). '}',
            $path
        );

        return $path;
    }

    public function getValue()
    {
        $templateObj = $this->getDescriptionSpecificTemplate();

        if ($templateObj->isModeNone()) {
            return false;
        }

        $value = $templateObj->getData($templateObj->getMode());

        if ($templateObj->isModeCustomAttribute()) {
            $value = $this->getMagentoProduct()->getAttributeValue($value, false);
        }

        $templateObj->isTypeInt() && $value = (int)$value;
        $templateObj->isTypeFloat() && $value = (float)str_replace(',', '.', $value);
        $templateObj->isTypeDateTime() && $value = str_replace(' ', 'T', $value);
        $templateObj->isTypeBoolean() && $value = $this->convertValueToBoolean($value);

        return $value;
    }

    public function getValueAttributes()
    {
        $templateObj = $this->getDescriptionSpecificTemplate();

        $attributes = [];

        foreach ($templateObj->getAttributes() as $index => $attribute) {
            [$attributeName] = array_keys($attribute);

            $attributeData = $attribute[$attributeName];

            $attributeValue = ($attributeData['mode'] ==
                \Ess\M2ePro\Model\Amazon\Template\Description\Specific::DICTIONARY_MODE_CUSTOM_VALUE)
                    ? $attributeData['custom_value']
                    : $this->getMagentoProduct()->getAttributeValue($attributeData['custom_attribute'], false);

            $attributes[$index] = [
                'name'  => str_replace(' ', '', $attributeName),
                'value' => $attributeValue,
            ];
        }

        return $this->getHelper('Data')->jsonEncode($attributes);
    }

    //########################################

    private function convertValueToBoolean($value)
    {
        if ($value === true) {
            $value = 'true';
        } elseif ($value === false) {
            $value = 'false';
        }

        return $value;
    }

    //########################################
}
