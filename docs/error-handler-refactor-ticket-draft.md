## Title
Refactor `Tracer\ErrorHandler` for separation of concerns and safer fallback behavior

## Background
`src/Tracer/ErrorHandler.php` currently handles multiple concerns in one class:
- CLI/web branching
- error page rendering
- stack trace source extraction/formatting
- severity mapping
- logging decisions
- direct web response send

The current behavior works, but the class is hard to test and harder to evolve safely.

## Goal
Refactor `ErrorHandler` to improve maintainability and testability without changing external behavior.

## Scope
- Keep global handler registration flow unchanged (`SetupErrorHandlerStage`).
- Keep current user-visible behavior for CLI and web errors.
- Internally split logic into focused collaborators.

## Proposed Changes
1. Extract `ExceptionSeverityResolver`
- Responsibility: map `Throwable` to error/log severity.
- Moves logic from `getErrorType()`.

2. Extract `StackTraceFormatter` (or `StackTraceComposer`)
- Responsibility: build trace payload + source snippets for debug views.
- Moves logic from `composeStackTrace()`, `getSourceCode()`, `formatLineItem()`.

3. Extract `WebExceptionRenderer`
- Responsibility: produce web error content for debug/production.
- Uses view rendering and formatter.
- Handles fallback minimal output if rendering fails.

4. Keep `ErrorHandler` as orchestrator
- Responsibility:
- route CLI vs web path
- invoke renderer/resolver
- perform logging
- send final web response

5. Add robust fallback behavior
- If web rendering fails inside error handling, fallback to plain text 500 response.
- Prevent recursive/secondary failure loops.

## Non-Goals
- No change to routing/middleware/web adapter flow.
- No switch to adapter-level exception handling in this ticket.
- No behavioral change to current error templates/content (except emergency fallback path).

## Acceptance Criteria
- Existing `ErrorHandler` behavior remains equivalent for:
- CLI exception output
- debug-mode web exception page
- production-mode web 500 page + logging
- New unit tests cover extracted components and fallback path.
- `ErrorHandler` unit tests are simplified and focused on orchestration.
- No regression in existing test suite.

## Suggested Test Plan
- Unit: severity resolver mappings (`ErrorException`, `ParseError`, `ReflectionException`, generic `Throwable`).
- Unit: stack trace formatter with local filesystem adapter and non-local adapter.
- Unit: renderer in debug and production modes.
- Unit: fallback output when renderer throws.
- Integration-like unit: `ErrorHandler` orchestrates CLI/web and logging calls correctly.

## Risks / Notes
- Refactor touches exception path; fallback behavior must be carefully tested.
- Keep constructor and wiring compatible with current DI/bootstrap usage.

## Estimated Change Set
- `src/Tracer/ErrorHandler.php` (reduced orchestrator)
- new classes under `src/Tracer/` (or `src/Tracer/*`)
- test updates/additions under `tests/Unit/Tracer/`
