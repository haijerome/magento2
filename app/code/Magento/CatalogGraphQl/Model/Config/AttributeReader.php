<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */

namespace Magento\CatalogGraphQl\Model\Config;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\CatalogGraphQl\Model\Resolver\Products\Attributes\Collection;
use Magento\CatalogGraphQl\Model\Resolver\Products\Attributes\CollectionFactory;
use Magento\EavGraphQl\Model\Resolver\Query\Type;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\Entity\MapperInterface;
use Magento\Framework\Reflection\TypeProcessor;
use Magento\Store\Model\ScopeInterface;

/**
 * Adds custom/eav attribute to Catalog product types in the GraphQL config.
 */
class AttributeReader implements ReaderInterface
{
    public const XML_PATH_INCLUDE_DYNAMIC_ATTRIBUTES =
        'web_api/graphql/include_dynamic_attributes_as_entity_type_fields';

    /**
     * @var MapperInterface
     */
    private MapperInterface $mapper;

    /**
     * @var Type
     */
    private Type $typeLocator;

    /**
     * @var CollectionFactory
     */
    private readonly CollectionFactory $collectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $config;

    /**
     * @param MapperInterface $mapper
     * @param Type $typeLocator
     * @param Collection $collection @deprecated @see $collectionFactory
     * @param ScopeConfigInterface $config
     * @param CollectionFactory|null $collectionFactory
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        MapperInterface $mapper,
        Type $typeLocator,
        Collection $collection,
        ScopeConfigInterface $config,
        ?CollectionFactory $collectionFactory = null,
    ) {
        $this->mapper = $mapper;
        $this->typeLocator = $typeLocator;
        $this->collectionFactory = $collectionFactory ?? ObjectManager::getInstance()->get(CollectionFactory::class);
        $this->config = $config;
    }

    /**
     * Read configuration scope
     *
     * @param string|null $scope
     * @return array
     * @throws GraphQlInputException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function read($scope = null) : array
    {
        $config = [];
        if ($this->config->isSetFlag(self::XML_PATH_INCLUDE_DYNAMIC_ATTRIBUTES, ScopeInterface::SCOPE_STORE)) {
            $typeNames = $this->mapper->getMappedTypes(Product::ENTITY);

            /** @var Attribute $attribute */
            foreach ($this->collectionFactory->create()->getAttributes() as $attribute) {
                $attributeCode = $attribute->getAttributeCode();
                $locatedType = $this->typeLocator->getType($attributeCode, Product::ENTITY) ?: 'String';
                $locatedType = TypeProcessor::NORMALIZED_ANY_TYPE === $locatedType ? 'String' : ucfirst($locatedType);

                foreach ($typeNames as $typeName) {
                    $config[$typeName]['fields'][$attributeCode] = [
                        'name' => $attributeCode,
                        'type' => $locatedType,
                        'arguments' => [],
                        'deprecated' => ['reason' => 'Use the `custom_attributes` field instead.'],
                    ];
                }
            }
        }

        return $config;
    }
}
