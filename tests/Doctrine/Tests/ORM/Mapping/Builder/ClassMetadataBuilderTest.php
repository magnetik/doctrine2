<?php

namespace Doctrine\Tests\ORM\Mapping\Builder;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\Builder\DiscriminatorColumnMetadataBuilder;
use Doctrine\ORM\Mapping\ChangeTrackingPolicy;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FetchMode;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumnMetadata;
use Doctrine\ORM\Mapping\JoinTableMetadata;
use Doctrine\Tests\OrmTestCase;

/**
 * @group DDC-659
 */
class ClassMetadataBuilderTest extends OrmTestCase
{
    /**
     * @var ClassMetadata
     */
    private $cm;
    /**
     * @var ClassMetadataBuilder
     */
    private $builder;

    public function setUp()
    {
        $this->cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $this->cm->initializeReflection(new RuntimeReflectionService());
        $this->builder = new ClassMetadataBuilder($this->cm);
    }

    /**
     * @group embedded
     */
    public function testSetMappedSuperClass()
    {
        self::assertIsFluent($this->builder->setMappedSuperClass());
        self::assertTrue($this->cm->isMappedSuperclass);
        self::assertFalse($this->cm->isEmbeddedClass);
    }

    /**
     * @group embedded
     */
    public function testSetEmbedable()
    {
        self::assertIsFluent($this->builder->setEmbeddable());
        self::assertTrue($this->cm->isEmbeddedClass);
        self::assertFalse($this->cm->isMappedSuperclass);
    }

    /**
     * @group embedded
     */
    public function testAddEmbeddedWithOnlyRequiredParams()
    {
        self::assertIsFluent($this->builder->addEmbedded('name', 'Doctrine\Tests\Models\ValueObjects\Name'));

        self::assertEquals(
            array(
                'name' => array(
                    'class'          => 'Doctrine\Tests\Models\ValueObjects\Name',
                    'columnPrefix'   => null,
                    'declaredField'  => null,
                    'originalField'  => null,
                    'declaringClass' => $this->cm,
                )
            ),
            $this->cm->embeddedClasses
        );
    }

    /**
     * @group embedded
     */
    public function testAddEmbeddedWithPrefix()
    {
        self::assertIsFluent($this->builder->addEmbedded('name', 'Doctrine\Tests\Models\ValueObjects\Name', 'nm_'));

        self::assertEquals(
            array(
                'name' => array(
                    'class'          => 'Doctrine\Tests\Models\ValueObjects\Name',
                    'columnPrefix'   => 'nm_',
                    'declaredField'  => null,
                    'originalField'  => null,
                    'declaringClass' => $this->cm,
                )
            ),
            $this->cm->embeddedClasses
        );
    }

    /**
     * @group embedded
     */
    public function testCreateEmbeddedWithoutExtraParams()
    {
        $embeddedBuilder = $this->builder->createEmbedded('name', 'Doctrine\Tests\Models\ValueObjects\Name');

        self::assertInstanceOf('Doctrine\ORM\Mapping\Builder\EmbeddedBuilder', $embeddedBuilder);
        self::assertFalse(isset($this->cm->embeddedClasses['name']));

        self::assertIsFluent($embeddedBuilder->build());
        self::assertEquals(
            array(
                'class'          => 'Doctrine\Tests\Models\ValueObjects\Name',
                'columnPrefix'   => null,
                'declaredField'  => null,
                'originalField'  => null,
                'declaringClass' => $this->cm,
            ),
            $this->cm->embeddedClasses['name']
        );
    }

    /**
     * @group embedded
     */
    public function testCreateEmbeddedWithColumnPrefix()
    {
        $embeddedBuilder = $this->builder->createEmbedded('name', 'Doctrine\Tests\Models\ValueObjects\Name');

        self::assertEquals($embeddedBuilder, $embeddedBuilder->setColumnPrefix('nm_'));

        self::assertIsFluent($embeddedBuilder->build());
        self::assertEquals(
            array(
                'class'          => 'Doctrine\Tests\Models\ValueObjects\Name',
                'columnPrefix'   => 'nm_',
                'declaredField'  => null,
                'originalField'  => null,
                'declaringClass' => $this->cm,
            ),
            $this->cm->embeddedClasses['name']
        );
    }

    public function testSetCustomRepositoryClass()
    {
        self::assertIsFluent($this->builder->setCustomRepositoryClass('Doctrine\Tests\Models\CMS\CmsGroup'));
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsGroup', $this->cm->customRepositoryClassName);
    }

    public function testSetReadOnly()
    {
        self::assertIsFluent($this->builder->setReadOnly());
        self::assertTrue($this->cm->isReadOnly);
    }

    public function testSetInheritanceJoined()
    {
        self::assertIsFluent($this->builder->setJoinedTableInheritance());
        self::assertEquals(InheritanceType::JOINED, $this->cm->inheritanceType);
    }

    public function testSetInheritanceSingleTable()
    {
        self::assertIsFluent($this->builder->setSingleTableInheritance());
        self::assertEquals(InheritanceType::SINGLE_TABLE, $this->cm->inheritanceType);
    }

    public function testSetDiscriminatorColumn()
    {
        $discriminatorColumnBuilder = (new DiscriminatorColumnMetadataBuilder())
            ->withColumnName('discr')
            ->withLength(124)
        ;

        self::assertIsFluent($this->builder->setDiscriminatorColumn($discriminatorColumnBuilder->build()));
        self::assertNotNull($this->cm->discriminatorColumn);

        $discrColumn = $this->cm->discriminatorColumn;

        self::assertEquals('CmsUser', $discrColumn->getTableName());
        self::assertEquals('discr', $discrColumn->getColumnName());
        self::assertEquals('string', $discrColumn->getTypeName());
        self::assertEquals(124, $discrColumn->getLength());
    }

    public function testAddDiscriminatorMapClass()
    {
        self::assertIsFluent($this->builder->addDiscriminatorMapClass('test', 'Doctrine\Tests\Models\CMS\CmsUser'));
        self::assertIsFluent($this->builder->addDiscriminatorMapClass('test2', 'Doctrine\Tests\Models\CMS\CmsGroup'));

        self::assertEquals(
            array(
                'test' => 'Doctrine\Tests\Models\CMS\CmsUser',
                'test2' => 'Doctrine\Tests\Models\CMS\CmsGroup'
            ),
            $this->cm->discriminatorMap
        );
        self::assertEquals('test', $this->cm->discriminatorValue);
    }

    public function testChangeTrackingPolicyExplicit()
    {
        self::assertIsFluent($this->builder->setChangeTrackingPolicyDeferredExplicit());
        self::assertEquals(ChangeTrackingPolicy::DEFERRED_EXPLICIT, $this->cm->changeTrackingPolicy);
    }

    public function testChangeTrackingPolicyNotify()
    {
        self::assertIsFluent($this->builder->setChangeTrackingPolicyNotify());
        self::assertEquals(ChangeTrackingPolicy::NOTIFY, $this->cm->changeTrackingPolicy);
    }

    public function testAddField()
    {
        self::assertNull($this->cm->getProperty('name'));

        self::assertIsFluent($this->builder->addProperty('name', 'string'));

        self::assertNotNull($this->cm->getProperty('name'));

        $property = $this->cm->getProperty('name');

        self::assertEquals('name', $property->getName());
        self::assertEquals($this->cm, $property->getDeclaringClass());
        self::assertEquals('string', $property->getTypeName());
        self::assertEquals('CmsUser', $property->getTableName());
        self::assertEquals('name', $property->getColumnName());
    }

    public function testCreateField()
    {
        $fieldBuilder = $this->builder->createField('name', 'string');

        self::assertInstanceOf('Doctrine\ORM\Mapping\Builder\FieldBuilder', $fieldBuilder);
        self::assertNull($this->cm->getProperty('name'));

        self::assertIsFluent($fieldBuilder->build());

        self::assertNotNull($this->cm->getProperty('name'));

        $property = $this->cm->getProperty('name');

        self::assertEquals('name', $property->getName());
        self::assertEquals($this->cm, $property->getDeclaringClass());
        self::assertEquals('string', $property->getTypeName());
        self::assertEquals('CmsUser', $property->getTableName());
        self::assertEquals('name', $property->getColumnName());
    }

    public function testCreateVersionedField()
    {
        $this->builder->createField('name', 'integer')
            ->columnName('username')
            ->length(124)
            ->nullable()
            ->columnDefinition('foobar')
            ->unique()
            ->isVersionField()
            ->build();

        self::assertNotNull($this->cm->getProperty('name'));

        $property = $this->cm->getProperty('name');

        self::assertEquals('name', $property->getName());
        self::assertEquals($this->cm, $property->getDeclaringClass());
        self::assertEquals('integer', $property->getTypeName());
        self::assertEquals('CmsUser', $property->getTableName());
        self::assertEquals('username', $property->getColumnName());
        self::assertEquals('foobar', $property->getColumnDefinition());
        self::assertEquals(124, $property->getLength());
        self::assertTrue($property->isNullable());
        self::assertTrue($property->isUnique());
        self::assertEquals(['default' => 1], $property->getOptions());
    }

    public function testCreatePrimaryField()
    {
        $this->builder->createField('id', 'integer')
            ->makePrimaryKey()
            ->generatedValue()
            ->build();

        self::assertNotNull($this->cm->getProperty('id'));

        $property = $this->cm->getProperty('id');

        self::assertEquals(array('id'), $this->cm->identifier);
        self::assertEquals('id', $property->getName());
        self::assertEquals($this->cm, $property->getDeclaringClass());
        self::assertEquals('integer', $property->getTypeName());
        self::assertEquals('CmsUser', $property->getTableName());
        self::assertEquals('id', $property->getColumnName());
        self::assertTrue($property->isPrimaryKey());
    }

    public function testCreateUnsignedOptionField()
    {
        $this->builder->createField('state', 'integer')
            ->option('unsigned', true)
            ->build();

        self::assertNotNull($this->cm->getProperty('state'));

        $property = $this->cm->getProperty('state');

        self::assertEquals('state', $property->getName());
        self::assertEquals($this->cm, $property->getDeclaringClass());
        self::assertEquals('integer', $property->getTypeName());
        self::assertEquals('CmsUser', $property->getTableName());
        self::assertEquals('state', $property->getColumnName());
        self::assertEquals(['unsigned' => true], $property->getOptions());
    }

    public function testAddLifecycleEvent()
    {
        $this->builder->addLifecycleEvent('getStatus', 'postLoad');

        self::assertEquals(array('postLoad' => array('getStatus')), $this->cm->lifecycleCallbacks);
    }

    public function testCreateManyToOne()
    {
        self::assertIsFluent(
            $this->builder->createManyToOne('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                  ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                  ->cascadeAll()
                  ->fetchExtraLazy()
                  ->build()
        );

        $joinColumn = new JoinColumnMetadata();
        $joinColumn->setTableName('CmsUser');
        $joinColumn->setColumnName('group_id');
        $joinColumn->setReferencedColumnName('id');
        $joinColumn->setOnDelete('CASCADE');

        self::assertEquals(
            array(
                'groups' => array (
                    'fieldName' => 'groups',
                    'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                    'cascade' => array (
                        0 => 'remove',
                        1 => 'persist',
                        2 => 'refresh',
                        3 => 'merge',
                        4 => 'detach',
                    ),
                    'fetch' => FetchMode::EXTRA_LAZY,
                    'joinColumns' => [$joinColumn],
                    'type' => 2,
                    'mappedBy' => NULL,
                    'inversedBy' => NULL,
                    'isOwningSide' => true,
                    'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                    'orphanRemoval' => false,
                    'declaringClass' => $this->cm,
                  ),
            ),
            $this->cm->associationMappings
        );
    }

    public function testCreateManyToOneWithIdentity()
    {
        self::assertIsFluent(
            $this
                ->builder
                ->createManyToOne('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                ->cascadeAll()
                ->fetchExtraLazy()
                ->makePrimaryKey()
                ->build()
        );

        $joinColumn = new JoinColumnMetadata();
        $joinColumn->setTableName('CmsUser');
        $joinColumn->setColumnName('group_id');
        $joinColumn->setReferencedColumnName('id');
        $joinColumn->setOnDelete('CASCADE');

        self::assertEquals(
            array(
                'groups' => array(
                    'fieldName' => 'groups',
                    'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                    'cascade' => array(
                        0 => 'remove',
                        1 => 'persist',
                        2 => 'refresh',
                        3 => 'merge',
                        4 => 'detach',
                    ),
                    'fetch' => FetchMode::EXTRA_LAZY,
                    'joinColumns' => [$joinColumn],
                    'type' => 2,
                    'mappedBy' => NULL,
                    'inversedBy' => NULL,
                    'isOwningSide' => true,
                    'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                    'orphanRemoval' => false,
                    'declaringClass' => $this->cm,
                    'id' => true,
                ),
            ),
            $this->cm->associationMappings
        );
    }

    public function testCreateOneToOne()
    {
        self::assertIsFluent(
            $this->builder->createOneToOne('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                ->cascadeAll()
                ->fetchExtraLazy()
                ->build()
        );

        $joinColumn = new JoinColumnMetadata();
        $joinColumn->setTableName('CmsUser');
        $joinColumn->setColumnName('group_id');
        $joinColumn->setReferencedColumnName('id');
        $joinColumn->setOnDelete('CASCADE');
        $joinColumn->setUnique(true);

        self::assertEquals(
            array(
                'groups' => array (
                    'fieldName' => 'groups',
                    'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                    'cascade' => array (
                        0 => 'remove',
                        1 => 'persist',
                        2 => 'refresh',
                        3 => 'merge',
                        4 => 'detach',
                    ),
                    'fetch' => FetchMode::EXTRA_LAZY,
                    'joinColumns' => [$joinColumn],
                    'type' => 1,
                    'mappedBy' => NULL,
                    'inversedBy' => NULL,
                    'isOwningSide' => true,
                    'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                    'orphanRemoval' => false,
                    'declaringClass' => $this->cm,
                ),
            ),
            $this->cm->associationMappings
        );
    }

    public function testCreateOneToOneWithIdentity()
    {
        self::assertIsFluent(
            $this->builder->createOneToOne('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                ->cascadeAll()
                ->fetchExtraLazy()
                ->makePrimaryKey()
                ->build()
        );

        $joinColumn = new JoinColumnMetadata();
        $joinColumn->setTableName('CmsUser');
        $joinColumn->setColumnName('group_id');
        $joinColumn->setReferencedColumnName('id');
        $joinColumn->setOnDelete('CASCADE');

        self::assertEquals(
            array(
                'groups' => array(
                    'fieldName' => 'groups',
                    'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                    'cascade' => array(
                        0 => 'remove',
                        1 => 'persist',
                        2 => 'refresh',
                        3 => 'merge',
                        4 => 'detach',
                    ),
                    'fetch' => FetchMode::EXTRA_LAZY,
                    'id' => true,
                    'joinColumns' => [$joinColumn],
                    'type' => 1,
                    'mappedBy' => NULL,
                    'inversedBy' => NULL,
                    'isOwningSide' => true,
                    'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                    'orphanRemoval' => false,
                    'declaringClass' => $this->cm,
                ),
            ),
            $this->cm->associationMappings
        );
    }

    public function testThrowsExceptionOnCreateOneToOneWithIdentityOnInverseSide()
    {
        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);

        $this
            ->builder
            ->createOneToOne('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
            ->mappedBy('test')
            ->fetchExtraLazy()
            ->makePrimaryKey()
            ->build();
    }

    public function testCreateManyToMany()
    {
        self::assertIsFluent(
            $this->builder->createManyToMany('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                  ->setJoinTable('groups_users')
                  ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                  ->addInverseJoinColumn('user_id', 'id')
                  ->cascadeAll()
                  ->fetchExtraLazy()
                  ->build()
        );

        $joinColumn = new JoinColumnMetadata();
        $joinColumn->setColumnName('group_id');
        $joinColumn->setReferencedColumnName('id');
        $joinColumn->setOnDelete('CASCADE');

        $inverseJoinColumn = new JoinColumnMetadata();
        $inverseJoinColumn->setColumnName('user_id');
        $inverseJoinColumn->setReferencedColumnName('id');

        $joinTable = new JoinTableMetadata();
        $joinTable->setName('groups_users');
        $joinTable->addJoinColumn($joinColumn);
        $joinTable->addInverseJoinColumn($inverseJoinColumn);

        self::assertEquals(
            array(
                'groups' => array(
                    'fieldName' => 'groups',
                    'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                    'cascade' =>
                    array(
                        0 => 'remove',
                        1 => 'persist',
                        2 => 'refresh',
                        3 => 'merge',
                        4 => 'detach',
                    ),
                    'fetch' => FetchMode::EXTRA_LAZY,
                    'joinTable' => $joinTable,
                    'type' => 8,
                    'mappedBy' => NULL,
                    'inversedBy' => NULL,
                    'isOwningSide' => true,
                    'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                    'orphanRemoval' => false,
                    'declaringClass' => $this->cm,
                ),
            ),
            $this->cm->associationMappings
        );
    }

    public function testThrowsExceptionOnCreateManyToManyWithIdentity()
    {
        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);

        $this->builder->createManyToMany('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
              ->makePrimaryKey()
              ->setJoinTable('groups_users')
              ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
              ->addInverseJoinColumn('user_id', 'id')
              ->cascadeAll()
              ->fetchExtraLazy()
              ->build();
    }

    public function testCreateOneToMany()
    {
        self::assertIsFluent(
            $this->builder->createOneToMany('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                ->mappedBy('test')
                ->setOrderBy(array('test'))
                ->setIndexBy('test')
                ->build()
        );

        self::assertEquals(
            array(
                'groups' => array(
                    'fieldName' => 'groups',
                    'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                    'mappedBy' => 'test',
                    'orderBy' => array(
                        0 => 'test',
                    ),
                    'indexBy' => 'test',
                    'type' => 4,
                    'inversedBy' => NULL,
                    'isOwningSide' => false,
                    'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                    'fetch' => FetchMode::LAZY,
                    'cascade' => array(),
                    'orphanRemoval' => false,
                    'declaringClass' => $this->cm,
                ),
            ),
            $this->cm->associationMappings
        );
    }

    public function testThrowsExceptionOnCreateOneToManyWithIdentity()
    {
        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);

        $this->builder->createOneToMany('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
            ->makePrimaryKey()
            ->mappedBy('test')
            ->setOrderBy(array('test'))
            ->setIndexBy('test')
            ->build();
    }

    public function testOrphanRemovalOnCreateOneToOne()
    {
        self::assertIsFluent(
            $this->builder
                ->createOneToOne('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                ->orphanRemoval()
                ->build()
        );

        $joinColumn = new JoinColumnMetadata();
        $joinColumn->setTableName('CmsUser');
        $joinColumn->setColumnName('group_id');
        $joinColumn->setReferencedColumnName('id');
        $joinColumn->setOnDelete('CASCADE');
        $joinColumn->setUnique(true);

        self::assertEquals(
            array(
                'groups' => array(
                    'fieldName' => 'groups',
                    'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                    'cascade' => array (
                        0 => 'remove'
                    ),
                    'fetch' => FetchMode::LAZY,
                    'joinColumns' => [$joinColumn],
                    'type' => 1,
                    'mappedBy' => NULL,
                    'inversedBy' => NULL,
                    'isOwningSide' => true,
                    'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                    'orphanRemoval' => true,
                    'declaringClass' => $this->cm,
                ),
            ),
            $this->cm->associationMappings
        );
    }

    public function testOrphanRemovalOnCreateOneToMany()
    {
        self::assertIsFluent(
            $this->builder
                ->createOneToMany('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                ->mappedBy('test')
                ->orphanRemoval()
                ->build()
        );

        self::assertEquals(
            array(
                'groups' => array(
                    'fieldName' => 'groups',
                    'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                    'mappedBy' => 'test',
                    'type' => 4,
                    'inversedBy' => NULL,
                    'isOwningSide' => false,
                    'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                    'fetch' => FetchMode::LAZY,
                    'cascade' => array(
                        0 => 'remove'
                    ),
                    'orphanRemoval' => true,
                    'declaringClass' => $this->cm,
                ),
            ),
            $this->cm->associationMappings
        );
    }

    public function testExceptionOnOrphanRemovalOnManyToOne()
    {
        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);

        $this->builder
            ->createManyToOne('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
            ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
            ->orphanRemoval()
            ->build();
    }

    public function testOrphanRemovalOnManyToMany()
    {
        $this->builder
            ->createManyToMany('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
            ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
            ->orphanRemoval()
            ->build();

        $joinColumn = new JoinColumnMetadata();
        $joinColumn->setColumnName('group_id');
        $joinColumn->setReferencedColumnName('id');
        $joinColumn->setOnDelete('CASCADE');

        $inverseJoinColumn = new JoinColumnMetadata();
        $inverseJoinColumn->setColumnName('cmsgroup_id');
        $inverseJoinColumn->setReferencedColumnName('id');
        $inverseJoinColumn->setOnDelete('CASCADE');

        $joinTable = new JoinTableMetadata();
        $joinTable->setName('cmsuser_cmsgroup');
        $joinTable->addJoinColumn($joinColumn);
        $joinTable->addInverseJoinColumn($inverseJoinColumn);

        self::assertEquals(
            array(
                'groups' => array(
                    'fieldName' => 'groups',
                    'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                    'cascade' => array(),
                    'fetch' => FetchMode::LAZY,
                    'joinTable' => $joinTable,
                    'type' => 8,
                    'mappedBy' => NULL,
                    'inversedBy' => NULL,
                    'isOwningSide' => true,
                    'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                    'orphanRemoval' => true,
                    'declaringClass' => $this->cm,
                ),
            ),
            $this->cm->associationMappings
        );
    }

    public function assertIsFluent($ret)
    {
        self::assertSame($this->builder, $ret, "Return Value has to be same instance as used builder");
    }
}