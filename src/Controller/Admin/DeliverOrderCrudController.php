<?php

namespace DeliverOrderBundle\Controller\Admin;

use DeliverOrderBundle\Entity\DeliverOrder;
use DeliverOrderBundle\Enum\DeliverOrderStatus;
use DeliverOrderBundle\Enum\SourceType;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

#[AdminCrud(routePath: '/deliver/order', routeName: 'deliver_order')]
#[Autoconfigure(public: true)]
final class DeliverOrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DeliverOrder::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('发货单')
            ->setEntityLabelInPlural('发货单管理')
            ->setPageTitle('index', '发货单列表')
            ->setPageTitle('detail', '发货单详情')
            ->setPageTitle('new', '创建发货单')
            ->setPageTitle('edit', '编辑发货单')
            ->setHelp('index', '管理所有发货单信息')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['sn', 'sourceId', 'expressNumber', 'consigneeName', 'consigneePhone'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->setMaxLength(9999)->hideOnForm();

        yield TextField::new('sn', '发货单号')
            ->setColumns(6)
        ;

        yield ChoiceField::new('sourceType', '来源类型')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => SourceType::class])
            ->formatValue(fn ($value) => $this->formatEnumValue($value, SourceType::class))
            ->setColumns(6)
        ;

        yield TextField::new('sourceId', '来源ID')
            ->setColumns(6)
        ;

        yield TextField::new('expressCompany', '快递公司')
            ->setColumns(6)
        ;

        yield TextField::new('expressCode', '快递编码')
            ->setColumns(6)
        ;

        yield TextField::new('expressNumber', '快递单号')
            ->setColumns(6)
        ;

        yield TextField::new('consigneeName', '收货人')
            ->setColumns(6)
        ;

        yield TextField::new('consigneePhone', '联系电话')
            ->setColumns(6)
        ;

        yield TextField::new('consigneeAddress', '收货地址')
            ->setColumns(12)
            ->hideOnIndex()
        ;

        yield TextareaField::new('consigneeRemark', '收货备注')
            ->setColumns(12)
            ->hideOnIndex()
        ;

        yield ChoiceField::new('status', '状态')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => DeliverOrderStatus::class])
            ->formatValue(fn ($value) => $this->formatEnumValue($value, DeliverOrderStatus::class))
            ->setColumns(6)
        ;

        yield DateTimeField::new('shippedTime', '发货时间')
            ->setColumns(6)
            ->hideOnIndex()
        ;

        yield TextField::new('shippedBy', '发货人')
            ->setColumns(6)
            ->hideOnIndex()
        ;

        yield DateTimeField::new('receivedTime', '收货时间')
            ->setColumns(6)
            ->hideOnIndex()
        ;

        yield TextField::new('receivedBy', '收货人')
            ->setColumns(6)
            ->hideOnIndex()
        ;

        yield DateTimeField::new('rejectedTime', '拒收时间')
            ->setColumns(6)
            ->hideOnIndex()
        ;

        yield TextField::new('rejectedBy', '拒收操作人')
            ->setColumns(6)
            ->hideOnIndex()
        ;

        yield TextareaField::new('rejectReason', '拒收原因')
            ->setColumns(12)
            ->hideOnIndex()
        ;

        yield AssociationField::new('deliverStocks', '发货商品')
            ->setColumns(12)
            ->hideOnForm()
        ;

        yield TextField::new('createdBy', '创建人')
            ->setColumns(6)
            ->hideOnIndex()
        ;

        yield TextField::new('updatedBy', '更新人')
            ->setColumns(6)
            ->hideOnIndex()
        ;

        yield DateTimeField::new('createTime', '创建时间')->hideOnForm();
        yield DateTimeField::new('updatedAt', '更新时间')->hideOnForm();
    }

    private function formatEnumValue(mixed $value, string $enumClass): string
    {
        if (is_object($value) && is_a($value, $enumClass) && method_exists($value, 'getLabel')) {
            return $value->getLabel();
        }

        if (is_string($value)) {
            $enum = $enumClass::tryFrom($value);

            return $enum?->getLabel() ?? $value;
        }

        if (null === $value) {
            return '';
        }

        return is_scalar($value) ? (string) $value : '';
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        $sourceChoices = [];
        foreach (SourceType::cases() as $case) {
            $sourceChoices[$case->getLabel()] = $case->value;
        }

        $statusChoices = [];
        foreach (DeliverOrderStatus::cases() as $case) {
            $statusChoices[$case->getLabel()] = $case->value;
        }

        return $filters
            ->add(TextFilter::new('sn', '发货单号'))
            ->add(ChoiceFilter::new('sourceType', '来源类型')->setChoices($sourceChoices))
            ->add(TextFilter::new('sourceId', '来源ID'))
            ->add(TextFilter::new('expressCompany', '快递公司'))
            ->add(TextFilter::new('expressNumber', '快递单号'))
            ->add(TextFilter::new('consigneeName', '收货人'))
            ->add(ChoiceFilter::new('status', '状态')->setChoices($statusChoices))
            ->add(DateTimeFilter::new('shippedTime', '发货时间'))
            ->add(DateTimeFilter::new('receivedTime', '收货时间'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->leftJoin('entity.deliverStocks', 'deliverStocks')
            ->orderBy('entity.id', 'DESC')
        ;
    }
}
