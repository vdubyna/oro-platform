<?php

namespace Oro\Bundle\BatchBundle\Test;

use Oro\Bundle\BatchBundle\Step\StepExecutionWarningHandlerInterface;

/**
 * StepExecutionWarningHandlerInterface implementation for tests
 */
class BufferedWarningHandler implements StepExecutionWarningHandlerInterface
{
    private $warnings = [];

    public function handleWarning($element, $name, $reason, array $reasonParameters, $item)
    {
        $this->warnings[] = [$element, $name, $reason, $reasonParameters, $item];
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }
}
