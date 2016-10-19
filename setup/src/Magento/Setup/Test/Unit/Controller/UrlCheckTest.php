<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Setup\Test\Unit\Controller;

use Magento\Setup\Controller\UrlCheck;
use Zend\Stdlib\RequestInterface;
use Zend\View\Model\JsonModel;
use Magento\Framework\Url\SimpleValidator;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class UrlCheckTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $requestJson
     * @param array $expectedResult
     * @dataProvider indexActionDataProvider
     */
    public function testIndexAction($requestJson, $expectedResult)
    {
        /** @var ObjectManagerHelper $objectManagerHelper */
        $objectManagerHelper = new ObjectManagerHelper($this);

        $returnMap = [];
        if (isset($requestJson['address']['actual_base_url'])) {
            $returnMap[] = [$requestJson['address']['actual_base_url'], $expectedResult['successUrl']];
        }
        if (isset($requestJson['https']['text'])) {
            $returnMap[] = [$requestJson['https']['text'], $expectedResult['successSecureUrl']];
        }

        /** @var SimpleValidator|\PHPUnit_Framework_MockObject_MockObject $validator */
        $validator = $this->getMockBuilder(SimpleValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $validator->expects($this->any())
            ->method('isValid')
            ->willReturnMap($returnMap);

        /** @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject $requestMock */
        $requestMock = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();
        $requestMock->expects($this->once())
            ->method('getContent')
            ->willReturn(json_encode($requestJson));

        $controller = $objectManagerHelper->getObject(
            UrlCheck::class,
            ['simpleUrlValidator' => $validator]
        );
        $objectManagerHelper->setBackwardCompatibleProperty($controller, 'request', $requestMock);

        $this->assertEquals(new JsonModel($expectedResult), $controller->indexAction());
    }

    /**
     * @return array
     */
    public function indexActionDataProvider()
    {
        return [
            [
                'requestJson' => [
                    'address' => [
                        'actual_base_url' => 'http://localhost'
                    ]
                ],
                'expectedResult' => ['successUrl' => true, 'successSecureUrl' => true]
            ],
            [
                'requestJson' => [
                    'address' => [
                        'actual_base_url' => 'http://localhost.com_test'
                    ]
                ],
                'expectedResult' => ['successUrl' => false, 'successSecureUrl' => true]
            ],
            [
                'requestJson' => [
                    'address' => [
                        'actual_base_url' => 'http://localhost.com_test'
                    ],
                    'https' => [
                        'admin' => false,
                        'front' => false,
                        'text' => ''
                    ]
                ],
                'expectedResult' => ['successUrl' => false, 'successSecureUrl' => true]
            ],
            [
                'requestJson' => [
                    'address' => [
                        'actual_base_url' => 'http://localhost.com:8080'
                    ],
                    'https' => [
                        'admin' => true,
                        'front' => false,
                        'text' => 'https://example.com.ua/'
                    ]
                ],
                'expectedResult' => ['successUrl' => true, 'successSecureUrl' => true]
            ],
            [
                'requestJson' => [
                    'address' => [
                        'actual_base_url' => 'http://localhost.com:8080/folder_name/'
                    ],
                    'https' => [
                        'admin' => false,
                        'front' => true,
                        'text' => 'https://example.com.ua/'
                    ]
                ],
                'expectedResult' => ['successUrl' => true, 'successSecureUrl' => true]
            ],
            [
                'requestJson' => [
                    'address' => [
                        'actual_base_url' => 'http://localhost.com:8080/folder_name/'
                    ],
                    'https' => [
                        'admin' => true,
                        'front' => true,
                        'text' => 'https://example.com.ua:8090/folder_name/'
                    ]
                ],
                'expectedResult' => ['successUrl' => true, 'successSecureUrl' => true]
            ],
        ];
    }
}
