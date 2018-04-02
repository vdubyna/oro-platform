<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Event;

use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use Oro\Bundle\EmbeddedFormBundle\Event\EmbeddedFormSubmitBeforeEvent;

class EmbeddedFormSubmitBeforeEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetter()
    {
        $formEntity = new EmbeddedForm();
        $event   = new EmbeddedFormSubmitBeforeEvent([], $formEntity);

        $this->assertSame($formEntity, $event->getFormEntity());
        $this->assertSame([], $event->getData());
    }
}
