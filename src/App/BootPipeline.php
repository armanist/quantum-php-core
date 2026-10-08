<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App;

use Quantum\App\Contracts\BootStageInterface;
use InvalidArgumentException;

/**
 * Class BootPipeline
 * @package Quantum\App
 */
class BootPipeline
{
    /**
     * @var BootStageInterface[]
     */
    private array $stages;

    /**
     * @param BootStageInterface[] $stages
     */
    public function __construct(array $stages = [])
    {
        foreach ($stages as $stage) {
            if (!$stage instanceof BootStageInterface) {
                throw new InvalidArgumentException(
                    'All stages must implement ' . BootStageInterface::class
                );
            }
        }

        $this->stages = $stages;
    }

    public function run(AppContext $context): void
    {
        foreach ($this->stages as $stage) {
            $this->dispatchStageEvent($stage, 'BEFORE', $context);

            $stage->process($context);

            $this->dispatchStageEvent($stage, 'AFTER', $context);
        }
    }

    /**
     * Dispatches the event a stage declares for the given point, if any.
     * Skipped while the event() helper is not loaded yet, since nothing can listen then.
     */
    private function dispatchStageEvent(BootStageInterface $stage, string $point, AppContext $context): void
    {
        $constant = $stage::class . '::' . $point;

        if (!defined($constant) || !function_exists('event')) {
            return;
        }

        event()->dispatch((string) constant($constant), ['context' => $context]);
    }
}
