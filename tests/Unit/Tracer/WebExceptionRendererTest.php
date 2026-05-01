<?php

namespace Quantum\Tests\Unit\Tracer;

use Quantum\Tests\Unit\AppTestCase;
use Quantum\Tracer\WebExceptionRenderer;
use Exception;

class WebExceptionRendererTest extends AppTestCase
{
    public function testRenderDebugModeUsesTraceTemplateAndSeverity(): void
    {
        config()->set('app.debug', true);

        $renderer = new WebExceptionRenderer();
        $html = $renderer->render(new Exception('Debug renderer failure'), 'warning');

        $this->assertStringContainsString('TRACE VIEW', $html);
        $this->assertStringContainsString('Warning :: Debug renderer failure', $html);
    }

    public function testRenderProductionModeUses500Template(): void
    {
        config()->set('app.debug', false);

        $renderer = new WebExceptionRenderer();
        $html = $renderer->render(new Exception('Prod renderer failure'), 'error');

        $this->assertStringContainsString('ERROR 500 VIEW', $html);
    }
}
