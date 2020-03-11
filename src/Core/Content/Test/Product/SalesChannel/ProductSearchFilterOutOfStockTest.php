<?php declare(strict_types=1);

namespace Shopware\Core\Content\Test\Product\SalesChannel;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingGateway;
use Shopware\Core\Content\Product\SalesChannel\Search\ProductSearchGateway;
use Shopware\Core\Content\Property\PropertyGroupCollection;
use Shopware\Core\Content\Test\Product\SalesChannel\Fixture\ListingTestData;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\EntityResult;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Request;

class ProductSearchFilterOutOfStockTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var ProductListingGateway
     */
    private $searchGateway;

    /**
     * @var string
     */
    private $categoryId;

    /**
     * @var ListingTestData
     */
    private $testData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchGateway = $this->getContainer()->get(ProductSearchGateway::class);

        $parent = $this->getContainer()->get(Connection::class)->fetchColumn(
            'SELECT LOWER(HEX(navigation_category_id)) FROM sales_channel WHERE id = :id',
            ['id' => Uuid::fromHexToBytes(Defaults::SALES_CHANNEL)]
        );

        $this->categoryId = Uuid::randomHex();

        $this->getContainer()->get('category.repository')
            ->create([['id' => $this->categoryId, 'name' => 'test', 'parentId' => $parent]], Context::createDefaultContext());

        $this->testData = new ListingTestData();

        $this->insertOptions();

        $this->insertProducts();
    }

    public function testListingWithFilterDisabled(): void
    {
        // disable hideCloseoutProductsWhenOutOfStock filter
        $this->getContainer()->get(SystemConfigService::class)
            ->set('core.listing.hideCloseoutProductsWhenOutOfStock', false);

        $request = new Request();

        $request->query->set('search', ['product']);

        $context = $this->getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), Defaults::SALES_CHANNEL);

        $request->attributes->set('_route_params', [
            'navigationId' => $this->categoryId,
        ]);

        $listing = $this->searchGateway->search($request, $context);

        static::assertSame(5, $listing->getTotal());
        static::assertFalse($listing->has($this->testData->getId('product1')));
        static::assertFalse($listing->has($this->testData->getId('product2')));

        // product 1 has all available variants
        static::assertTrue($listing->has($this->testData->getId('product1-red')));
        static::assertTrue($listing->has($this->testData->getId('product1-green')));
        static::assertTrue($listing->has($this->testData->getId('product1-blue')));

        // product 2 has all available variants
        static::assertTrue($listing->has($this->testData->getId('product2-green')));
        static::assertTrue($listing->has($this->testData->getId('product2-red')));

        /** @var EntityResult $result */
        $result = $listing->getAggregations()->get('properties');

        /** @var PropertyGroupCollection $options */
        $options = $result->getEntities();

        $ids = array_keys($options->getOptionIdMap());

        static::assertContains($this->testData->getId('green'), $ids);
        static::assertContains($this->testData->getId('red'), $ids);
        static::assertContains($this->testData->getId('blue'), $ids);
    }

    public function testListingWithFilterEnabled(): void
    {
        // enable hideCloseoutProductsWhenOutOfStock filter
        $this->getContainer()->get(SystemConfigService::class)
            ->set('core.listing.hideCloseoutProductsWhenOutOfStock', true);

        $request = new Request();

        $request->query->set('search', ['product']);

        $context = $this->getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), Defaults::SALES_CHANNEL);

        $request->attributes->set('_route_params', [
            'navigationId' => $this->categoryId,
        ]);

        $listing = $this->searchGateway->search($request, $context);

        static::assertSame(2, $listing->getTotal());
        static::assertFalse($listing->has($this->testData->getId('product1')));
        static::assertFalse($listing->has($this->testData->getId('product2')));

        // product 1 has only 2 available variants
        static::assertTrue($listing->has($this->testData->getId('product1-red')));
        static::assertTrue($listing->has($this->testData->getId('product1-green')));
        static::assertFalse($listing->has($this->testData->getId('product1-blue')));

        // product 2 has no available variants
        static::assertFalse($listing->has($this->testData->getId('product2-green')));
        static::assertFalse($listing->has($this->testData->getId('product2-red')));

        /** @var EntityResult $result */
        $result = $listing->getAggregations()->get('properties');

        /** @var PropertyGroupCollection $options */
        $options = $result->getEntities();

        $ids = array_keys($options->getOptionIdMap());

        static::assertContains($this->testData->getId('green'), $ids);
        static::assertContains($this->testData->getId('red'), $ids);
        static::assertNotContains($this->testData->getId('blue'), $ids);
    }

    private function insertProducts(): void
    {
        $this->createProduct(
            'product1',
            [
                [
                    'combination' => [$this->testData->getId('red')],
                    'stock' => 1,
                ],
                [
                    'combination' => [$this->testData->getId('blue')],
                    'stock' => 0,
                ],
                [
                    'combination' => [$this->testData->getId('green')],
                    'stock' => 1,
                ],
            ],
            [$this->testData->getId('color')]
        );

        $this->createProduct(
            'product2',
            [
                [
                    'combination' => [$this->testData->getId('red')],
                    'stock' => 0,
                ],
                [
                    'combination' => [$this->testData->getId('green')],
                    'stock' => 0,
                ],
            ],
            [$this->testData->getId('color')]
        );
    }

    private function createProduct(string $key, array $options, array $listingGroups): void
    {
        $config = [];
        foreach ($listingGroups as $groupId) {
            $config[] = [
                'id' => $groupId,
                'expressionForListings' => true,
                'representation' => 'box', // box, select, image, color
            ];
        }

        $configurator = [];
        foreach ($options as $grouped) {
            foreach ($grouped['combination'] as $optionId) {
                $configurator[] = ['optionId' => $optionId];
            }
        }

        $id = $this->testData->createId($key);
        $data = [
            [
                'id' => $id,
                'configuratorGroupConfig' => $config,
                'productNumber' => $id,
                'manufacturer' => ['name' => 'test'],
                'tax' => ['taxRate' => 19, 'name' => 'test'],
                'stock' => 0,
                'isCloseout' => true,
                'name' => $key,
                'active' => true,
                'price' => [
                    ['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => true],
                ],
                'categories' => [
                    ['id' => $this->categoryId],
                ],
                'configuratorSettings' => $configurator,
                'visibilities' => [
                    [
                        'salesChannelId' => Defaults::SALES_CHANNEL,
                        'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                    ],
                ],
            ],
        ];

        if (!empty($options)) {
            foreach ($options as $index => $option) {
                //$combination = $this->combos($option['combination']);
                $combination = $option['combination'];

                $variantKey = $key . '-' . implode('-', $this->testData->getKeyList($combination));

                $data[] = [
                    'id' => $this->testData->createId($variantKey),
                    'productNumber' => $key . '.' . $index,
                    'stock' => $option['stock'],
                    'isCloseout' => true,
                    'name' => $variantKey,
                    'active' => true,
                    'parentId' => $this->testData->getId($key),
                    'options' => array_map(static function ($id) {
                        return ['id' => $id];
                    }, $combination),
                ];
            }
        }

        $repo = $this->getContainer()->get('product.repository');

        /* @var EntityRepositoryInterface $repo */
        $repo->create($data, Context::createDefaultContext());
    }

    private function insertOptions(): void
    {
        $data = [
            [
                'id' => $this->testData->createId('color'),
                'name' => 'color',
                'options' => [
                    ['id' => $this->testData->createId('green'), 'name' => 'green'],
                    ['id' => $this->testData->createId('red'), 'name' => 'red'],
                    ['id' => $this->testData->createId('blue'), 'name' => 'blue'],
                ],
            ],
        ];

        /** @var EntityRepositoryInterface $repo */
        $repo = $this->getContainer()->get('property_group.repository');
        $repo->create($data, Context::createDefaultContext());
    }
}
