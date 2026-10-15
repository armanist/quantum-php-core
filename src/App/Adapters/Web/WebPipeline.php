<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Adapters\Web;

use Quantum\App\Contracts\WebStageInterface;
use InvalidArgumentException;

/**
 * Class WebPipeline
 *
 * Runs the web stages in order. The pipeline does not skip stages by itself;
 * a stage checks WebContext::isStopped() when it must be skipped.
 * Stage lifecycle events are not dispatched here yet.
 *
 * @package Quantum\App
 */
class WebPipeline
{
    /**
     * @var WebStageInterface[]
     */
    private array $stages;

    /**
     * @param WebStageInterface[] $stages
     */
    public function __construct(array $stages = [])
    {
        foreach ($stages as $stage) {
            if (!$stage instanceof WebStageInterface) {
                throw new InvalidArgumentException(
                    'All stages must implement ' . WebStageInterface::class
                );
            }
        }

        $this->stages = $stages;
    }

    public function run(WebContext $context): void
    {
        foreach ($this->stages as $stage) {
            $stage->process($context);
        }
    }
}
