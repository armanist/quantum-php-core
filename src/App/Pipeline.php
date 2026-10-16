<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App;

use Quantum\App\Contracts\StageInterface;
use InvalidArgumentException;

/**
 * Class Pipeline
 *
 * Runs stages in order with a context. The event a stage declares in its BEFORE / AFTER
 * constants is dispatched around it, with the context as the payload.
 *
 * @template TContext of AppContext
 * @package Quantum\App
 */
final class Pipeline
{
    /**
     * @var array<StageInterface<TContext>>
     */
    private array $stages;

    /**
     * @param array<StageInterface<TContext>> $stages
     */
    public function __construct(array $stages = [])
    {
        foreach ($stages as $stage) {
            if (!$stage instanceof StageInterface) {
                throw new InvalidArgumentException('All stages must implement ' . StageInterface::class);
            }
        }

        $this->stages = $stages;
    }

    /**
     * @param TContext $context
     */
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
     * @param StageInterface<TContext> $stage
     * @param TContext $context
     */
    private function dispatchStageEvent(StageInterface $stage, string $point, AppContext $context): void
    {
        $constant = $stage::class . '::' . $point;

        if (!defined($constant) || !function_exists('event')) {
            return;
        }

        event()->dispatch((string) constant($constant), ['context' => $context]);
    }
}
