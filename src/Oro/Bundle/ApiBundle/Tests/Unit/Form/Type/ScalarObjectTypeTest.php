<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\ApiFormBuilder;
use Oro\Bundle\ApiBundle\Form\Extension\CustomizeFormDataExtension;
use Oro\Bundle\ApiBundle\Form\Extension\EmptyDataExtension;
use Oro\Bundle\ApiBundle\Form\FormHelper;
use Oro\Bundle\ApiBundle\Form\Type\ScalarObjectType;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataHandler;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class ScalarObjectTypeTest extends TypeTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new ApiFormBuilder('', null, $this->dispatcher, $this->factory);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $customizationProcessor = $this->createMock(ActionProcessorInterface::class);
        $customizationProcessor->expects(self::any())
            ->method('createContext')
            ->willReturn($this->createMock(CustomizeFormDataContext::class));
        $entityInstantiator = $this->createMock(EntityInstantiator::class);
        $entityInstantiator->expects(self::any())
            ->method('instantiate')
            ->willReturnCallback(function ($class) {
                return new $class();
            });

        return [
            new PreloadedExtension(
                [new ScalarObjectType($this->getFormHelper())],
                [
                    FormType::class => [
                        new CustomizeFormDataExtension(
                            $customizationProcessor,
                            $this->createMock(CustomizeFormDataHandler::class)
                        ),
                        new EmptyDataExtension($entityInstantiator)
                    ]
                ]
            )
        ];
    }

    /**
     * @return FormHelper
     */
    protected function getFormHelper()
    {
        return new FormHelper(
            $this->createMock(FormFactoryInterface::class),
            $this->createMock(PropertyAccessorInterface::class),
            $this->createMock(ContainerInterface::class)
        );
    }

    public function testSubmitWhenNoApiContext()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('value'));
        $metadata->addField(new FieldMetadata('currency'));

        $config = new EntityDefinitionConfig();
        $config->addField('value');
        $config->addField('currency');

        $data = new Entity\Product();
        $formBuilder = $this->factory->createBuilder(
            FormType::class,
            $data,
            ['data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            ScalarObjectType::class,
            [
                'data_class'    => Entity\ProductPrice::class,
                'data_property' => 'value',
                'metadata'      => $metadata,
                'config'        => $config
            ]
        );
        $form = $formBuilder->getForm();

        $form->submit(['price' => 'testPriceValue']);
        self::assertTrue($form->isSynchronized());
        self::assertSame('testPriceValue', $data->getPrice()->getValue());
    }

    public function testCreateNestedObject()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('value'));
        $metadata->addField(new FieldMetadata('currency'));

        $config = new EntityDefinitionConfig();
        $config->addField('value');
        $config->addField('currency');

        $context = $this->createMock(FormContext::class);

        $data = new Entity\Product();
        $formBuilder = $this->factory->createBuilder(
            FormType::class,
            $data,
            ['api_context' => $context, 'data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            ScalarObjectType::class,
            [
                'data_class'    => Entity\ProductPrice::class,
                'data_property' => 'value',
                'metadata'      => $metadata,
                'config'        => $config
            ]
        );
        $form = $formBuilder->getForm();

        $context->expects(self::once())
            ->method('addAdditionalEntity')
            ->with(self::isInstanceOf(Entity\ProductPrice::class));

        $form->submit(['price' => 'testPriceValue']);
        self::assertTrue($form->isSynchronized());
        self::assertSame('testPriceValue', $data->getPrice()->getValue());
    }

    public function testCreateNestedObjectWhenValueIsNotSubmitted()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('value'));
        $metadata->addField(new FieldMetadata('currency'));

        $config = new EntityDefinitionConfig();
        $config->addField('value');
        $config->addField('currency');

        $context = $this->createMock(FormContext::class);

        $data = new Entity\Product();
        $formBuilder = $this->factory->createBuilder(
            FormType::class,
            $data,
            ['api_context' => $context, 'data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            ScalarObjectType::class,
            [
                'data_class'    => Entity\ProductPrice::class,
                'data_property' => 'value',
                'metadata'      => $metadata,
                'config'        => $config
            ]
        );
        $form = $formBuilder->getForm();

        $context->expects(self::once())
            ->method('addAdditionalEntity')
            ->with(self::isInstanceOf(Entity\ProductPrice::class));

        $form->submit([]);
        self::assertTrue($form->isSynchronized());
        self::assertNull($data->getPrice()->getValue());
    }

    public function testCreateNestedObjectWhenSubmittedValueIsNull()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('value'));
        $metadata->addField(new FieldMetadata('currency'));

        $config = new EntityDefinitionConfig();
        $config->addField('value');
        $config->addField('currency');

        $context = $this->createMock(FormContext::class);

        $data = new Entity\Product();
        $formBuilder = $this->factory->createBuilder(
            FormType::class,
            $data,
            ['api_context' => $context, 'data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            ScalarObjectType::class,
            [
                'data_class'    => Entity\ProductPrice::class,
                'data_property' => 'value',
                'metadata'      => $metadata,
                'config'        => $config
            ]
        );
        $form = $formBuilder->getForm();

        $context->expects(self::once())
            ->method('addAdditionalEntity')
            ->with(self::isInstanceOf(Entity\ProductPrice::class));

        $form->submit(['price' => null]);
        self::assertTrue($form->isSynchronized());
        self::assertNull($data->getPrice()->getValue());
    }

    public function testCreateNestedObjectWhenSubmittedValueIsNullAndRequiredOptionIsFalse()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('value'));
        $metadata->addField(new FieldMetadata('currency'));

        $config = new EntityDefinitionConfig();
        $config->addField('value');
        $config->addField('currency');

        $context = $this->createMock(FormContext::class);

        $data = new Entity\Product();
        $formBuilder = $this->factory->createBuilder(
            FormType::class,
            $data,
            ['api_context' => $context, 'data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            ScalarObjectType::class,
            [
                'data_class'    => Entity\ProductPrice::class,
                'data_property' => 'value',
                'metadata'      => $metadata,
                'config'        => $config,
                'required'      => false,
                'property_path' => 'nullablePrice'
            ]
        );
        $form = $formBuilder->getForm();

        $context->expects(self::never())
            ->method('addAdditionalEntity');

        $form->submit(['price' => null]);
        self::assertTrue($form->isSynchronized());
        self::assertNull($data->getNullablePrice());
    }

    public function testUpdateNestedObject()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('value'));
        $metadata->addField(new FieldMetadata('currency'));

        $config = new EntityDefinitionConfig();
        $config->addField('value');
        $config->addField('currency');

        $context = $this->createMock(FormContext::class);

        $data = new Entity\Product();
        $data->setPrice(new Entity\ProductPrice('oldPriceValue', 'oldPriceCurrency'));
        $formBuilder = $this->factory->createBuilder(
            FormType::class,
            $data,
            ['api_context' => $context, 'data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            ScalarObjectType::class,
            [
                'data_class'    => Entity\ProductPrice::class,
                'data_property' => 'value',
                'metadata'      => $metadata,
                'config'        => $config
            ]
        );
        $form = $formBuilder->getForm();

        $context->expects(self::once())
            ->method('addAdditionalEntity')
            ->with(self::isInstanceOf(Entity\ProductPrice::class));

        $form->submit(['price' => 'newPriceValue'], false);
        self::assertTrue($form->isSynchronized());
        self::assertSame('newPriceValue', $data->getPrice()->getValue());
        self::assertSame('oldPriceCurrency', $data->getPrice()->getCurrency());
    }

    public function testUpdateNestedObjectWhenValueIsNotSubmitted()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('value'));
        $metadata->addField(new FieldMetadata('currency'));

        $config = new EntityDefinitionConfig();
        $config->addField('value');
        $config->addField('currency');

        $context = $this->createMock(FormContext::class);

        $data = new Entity\Product();
        $data->setPrice(new Entity\ProductPrice('oldPriceValue', 'oldPriceCurrency'));
        $formBuilder = $this->factory->createBuilder(
            FormType::class,
            $data,
            ['api_context' => $context, 'data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            ScalarObjectType::class,
            [
                'data_class'    => Entity\ProductPrice::class,
                'data_property' => 'value',
                'metadata'      => $metadata,
                'config'        => $config
            ]
        );
        $form = $formBuilder->getForm();

        $context->expects(self::never())
            ->method('addAdditionalEntity');

        $form->submit([], false);
        self::assertTrue($form->isSynchronized());
        self::assertSame('oldPriceValue', $data->getPrice()->getValue());
        self::assertSame('oldPriceCurrency', $data->getPrice()->getCurrency());
    }

    public function testUpdateNestedObjectWhenSubmittedValueIsNull()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('value'));
        $metadata->addField(new FieldMetadata('currency'));

        $config = new EntityDefinitionConfig();
        $config->addField('value');
        $config->addField('currency');

        $context = $this->createMock(FormContext::class);

        $data = new Entity\Product();
        $data->setPrice(new Entity\ProductPrice('oldPriceValue', 'oldPriceCurrency'));
        $formBuilder = $this->factory->createBuilder(
            FormType::class,
            $data,
            ['api_context' => $context, 'data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            ScalarObjectType::class,
            [
                'data_class'    => Entity\ProductPrice::class,
                'data_property' => 'value',
                'metadata'      => $metadata,
                'config'        => $config
            ]
        );
        $form = $formBuilder->getForm();

        $context->expects(self::once())
            ->method('addAdditionalEntity')
            ->with(self::isInstanceOf(Entity\ProductPrice::class));

        $form->submit(['price' => null], false);
        self::assertTrue($form->isSynchronized());
        self::assertNull($data->getPrice()->getValue());
        self::assertEquals('oldPriceCurrency', $data->getPrice()->getCurrency());
    }

    public function testCreateNestedObjectWithRenamedField()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('renamedValue'))->setPropertyPath('value');
        $metadata->addField(new FieldMetadata('currency'));

        $config = new EntityDefinitionConfig();
        $config->addField('renamedValue')->setPropertyPath('value');
        $config->addField('currency');

        $context = $this->createMock(FormContext::class);

        $data = new Entity\Product();
        $formBuilder = $this->factory->createBuilder(
            FormType::class,
            $data,
            ['api_context' => $context, 'data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            ScalarObjectType::class,
            [
                'data_class'    => Entity\ProductPrice::class,
                'data_property' => 'renamedValue',
                'metadata'      => $metadata,
                'config'        => $config
            ]
        );
        $form = $formBuilder->getForm();

        $context->expects(self::once())
            ->method('addAdditionalEntity')
            ->with(self::isInstanceOf(Entity\ProductPrice::class));

        $form->submit(['price' => 'testPriceValue']);
        self::assertTrue($form->isSynchronized());
        self::assertSame('testPriceValue', $data->getPrice()->getValue());
    }

    public function testCreateNestedObjectWithFormOptions()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('renamedValue'));
        $metadata->addField(new FieldMetadata('currency'));

        $config = new EntityDefinitionConfig();
        $config->addField('renamedValue')->setFormOptions(['property_path' => 'value']);
        $config->addField('currency');

        $context = $this->createMock(FormContext::class);

        $data = new Entity\Product();
        $formBuilder = $this->factory->createBuilder(
            FormType::class,
            $data,
            ['api_context' => $context, 'data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            ScalarObjectType::class,
            [
                'data_class'    => Entity\ProductPrice::class,
                'data_property' => 'renamedValue',
                'metadata'      => $metadata,
                'config'        => $config
            ]
        );
        $form = $formBuilder->getForm();

        $context->expects(self::once())
            ->method('addAdditionalEntity')
            ->with(self::isInstanceOf(Entity\ProductPrice::class));

        $form->submit(['price' => 'testPriceValue']);
        self::assertTrue($form->isSynchronized());
        self::assertSame('testPriceValue', $data->getPrice()->getValue());
    }
}