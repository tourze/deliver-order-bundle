<?php

namespace DeliverOrderBundle\Controller\Admin;

use DeliverOrderBundle\Entity\DeliverStock;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[AdminCrud(routePath: '/deliver/stock', routeName: 'deliver_stock')]
#[Autoconfigure(public: true)]
final class DeliverStockCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DeliverStock::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('发货商品')
            ->setEntityLabelInPlural('发货商品管理')
            ->setPageTitle('index', '发货商品列表')
            ->setPageTitle('detail', '发货商品详情')
            ->setPageTitle('new', '添加发货商品')
            ->setPageTitle('edit', '编辑发货商品')
            ->setHelp('index', '管理发货单中的商品明细')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['skuId', 'skuCode', 'skuName', 'batchNo', 'serialNo'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->setMaxLength(9999)->hideOnForm();

        yield AssociationField::new('deliverOrder', '发货单')
            ->setColumns(6)
        ;

        yield TextField::new('skuId', 'SKU ID')
            ->setColumns(6)
        ;

        yield TextField::new('skuCode', 'SKU编码')
            ->setColumns(6)
        ;

        yield TextField::new('skuName', 'SKU名称')
            ->setColumns(6)
        ;

        yield IntegerField::new('quantity', '数量')
            ->setColumns(6)
        ;

        yield TextField::new('batchNo', '批次号')
            ->setColumns(6)
            ->hideOnIndex()
        ;

        yield TextField::new('serialNo', '序列号')
            ->setColumns(6)
            ->hideOnIndex()
        ;

        yield TextareaField::new('remark', '备注')
            ->setColumns(12)
            ->hideOnIndex()
        ;

        yield BooleanField::new('received', '是否已收货')
            ->setColumns(6)
        ;

        yield DateTimeField::new('receivedTime', '收货时间')
            ->setColumns(6)
            ->hideOnIndex()
        ;

        yield DateTimeField::new('createTime', '创建时间')->hideOnForm();
        yield DateTimeField::new('updatedAt', '更新时间')->hideOnForm();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('deliverOrder', '发货单'))
            ->add(TextFilter::new('skuId', 'SKU ID'))
            ->add(TextFilter::new('skuCode', 'SKU编码'))
            ->add(TextFilter::new('skuName', 'SKU名称'))
            ->add(NumericFilter::new('quantity', '数量'))
            ->add(TextFilter::new('batchNo', '批次号'))
            ->add(TextFilter::new('serialNo', '序列号'))
            ->add(BooleanFilter::new('received', '是否已收货'))
            ->add(DateTimeFilter::new('receivedTime', '收货时间'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->leftJoin('entity.deliverOrder', 'deliverOrder')
            ->addSelect('deliverOrder')
            ->orderBy('entity.id', 'DESC')
        ;
    }
}
