<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Provider;

use Oro\Bundle\NavigationBundle\Provider\TitleService;
use Oro\Bundle\NavigationBundle\Title\TitleReader\TitleReaderRegistry;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TitleServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TitleReaderRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $titleReaderRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $titleTranslator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $breadcrumbManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $userConfigManager;

    /**
     * @var TitleService
     */
    private $titleService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->titleReaderRegistry = $this->getMockBuilder(TitleReaderRegistry::class)->getMock();

        $this->titleTranslator = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Provider\TitleTranslator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->userConfigManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->breadcrumbManager = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Menu\BreadcrumbManager')
            ->disableOriginalConstructor()
            ->getMock();

        $breadcrumbLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();

        $breadcrumbLink->expects($this->any())->method('getService')->willReturn($this->breadcrumbManager);

        $this->titleService = new TitleService(
            $this->titleReaderRegistry,
            $this->titleTranslator,
            $this->userConfigManager,
            $breadcrumbLink
        );
    }

    public function testRender()
    {
        $this->titleTranslator->expects($this->once())
            ->method('trans')
            ->with('PrefixSuffix', [])
            ->will($this->returnValue('PrefixSuffix'));

        $result = $this->titleService->render([], null, 'Prefix', 'Suffix');

        $this->assertTrue(is_string($result));
    }

    public function testRenderStored()
    {
        $data = '{"template":"test template","short_template":"test short template","params":{"prm1":"val1"},'
            . '"prefix":"test prefix","suffix":"test suffix"}';

        $this->titleTranslator->expects($this->once())
            ->method('trans')
            ->with('test prefixtest templatetest suffix', ['prm1' => 'val1'])
            ->will($this->returnValue('translated template'));

        $result = $this->titleService->render([], $data, null, null, true);

        $this->assertEquals('translated template', $result);
    }

    public function testRenderStoredForShortTemplate()
    {
        $data = '{"template":"test template","short_template":"test short template","params":{"prm1":"val1"},'
            . '"prefix":"test prefix","suffix":"test suffix"}';

        $this->titleTranslator->expects($this->once())
            ->method('trans')
            ->with('test short template', ['prm1' => 'val1'])
            ->will($this->returnValue('translated short template'));

        $result = $this->titleService->render([], $data, null, null, true, true);

        $this->assertEquals('translated short template', $result);
    }

    public function testRenderStoredWithoutOptionalData()
    {
        $data = '{"template":"test template","short_template":"test short template","params":{"prm1":"val1"}}';

        $this->titleTranslator->expects($this->once())
            ->method('trans')
            ->with('test template', ['prm1' => 'val1'])
            ->will($this->returnValue('translated template'));

        $result = $this->titleService->render([], $data, null, null, true);

        $this->assertEquals('translated template', $result);
    }

    public function testRenderStoredWithEmptyData()
    {
        $data = '{"template":null,"short_template":null,"params":[]}';

        $this->titleTranslator->expects($this->once())
            ->method('trans')
            ->with('', [])
            ->will($this->returnValue(''));

        $result = $this->titleService->render([], $data, null, null, true);

        $this->assertEquals('', $result);
    }

    public function testRenderStoredInvalidData()
    {
        $data = 'invalid';

        $this->titleTranslator->expects($this->once())
            ->method('trans')
            ->with('Untitled', [])
            ->will($this->returnValue('translated Untitled'));

        $result = $this->titleService->render([], $data, null, null, true);

        $this->assertEquals('translated Untitled', $result);
    }

    public function testRenderShort()
    {
        $shortTitle = 'short title';
        $this->titleTranslator->expects($this->once())
            ->method('trans')
            ->with($shortTitle, [])
            ->will($this->returnValue($shortTitle));
        $this->titleService->setShortTemplate($shortTitle);
        $result = $this->titleService->render([], null, 'Prefix', 'Suffix', true, true);
        $this->assertTrue(is_string($result));
        $this->assertEquals($result, $shortTitle);
    }

    public function testSettersAndGetters()
    {
        $testString = 'Test string';
        $testArray = ['test'];

        $this->assertInstanceOf(
            '\Oro\Bundle\NavigationBundle\Provider\TitleService',
            $this->titleService->setSuffix($testString)
        );
        $this->assertInstanceOf(
            '\Oro\Bundle\NavigationBundle\Provider\TitleService',
            $this->titleService->setPrefix($testString)
        );

        $this->titleService->setParams($testArray);
        $this->assertEquals($testArray, $this->titleService->getParams());

        $dataArray = [
            'titleTemplate' => 'titleTemplate',
            'titleShortTemplate' => 'titleShortTemplate',
            'prefix' => 'prefix',
            'suffix' => 'suffix',
            'params' => ['test_params']
        ];
        $this->titleService->setData($dataArray);

        $this->assertEquals($dataArray['titleTemplate'], $this->titleService->getTemplate());
        $this->assertEquals($dataArray['titleShortTemplate'], $this->titleService->getShortTemplate());
        $this->assertEquals($dataArray['params'], $this->titleService->getParams());
    }

    public function testLoadByRoute()
    {
        $route          = 'test_route';
        $testTitle      = 'Test Title';
        $breadcrumbs    = ['Parent Path'];

        $this->titleReaderRegistry
            ->expects($this->once())
            ->method('getTitleByRoute')
            ->with($route)
            ->willReturn($testTitle);

        $this->breadcrumbManager
            ->expects($this->once())
            ->method('getBreadcrumbLabels')
            ->willReturn($breadcrumbs);

        $this->userConfigManager
            ->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap([
                ['oro_navigation.breadcrumb_menu', false, false, null, 'application_menu'],
                ['oro_navigation.title_suffix', false, false, null, 'Suffix'],
                ['oro_navigation.title_delimiter', false, false, null, '-'],
            ]);

        $this->titleService->setPrefix('-');
        $this->titleService->loadByRoute($route);

        $this->assertEquals('Test Title - Parent Path - Suffix', $this->titleService->getTemplate());
        $this->assertEquals($testTitle, $this->titleService->getShortTemplate());
    }

    public function testLoadByRouteWhenTitleDoesNotExist()
    {
        $route          = 'test_route';
        $breadcrumbs    = ['Parent Path'];

        $this->titleReaderRegistry
            ->expects($this->once())
            ->method('getTitleByRoute')
            ->with($route)
            ->willReturn(null);

        $this->breadcrumbManager
            ->expects($this->exactly(2))
            ->method('getBreadcrumbLabels')
            ->willReturn($breadcrumbs);

        $this->userConfigManager
            ->expects($this->exactly(4))
            ->method('get')
            ->willReturnMap([
                ['oro_navigation.breadcrumb_menu', false, false, null, 'application_menu'],
                ['oro_navigation.title_suffix', false, false, null, 'Suffix'],
                ['oro_navigation.title_delimiter', false, false, null, '-'],
                ['oro_navigation.breadcrumb_menu', false, false, null, 'application_menu'],
            ]);

        $this->titleService->setPrefix('-');
        $this->titleService->loadByRoute($route);

        $this->assertEquals('Parent Path - Suffix', $this->titleService->getTemplate());
        $this->assertEquals('Parent Path', $this->titleService->getShortTemplate());
    }

    public function testLoadByRouteWithoutMenuName()
    {
        $this->userConfigManager->expects($this->once())
            ->method('get')
            ->with('oro_navigation.breadcrumb_menu')
            ->will($this->returnValue('application_menu'));

        $this->titleService->loadByRoute('test_route');
    }

    public function testGetSerialized()
    {
        $this->titleService->setTemplate('test template');
        $this->titleService->setShortTemplate('test short template');
        $this->titleService->setParams(['prm1' => 'val1']);
        $this->titleService->setPrefix('test prefix');
        $this->titleService->setSuffix('test suffix');

        $this->assertEquals(
            '{"template":"test template","short_template":"test short template","params":{"prm1":"val1"},'
            . '"prefix":"test prefix","suffix":"test suffix"}',
            $this->titleService->getSerialized()
        );
    }

    public function testGetSerializedWithoutOptionalData()
    {
        $this->titleService->setTemplate('test template');
        $this->titleService->setShortTemplate('test short template');
        $this->titleService->setParams(['prm1' => 'val1']);

        $this->assertEquals(
            '{"template":"test template","short_template":"test short template","params":{"prm1":"val1"}}',
            $this->titleService->getSerialized()
        );
    }

    public function testGetSerializedWithEmptyData()
    {
        $this->assertEquals(
            '{"template":null,"short_template":null,"params":[]}',
            $this->titleService->getSerialized()
        );
    }
}
