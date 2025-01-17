<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\AdminBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Controller\CRUDController;
use Sonata\AdminBundle\DependencyInjection\Configuration;
use Sonata\AdminBundle\Tests\Fixtures\Controller\FooAdminController;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    use ExpectDeprecationTrait;

    public function testOptions(): void
    {
        $config = $this->process([]);

        static::assertTrue($config['options']['html5_validate']);
        static::assertNull($config['options']['pager_links']);
        static::assertTrue($config['options']['confirm_exit']);
        static::assertFalse($config['options']['js_debug']);
        static::assertTrue($config['options']['use_icheck']);
        static::assertSame('bundles/sonataadmin/default_mosaic_image.png', $config['options']['mosaic_background']);
        static::assertSame('default', $config['options']['default_group']);
        static::assertSame('SonataAdminBundle', $config['options']['default_label_catalogue']);
        static::assertSame('fa fa-folder', $config['options']['default_icon']);
    }

    public function testBreadcrumbsChildRouteDefaultsToEdit(): void
    {
        $config = $this->process([]);

        static::assertSame('edit', $config['breadcrumbs']['child_admin_route']);
    }

    public function testOptionsWithInvalidFormat(): void
    {
        $this->expectException(InvalidTypeException::class);

        $this->process([[
            'options' => [
                'html5_validate' => '1',
            ],
        ]]);
    }

    /**
     * NEXT_MAJOR: Remove this test.
     *
     * @group legacy
     */
    public function testCustomTemplatesPerAdmin(): void
    {
        $config = $this->process([[
            'admin_services' => [
                'my_admin_id' => [
                    'templates' => [
                        'form' => ['form.twig.html', 'form_extra.twig.html'],
                        'view' => ['user_block' => '@SonataAdmin/mycustomtemplate.html.twig'],
                        'filter' => [],
                    ],
                ],
            ],
        ]]);

        static::assertSame('@SonataAdmin/mycustomtemplate.html.twig', $config['admin_services']['my_admin_id']['templates']['view']['user_block']);
    }

    /**
     * NEXT_MAJOR: Remove this test.
     *
     * @group legacy
     */
    public function testAdminServicesDefault(): void
    {
        $config = $this->process([[
            'admin_services' => ['my_admin_id' => []],
        ]]);

        static::assertSame([
            'model_manager' => null,
            'data_source' => null,
            'field_description_factory' => null,
            'form_contractor' => null,
            'show_builder' => null,
            'list_builder' => null,
            'datagrid_builder' => null,
            'translator' => null,
            'configuration_pool' => null,
            'route_generator' => null,
            'validator' => null,
            'security_handler' => null,
            'label' => null,
            'menu_factory' => null,
            'route_builder' => null,
            'label_translator_strategy' => null,
            'pager_type' => null,
            'templates' => [
                'form' => [],
                'filter' => [],
                'view' => [],
            ],
        ], $config['admin_services']['my_admin_id']);
    }

    public function testDefaultAdminServicesDefault(): void
    {
        $config = $this->process([[
            'default_admin_services' => [],
        ]]);

        static::assertSame([
            'model_manager' => null,
            'data_source' => null,
            'field_description_factory' => null,
            'form_contractor' => null,
            'show_builder' => null,
            'list_builder' => null,
            'datagrid_builder' => null,
            'translator' => null,
            'configuration_pool' => null,
            'route_generator' => null,
            'security_handler' => null,
            'menu_factory' => null,
            'route_builder' => null,
            'label_translator_strategy' => null,
            'pager_type' => null,
        ], $config['default_admin_services']);
    }

    public function testDashboardWithoutRoles(): void
    {
        $config = $this->process([]);

        static::assertEmpty($config['dashboard']['blocks'][0]['roles']);
    }

    public function testDashboardWithRoles(): void
    {
        $config = $this->process([[
            'dashboard' => [
                'blocks' => [[
                    'roles' => ['ROLE_ADMIN'],
                    'type' => 'my.type',
                ]],
            ],
        ]]);

        static::assertSame($config['dashboard']['blocks'][0]['roles'], ['ROLE_ADMIN']);
    }

    public function testDashboardGroups(): void
    {
        $config = $this->process([[
            'dashboard' => [
                'groups' => [
                    'bar' => [
                        'label' => 'foo',
                        'icon' => '<i class="fa fa-edit"></i>',
                        'items' => [
                            'item1',
                            'item2',
                            [
                                'label' => 'fooLabel',
                                'route' => 'fooRoute',
                                'route_params' => ['bar' => 'foo'],
                                'route_absolute' => true,
                            ],
                            [
                                'label' => 'barLabel',
                                'route' => 'barRoute',
                            ],
                        ],
                    ],
                ],
            ],
        ]]);

        static::assertCount(4, $config['dashboard']['groups']['bar']['items']);
        static::assertSame(
            $config['dashboard']['groups']['bar']['items'][0],
            [
                'admin' => 'item1',
                'roles' => [],
                'route_params' => [],
                'route_absolute' => false,
            ]
        );
        static::assertSame(
            $config['dashboard']['groups']['bar']['items'][1],
            [
                'admin' => 'item2',
                'roles' => [],
                'route_params' => [],
                'route_absolute' => false,
            ]
        );
        static::assertSame(
            $config['dashboard']['groups']['bar']['items'][2],
            [
                'label' => 'fooLabel',
                'route' => 'fooRoute',
                'route_params' => ['bar' => 'foo'],
                'route_absolute' => true,
                'roles' => [],
            ]
        );
        static::assertSame(
            $config['dashboard']['groups']['bar']['items'][3],
            [
                'label' => 'barLabel',
                'route' => 'barRoute',
                'roles' => [],
                'route_params' => [],
                'route_absolute' => false,
            ]
        );
    }

    /**
     * NEXT_MAJOR: Remove this test.
     *
     * @group legacy
     */
    public function testDashboardGroupsWithNullLabel(): void
    {
        $this->expectDeprecation('Passing a null label is deprecated since sonata-project/admin-bundle 3.77.');

        $config = $this->process([[
            'dashboard' => [
                'groups' => [
                    'bar' => [
                        'label' => 'foo',
                        'icon' => '<i class="fa fa-edit"></i>',
                        'items' => [
                            [
                                'label' => null,
                                'route' => 'barRoute',
                            ],
                        ],
                    ],
                ],
            ],
        ]]);

        static::assertCount(1, $config['dashboard']['groups']['bar']['items']);
        static::assertSame(
            $config['dashboard']['groups']['bar']['items'][0],
            [
                'label' => '',
                'route' => 'barRoute',
                'roles' => [],
                'route_params' => [],
                'route_absolute' => false,
            ]
        );
    }

    public function testDashboardGroupsWithNoRoute(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected parameter "route" for array items');

        $this->process([[
            'dashboard' => [
                'groups' => [
                    'bar' => [
                        'label' => 'foo',
                        'icon' => '<i class="fa fa-edit"></i>',
                        'items' => [
                            ['label' => 'noRoute'],
                        ],
                    ],
                ],
            ],
        ]]);
    }

    public function testDashboardGroupsWithNoLabel(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected parameter "label" for array items');

        $this->process([[
            'dashboard' => [
                'groups' => [
                    'bar' => [
                        'label' => 'foo',
                        'icon' => '<i class="fa fa-edit"></i>',
                        'items' => [
                            ['route' => 'noLabel'],
                        ],
                    ],
                ],
            ],
        ]]);
    }

    public function testSecurityConfigurationDefaults(): void
    {
        $config = $this->process([[]]);

        static::assertSame('ROLE_SONATA_ADMIN', $config['security']['role_admin']);
        static::assertSame('ROLE_SUPER_ADMIN', $config['security']['role_super_admin']);
    }

    public function testExtraAssetsDefaults(): void
    {
        $config = $this->process([[]]);

        static::assertSame([], $config['assets']['extra_stylesheets']);
        static::assertSame([], $config['assets']['extra_javascripts']);
    }

    public function testRemoveAssetsDefaults(): void
    {
        $config = $this->process([[]]);

        static::assertSame([], $config['assets']['remove_stylesheets']);
        static::assertSame([], $config['assets']['remove_javascripts']);
    }

    public function testDefaultControllerIsCRUDController(): void
    {
        $config = $this->process([]);

        static::assertSame(CRUDController::class, $config['default_controller']);
    }

    public function testSettingDefaultController(): void
    {
        $config = $this->process([[
            'default_controller' => FooAdminController::class,
        ]]);

        static::assertSame(FooAdminController::class, $config['default_controller']);
    }

    /**
     * Processes an array of configurations and returns a compiled version.
     *
     * @param array $configs An array of raw configurations
     *
     * @return array A normalized array
     */
    protected function process($configs): array
    {
        $processor = new Processor();

        return $processor->processConfiguration(new Configuration(), $configs);
    }
}
