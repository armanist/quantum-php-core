# Unify ErrorHandler With Web Response Flow

## Background
The web request/response pipeline now expects handlers to return a `Response`, with a shared send path that applies cross-cutting behavior (for example, CORS headers) before `send()`.

Current exception handling in `Tracer\ErrorHandler` bypasses that pipeline by rendering and sending directly. This creates a second response-send path with duplicated behavior and a risk of drift.

## Current Gap
- Normal flow: route/controller returns `Response` -> web adapter sends through shared path.
- Error flow: global exception handler builds output and calls `response()->send()` directly.

Result: exception responses are not guaranteed to go through the same emit path as normal web responses.

## Goal
Adopt one shared response emission path for:
- success responses
- 404/not-found responses
- uncaught exception responses

while preserving global exception handling as a safety net.

## What Is An Emitter?
An emitter is a very small class whose only job is to send a prepared `Response` to the client.

In this codebase, "send a response" is not just `->send()`. We also need shared behavior (like CORS headers) right before send. That means response emission is a policy, not a one-liner.

So an emitter gives us one place for that policy.

## Why We Need It Here
Right now we have two web send paths:

1. Normal request path:
- `WebAppAdapter` -> `WebAppTrait::sendResponse()` -> apply CORS -> `send()`

2. Exception path:
- `ErrorHandler::handleWebException()` -> `response()->html(...)` -> `response()->send()`

Issue:
- exception responses skip shared web send behavior (CORS and future headers/middleware-like concerns)
- behavior can drift over time because logic is duplicated

Emitter fixes this by making both paths call the same send component.

## Minimal Design

### 1) New shared emitter
```php
<?php

namespace Quantum\Http;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Loader\Exceptions\LoaderException;
use Quantum\Di\Exceptions\DiException;
use ReflectionException;
use Quantum\Loader\Setup;

final class WebResponseEmitter
{
    /**
     * @throws ConfigException|LoaderException|DiException|ReflectionException
     */
    public function emit(Response $response): void
    {
        if (!config()->has('cors')) {
            config()->import(new Setup('config', 'cors'));
        }

        foreach (config()->get('cors') as $key => $value) {
            $response->setHeader($key, (string) $value);
        }

        $response->send();
    }
}
```

### 2) Use emitter in normal flow (WebAppTrait)
Before:
```php
private function sendResponse(Response $response): void
{
    $this->handleCors($response);
    $response->send();
}
```

After:
```php
private function sendResponse(Response $response): void
{
    $emitter = Di::get(WebResponseEmitter::class);
    $emitter->emit($response);
}
```

### 3) Use emitter in ErrorHandler web exception path
Before:
```php
response()->html($errorPage);
response()->send();
```

After:
```php
$response = response()->html($errorPage);
$emitter = Di::get(WebResponseEmitter::class);
$emitter->emit($response);
```

## Important Clarification
Unifying flow does not mean "run exception responses through `WebAppAdapter::start()` again".

When `set_exception_handler` runs, the original flow has already failed. The correct unification point is response emission policy (headers + send), not full request dispatch.

## Proposed Approach

### Phase 1: Extract exception-to-response mapping
Create a dedicated service (example: `ExceptionResponseFactory`) that converts `Throwable` -> `Response`.

Responsibilities moved from `ErrorHandler`:
- debug/production error-page selection
- stack trace payload composition for debug view
- error type/severity mapping
- production logging decision

### Phase 2: Centralize response emission
Create a reusable `ResponseEmitter` (or equivalent) used by all web response exits.

Responsibilities:
- apply shared response concerns (existing CORS behavior)
- call `send()` exactly once

Refactor callers:
- `WebAppAdapter` (normal path) uses emitter
- `ErrorHandler` web exception path uses emitter

### Phase 3 (Optional): True single control-flow entry
Wrap top-level web app startup in `try/catch` and route caught exceptions through:
- `ExceptionResponseFactory`
- `ResponseEmitter`

Keep `set_exception_handler` as fallback for fatal/unexpected contexts.

## Scope
In-scope:
- `src/Tracer/ErrorHandler.php`
- web response emission path used by `WebAppAdapter`
- new service(s) for exception-response mapping and emission
- related unit tests

Out-of-scope (for initial ticket):
- changing HTTP contract of controllers/routes
- broad boot pipeline redesign

## Testing Plan
- Update `ErrorHandler` tests to validate delegation to new shared services.
- Add focused unit tests for `ExceptionResponseFactory`:
  - debug vs production rendering
  - severity mapping
  - logging behavior
- Add unit tests for `ResponseEmitter`:
  - shared headers applied
  - single send behavior
- Keep/extend adapter tests to confirm exception responses still render and send correctly.

## Risks and Mitigations
- Risk: double-send/partial output in exceptional paths.
  - Mitigation: guard emitter usage and add explicit tests for send behavior.
- Risk: behavior changes in testing/CLI environment.
  - Mitigation: isolate CLI exception path, keep existing behavior intact.
- Risk: hidden coupling from helper globals.
  - Mitigation: move logic into injectable services and test boundaries directly.

## Acceptance Criteria
- Uncaught web exceptions no longer call `response()->send()` via custom inline flow in `ErrorHandler`.
- Exception responses pass through the same shared emitter path as normal web responses.
- Existing behavior remains functionally equivalent (debug trace page, production 500 page, logging).
- Validation passes:
  - `vendor/bin/phpunit --coverage-clover coverage.xml`
  - `composer phpstan`
  - `composer cs:check`

## Suggested Ticket Title
Unify `Tracer\\ErrorHandler` exception response handling with shared web response pipeline
