<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Workflow\Action;

use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailOrigin;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\EmailBundle\Workflow\Action\SendEmail;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Component\ConfigExpression\ContextAccessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SendEmailTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContextAccessor|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $contextAccessor;

    /**
     * @var Processor|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $emailProcessor;

    /**
     * @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityNameResolver;

    /**
     * @var EventDispatcher|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dispatcher;

    /**
     * @var EmailOriginHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $emailOriginHelper;

    /**
     * @var SendEmail
     */
    protected $action;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    protected function setUp()
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->emailProcessor = $this->createMock(Processor::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->dispatcher = $this->createMock(EventDispatcher::class);
        $this->emailOriginHelper = $this->createMock(EmailOriginHelper::class);

        $this->action = new SendEmail(
            $this->contextAccessor,
            $this->emailProcessor,
            new EmailAddressHelper(),
            $this->entityNameResolver,
            $this->emailOriginHelper
        );

        $this->action->setDispatcher($this->dispatcher);

        $this->logger = $this->createMock('Psr\Log\LoggerInterface');
        $this->action->setLogger($this->logger);
    }

    /**
     * @param array $options
     * @param string $exceptionName
     * @param string $exceptionMessage
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, $exceptionName, $exceptionMessage)
    {
        $this->expectException($exceptionName);
        $this->expectExceptionMessage($exceptionMessage);
        $this->action->initialize($options);
    }

    /**
     * @return array
     */
    public function initializeExceptionDataProvider()
    {
        return array(
            'no from' => array(
                'options' => array('to' => 'test@test.com', 'subject' => 'test', 'body' => 'test'),
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'From parameter is required'
            ),
            'no from email' => array(
                'options' => array(
                    'to' => 'test@test.com', 'subject' => 'test', 'body' => 'test',
                    'from' => array('name' => 'Test')
                ),
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Email parameter is required'
            ),
            'no to' => array(
                'options' => array('from' => 'test@test.com', 'subject' => 'test', 'body' => 'test'),
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'To parameter is required'
            ),
            'no to email' => array(
                'options' => array(
                    'from' => 'test@test.com', 'subject' => 'test', 'body' => 'test',
                    'to' => array('name' => 'Test')
                ),
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Email parameter is required'
            ),
            'no to email in one of addresses' => array(
                'options' => array(
                    'from' => 'test@test.com', 'subject' => 'test', 'body' => 'test',
                    'to' => array('test@test.com', array('name' => 'Test'))
                ),
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Email parameter is required'
            ),
            'no subject' => array(
                'options' => array('from' => 'test@test.com', 'to' => 'test@test.com', 'body' => 'test'),
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Subject parameter is required'
            ),
            'no body' => array(
                'options' => array('from' => 'test@test.com', 'to' => 'test@test.com', 'subject' => 'test'),
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Body parameter is required'
            ),
        );
    }

    /**
     * @dataProvider optionsDataProvider
     * @param array $options
     * @param array $expected
     */
    public function testInitialize($options, $expected)
    {
        $this->assertSame($this->action, $this->action->initialize($options));
        $this->assertAttributeEquals($expected, 'options', $this->action);
    }

    public function optionsDataProvider()
    {
        return array(
            'simple' => array(
                array(
                    'from' => 'test@test.com',
                    'to' => 'test@test.com',
                    'subject' => 'test',
                    'body' => 'test'
                ),
                array(
                    'from' => 'test@test.com',
                    'to' => array('test@test.com'),
                    'subject' => 'test',
                    'body' => 'test'
                )
            ),
            'simple with name' => array(
                array(
                    'from' => 'Test <test@test.com>',
                    'to' => 'Test <test@test.com>',
                    'subject' => 'test',
                    'body' => 'test'
                ),
                array(
                    'from' => 'Test <test@test.com>',
                    'to' => array('Test <test@test.com>'),
                    'subject' => 'test',
                    'body' => 'test'
                )
            ),
            'extended' => array(
                array(
                    'from' => array(
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ),
                    'to' => array(
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ),
                    'subject' => 'test',
                    'body' => 'test'
                ),
                array(
                    'from' => array(
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ),
                    'to' => array(
                        array(
                            'name' => 'Test',
                            'email' => 'test@test.com'
                        )
                    ),
                    'subject' => 'test',
                    'body' => 'test'
                )
            ),
            'multiple to' => array(
                array(
                    'from' => array(
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ),
                    'to' => array(
                        array(
                            'name' => 'Test',
                            'email' => 'test@test.com'
                        ),
                        'test@test.com',
                        'Test <test@test.com>'
                    ),
                    'subject' => 'test',
                    'body' => 'test'
                ),
                array(
                    'from' => array(
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ),
                    'to' => array(
                        array(
                            'name' => 'Test',
                            'email' => 'test@test.com'
                        ),
                        'test@test.com',
                        'Test <test@test.com>'
                    ),
                    'subject' => 'test',
                    'body' => 'test'
                )
            )
        );
    }

    /**
     * @dataProvider executeOptionsDataProvider
     * @param array $options
     * @param array $expected
     */
    public function testExecute($options, $expected)
    {
        $context = array();
        $this->contextAccessor->expects($this->any())
            ->method('getValue')
            ->will($this->returnArgument(1));
        $this->entityNameResolver->expects($this->any())
            ->method('getName')
            ->will(
                $this->returnCallback(
                    function () {
                        return '_Formatted';
                    }
                )
            );

        $emailEntity = new EmailEntity();
        $emailUserEntity = $this->createMock(EmailUser::class);
        $emailUserEntity->expects($this->any())
            ->method('getEmail')
            ->willReturn($emailEntity);

        $emailOrigin = new TestEmailOrigin();
        $this->emailOriginHelper->expects($this->once())
            ->method('getEmailOrigin')
            ->with($expected['from'], null)
            ->willReturn($emailOrigin);

        $self = $this;
        $this->emailProcessor->expects($this->once())
            ->method('process')
            ->with($this->isInstanceOf(Email::class), $emailOrigin)
            ->will(
                $this->returnCallback(
                    function (Email $model) use ($emailUserEntity, $expected, $self) {
                        $self->assertEquals($expected['body'], $model->getBody());
                        $self->assertEquals($expected['subject'], $model->getSubject());
                        $self->assertEquals($expected['from'], $model->getFrom());
                        $self->assertEquals($expected['to'], $model->getTo());

                        return $emailUserEntity;
                    }
                )
            );
        if (array_key_exists('attribute', $options)) {
            $this->contextAccessor->expects($this->once())
                ->method('setValue')
                ->with($context, $options['attribute'], $emailEntity);
        }
        $this->action->initialize($options);
        $this->action->execute($context);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function executeOptionsDataProvider()
    {
        $nameMock = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\FirstNameInterface')
            ->getMock();
        $nameMock->expects($this->any())
            ->method('getFirstName')
            ->will($this->returnValue('NAME'));

        return array(
            'simple' => array(
                array(
                    'from' => 'test@test.com',
                    'to' => 'test@test.com',
                    'subject' => 'test',
                    'body' => 'test'
                ),
                array(
                    'from' => 'test@test.com',
                    'to' => array('test@test.com'),
                    'subject' => 'test',
                    'body' => 'test'
                )
            ),
            'simple with name' => array(
                array(
                    'from' => '"Test" <test@test.com>',
                    'to' => '"Test" <test@test.com>',
                    'subject' => 'test',
                    'body' => 'test'
                ),
                array(
                    'from' => '"Test" <test@test.com>',
                    'to' => array('"Test" <test@test.com>'),
                    'subject' => 'test',
                    'body' => 'test'
                )
            ),
            'extended' => array(
                array(
                    'from' => array(
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ),
                    'to' => array(
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ),
                    'subject' => 'test',
                    'body' => 'test'
                ),
                array(
                    'from' => '"Test" <test@test.com>',
                    'to' => array('"Test" <test@test.com>'),
                    'subject' => 'test',
                    'body' => 'test'
                )
            ),
            'extended with name formatting' => array(
                array(
                    'from' => array(
                        'name' => $nameMock,
                        'email' => 'test@test.com'
                    ),
                    'to' => array(
                        'name' => $nameMock,
                        'email' => 'test@test.com'
                    ),
                    'subject' => 'test',
                    'body' => 'test'
                ),
                array(
                    'from' => '"_Formatted" <test@test.com>',
                    'to' => array('"_Formatted" <test@test.com>'),
                    'subject' => 'test',
                    'body' => 'test'
                )
            ),
            'multiple to' => array(
                array(
                    'from' => array(
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ),
                    'to' => array(
                        array(
                            'name' => 'Test',
                            'email' => 'test@test.com'
                        ),
                        'test@test.com',
                        '"Test" <test@test.com>'
                    ),
                    'subject' => 'test',
                    'body' => 'test',
                    'attribute' => 'attr'
                ),
                array(
                    'from' => '"Test" <test@test.com>',
                    'to' => array(
                        '"Test" <test@test.com>',
                        'test@test.com',
                        '"Test" <test@test.com>'
                    ),
                    'subject' => 'test',
                    'body' => 'test'
                )
            )
        );
    }

    public function testExecuteWithProcessException()
    {
        $options = [
            'from' => 'test@test.com',
            'to' => 'test@test.com',
            'template' => 'test',
            'subject' => 'subject',
            'body' => 'body',
            'entity' => new \stdClass(),
        ];

        $context = array();
        $this->contextAccessor->expects($this->any())
            ->method('getValue')
            ->will($this->returnArgument(1));
        $this->entityNameResolver->expects($this->any())
            ->method('getName')
            ->will(
                $this->returnCallback(
                    function () {
                        return '_Formatted';
                    }
                )
            );

        $emailUserEntity = $this->getMockBuilder('\Oro\Bundle\EmailBundle\Entity\EmailUser')
            ->disableOriginalConstructor()
            ->setMethods(['getEmail'])
            ->getMock();
        $emailEntity = $this->createMock('\Oro\Bundle\EmailBundle\Entity\Email');
        $emailUserEntity->expects($this->any())
            ->method('getEmail')
            ->willReturn($emailEntity);

        $this->emailProcessor->expects($this->once())
            ->method('process')
            ->with($this->isInstanceOf('Oro\Bundle\EmailBundle\Form\Model\Email'))
            ->willThrowException(new \Swift_SwiftException('The email was not delivered.'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Workflow send email action.');

        $this->action->initialize($options);
        $this->action->execute($context);
    }
}
