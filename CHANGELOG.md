# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog.

## [3.1.0] - TBD

### Added
- Added `Request::expectsJson()`: true when the `Accept` header lists a JSON media type (`application/json` or a `+json` type, parameters and case ignored), or for an AJAX request that accepts any content type (#581)
- Added boot stage lifecycle events: stages may declare `BEFORE`/`AFTER` event names and the boot `Pipeline` dispatches them with the `AppContext` as a named payload. The environment, config, error handler, HTTP, and modules stages declare `boot.<stage>.before` and `boot.<stage>.after` (#536)
- Added adapter-based Lang provider support with `DeepL` and `Google Translate` adapters plus shared remote request/caching infrastructure (#533)

### Changed
- **BREAKING:** Unified the app adapter lifecycle and let the pipeline own an early end of a run (#589):
  - `AppInterface` gains `boot()`. The adapter constructors only store the context, and `AppFactory` calls `boot()` right after creating the adapter, so `AppFactory::create(...)->start()` is unchanged. Code that creates an adapter directly must call `boot()` before `start()`.
  - `Pipeline` ends the run when a stage calls `stop()` on the context: no later stage runs or dispatches events. An optional closing stage, the second constructor argument, runs last, also after a stop, but not after a stage throws. The web runtime passes `SendResponseStage` as its closing stage, and the web stages no longer check `isStopped()` themselves.
  - `stop()`, `isStopped()`, `getExitCode()` and `setExitCode()` moved to `AppContext`; `WebContext` and `ConsoleContext` inherit them.
  - The console boot no longer catches failures. It registers the core commands right after the helpers and the app commands at its end, after the environment, the config and the error handler, so app command constructors can use `env()` and `config()`. The new `ResolveEnvironmentStage` lets a core command that does not require the environment (`core:env`) and Symfony's `list` and `help` run when the `.env` file is missing or malformed, by stopping the boot; every other command fails with the exception of the environment file. The console runtime only runs the command.
  - Removed the boot failure added earlier in 3.1.0: `ConsoleContext::getBootFailure()` and its constructor argument, and the boot failure handling of `ConsoleAppAdapter` and `RunCommandStage`.
  - On a missing or malformed `.env`, an app command no longer runs even when it does not require the environment, and `list` shows only the core commands. A broken app helper file, or an error handler that cannot be set up, now fails every command instead of being tolerated.
  - Moved `RegisterCoreCommandsStage` and `RegisterAppCommandsStage` to `Quantum\App\Stages\Boot`. A core command constructor must not use the environment or any config: `DebugBarCommand`, `OpenApiCommand` and `ResourceCacheClearCommand` now get the file system in `exec()`.
  - `Environment::load()` now documents the Dotenv `InvalidFileException` it throws for a malformed file.
- **BREAKING:** Replaced the boot, web and console pipelines with one generic `Pipeline` (#583):
  - `Pipeline<TContext>` runs `StageInterface<TContext>` stages in order and dispatches the `BEFORE`/`AFTER` events they declare. The context type is a PHPDoc template checked by PHPStan: a stage states it with `@implements StageInterface<AppContext>` (or `WebContext`, `ConsoleContext`) and takes `process(AppContext $context)`.
  - Removed `BootPipeline` and `BootStageInterface`: build a `new Pipeline([...])`, and implement `StageInterface` with `@implements StageInterface<AppContext>` in a boot stage. `WebPipeline`, `ConsolePipeline`, `WebStageInterface` and `ConsoleStageInterface`, added earlier in 3.1.0, are removed too.
  - A pipeline still rejects anything that is not a `StageInterface`, but no longer rejects a stage of another kind at runtime (a boot stage in a web pipeline); PHPStan reports it instead.
- **BREAKING:** The web and console contexts now extend `AppContext` (#579):
  - `AppContext` keeps only what both the web and the console boot provide: `getBaseDir()`, `getContainer()`, `getEnvironment()` and `getConfig()`. Removed `AppContext::getRequest()`, `getResponse()` and `getRoutes()`, which only the web boot can serve: use `WebContext::getRequest()` and `WebContext::getRoutes()`, and `response()` for the shared response.
  - `WebContext` and `ConsoleContext` extend `AppContext` and are built from it with the same base directory and container; `WebContext::getAppContext()` is removed. Like `ConsoleContext`, `WebContext` reads its services from the container (the request, the routes and the view cache) and the matched route from the request, and stores only what the run produces: the response that is sent and the stopped flag. `WebContext::setMatchedRoute()` and `setViewCache()` are removed.
  - The web stages read the request, the routes and the config from the context instead of the global `request()` and `config()` helpers, as the console stages do. They are the same objects from the same container, so behavior is unchanged.
- **BREAKING:** The console adapter constructor is now only the boot pipeline, like the web adapter (#576):
  - The Symfony `Application`, the input and the output are provided by the new `InitConsoleStage` through the container, and the new `SetupConsoleApplicationStage` names the application from `app.name` and `app.version` once the whole boot has succeeded (it stays `UNKNOWN` after a failed boot). `ConsoleContext` is built from the `AppContext` and the boot failure, and reads those objects from the container. Removed `ConsoleAppAdapter::createApplication()` (get the application with `Di::get(Application::class)`) and `ConsoleContext::getAppContext()`.
  - The adapter no longer makes the environment mutable for every command. `core:key` and the toolkit installer already do it themselves; a command outside the framework that calls `environment()->updateRow()` must call `setMutable(true)` first, or it gets `EnvException::environmentImmutable()`.
- The console boot no longer decides by the `core:env` command name (#575):
  - `ConsoleAppAdapter` always runs the full boot and, when it fails, keeps the failure instead of stopping. `RunCommandStage` re-throws that original failure only for a command that requires the environment, so `core:env`, `list`, and `help` still run and `core:env --yes` can repair a missing, empty, or malformed `.env`. The reduced boot for `core:env` is gone, and the Symfony `Application` is named `UNKNOWN` until a successful boot names it from the app config.
  - Added `CliCommand::$requiresEnvironment` (default `true`) with `CliCommand::requiresEnvironment()`; a command that works without the environment, like `EnvCommand`, sets it to `false`. `ConsoleContext` takes an optional boot failure and exposes it with `getBootFailure()`.
- **BREAKING:** Refactored the web and console runtime into staged pipelines (#537):
  - `WebAppAdapter::start()` now runs a `Pipeline` of the preflight, route resolution, not found, prepare, dispatch, and send stages with a `WebContext`, and `ConsoleAppAdapter::start()` runs a `Pipeline` of the register core commands, register app commands, and run stages with a `ConsoleContext`; the run stage rejects a missing or unknown command before running anything, and registration goes through the new `Quantum\Console\CommandRegistrar`. Behavior is unchanged. The pipeline dispatches a stage's `BEFORE`/`AFTER` events when the stage declares them, but the built-in web and console stages declare none yet.
  - Moved the web adapter and context to `Quantum\App\Adapters\Web`, the console ones to `Quantum\App\Adapters\Console`, and the boot stages to `Quantum\App\Stages\Boot`. Code that goes through `AppFactory` is unaffected; code that references these classes directly must update its imports.
  - Removed the `AppAdapter` base class and the `WebAppTrait` and `ConsoleAppTrait` traits. `WebAppAdapter` and `ConsoleAppAdapter` now implement `AppInterface` directly.
- **BREAKING:** Replaced the `Quantum\Hook` package with `Quantum\Event` (#536):
  - `hook()` is now `event()`; `on()` and `fire()` are now `listen()` and `dispatch()`, and no aliases remain. Listeners receive one named payload array, which is empty when none is given.
  - Event names no longer need to be registered, so `shared/config/hooks.php` is no longer read; dispatching a name without listeners does nothing and an empty name throws `EventException`.
  - Listeners stay registered after `dispatch()` instead of being removed after the first call.
  - Listeners can be registered from app helper files, which load before config and before any boot event.
  - Removed the unused `hooks_dir()` helper and renamed the debugger tab constant `Debugger::HOOKS` to `Debugger::EVENTS`; the debugbar tab is now `events` and lists only events that have listeners.
- **BREAKING:** Removed `Quantum\Loader` and moved its responsibilities to their owning packages (#535):
  - Moved `Setup` to `Quantum\Config\Setup`; required config failures now use `ConfigException`, and optional imports use `Config::importIfExists()`.
  - Config paths resolve from the application base directory, independent of the process working directory.
  - `Environment::load()` no longer accepts `Setup`, reads only `shared/config/env.php`, and reports missing files with `EnvException`.
  - App boot now loads component, app, and module helpers directly.
- Corrected cache adapter deserialization exception handling and `ModelCollection` key type declarations (#570)
- Refactored `HttpClient` internals behind explicit `CurlAdapter` and `MultiCurlAdapter` wrappers while preserving the existing facade methods and keeping `php-curl-class` as the underlying transport for this phase (#534)
- Replaced the single-request `HttpClient` `CurlAdapter` execution path with native PHP cURL (#566)
- Replaced the multi-request `HttpClient` `MultiCurlAdapter` execution path with native PHP multi-curl and removed the `php-curl-class` dependency while preserving facade, factory, helper, callback, and response aggregation behavior (#567)
- Refactored the Lang package to resolve adapter instances through `LangFactory` configuration and load file translations lazily on first use instead of preloading them during web boot (#533)
- **BREAKING:** Reshaped Lang configuration so `lang.default` now selects the adapter, locale fallback moved to `lang.default_locale`, and the unused `lang.enabled` toggle was removed (#533)
- **BREAKING:** Removed `Lang::isEnabled()` from the public Lang API because it no longer affected runtime behavior (#533)
- Applied the pending Rector refactors across `src/` (null-coalescing assignment for lazy initialization, explicit `instanceof` checks instead of truthiness checks on nullable objects, native return types on `env()`, `CurlAdapter::getId()` and `CurlAdapter::getResponseCookies()`) and added `composer rector:check` to the CI workflow after PHPStan (#587)

### Fixed
- The page not found response is JSON for every client that asks for JSON, not only when the `Accept` header is exactly `application/json`: clients such as axios (`application/json, text/plain, */*`), a `charset` parameter, `+json` types and AJAX requests used to get the HTML page. `page_not_found_response()` decides with `Request::expectsJson()` and takes an optional request (#581)
- `core:env` now looks for the existing `.env` in the application base directory, so running it from another working directory asks for the confirmation instead of silently overwriting the file (#575)
- The file rate limiter creates the missing parent folders of its `path` (for example `cache/data` when `cache/` does not exist), instead of failing every request that goes through the rate limit middleware with `mkdir(): No such file or directory` (#585)

## [3.0.3] - 2026-07-10

### Changed
- **BREAKING:** Raised the minimum supported PHP version from `7.4` to `8.0` (#532)
- Updated CI coverage to test PHP `8.0`, `8.1`, and `8.2` (#532)
- Raised the static analysis baseline to PHPStan level `8` with PHP `8.0` target settings (#532)
- Kept Rector aligned to the PHP 8 baseline and applied bounded PHP 8-safe modernization updates (#532)
- Simplified the standard framework file banner across core sources, templates, and affected tests (#532)
- Made Twig an optional renderer dependency so the framework can retain PHP 8.0 support without requiring vulnerable Twig releases (#532)
- Upgraded the PHP 8.0-compatible runtime lines for `psr/log`, `php-curl-class`, `php-debugbar`, `phpmailer`, `voku/html-min`, `swagger-ui`, and `swagger-php` (#532)

### Notes
- OpenAPI metadata remains on Doctrine-style annotations for now because the currently used `swagger-php` attribute scanning path requires PHP `8.1+`; `doctrine/annotations` is intentionally retained on the PHP `8.0` baseline (#532)
- Twig rendering requires a separately installed, security-supported Twig release; Twig `^3.27` requires PHP 8.1 or later, while the default HTML renderer remains available on PHP 8.0 (#532)

## [3.0.0] - 2026-05-28

Upgrade guide: https://github.com/softberg/quantum-php-docs/blob/master/v3.0/upgrade-guide.md

### Changed
- **BREAKING:** Refactored app bootstrapping and DI ownership model (#373):
  - Introduced `AppContext` as the central execution state holder (mode, baseDir, DiContainer, Environment, Config, Request, Response, Routes)
  - Split `Di` into a static facade delegating to an instance-based `DiContainer`, one container per application execution
  - Added `Di::set()`, `Di::has()`, and `Di::isRegistered()` for explicit service management
  - `Di::get()` now enforces a strict contract: throws if the dependency is not explicitly registered (no more implicit auto-registration)
  - Introduced `BootPipeline` and `BootStageInterface` for explicit, ordered boot sequences
  - Extracted boot stages: `LoadHelpersStage`, `LoadEnvironmentStage`, `LoadAppConfigStage`, `SetupErrorHandlerStage`, `InitHttpStage`, `InitDebuggerStage`
  - Removed `AppTrait`; all adapter boot logic moved to pipeline stages and focused private methods in `WebAppTrait`/`ConsoleAppTrait`
- **BREAKING:** Converted `Request` and `Response` from static facades to instance-based classes (#454):
  - Merged `HttpRequest`/`HttpResponse` wrapper layer directly into `Request`/`Response`
  - All request/response access now goes through `request()` and `response()` helper functions
- **BREAKING:** Refactored request/response execution flow to explicit `Response` returns (#470, #471, #472, #473):
  - Route handlers (controller actions and closure routes) must now return `Quantum\Http\Response`; non-`Response` returns now fail fast
  - Response builder methods are now fluent and return `Response`/`self` (`json()`, `html()`, `xml()`, `jsonp()`, `set()`, `setHeader()`, `setStatusCode()`, `redirect()`)
  - Redirect helpers now return `Response` (`redirect()`, `redirectWith()`) instead of terminating execution implicitly
  - Middleware pipeline is now terminal/return-driven: middleware `apply()` must return `Response`, and middleware chaining no longer returns `[Request, Response]`
  - Web adapter flow no longer relies on `stop()` for normal control flow (`OPTIONS`, 404, cache-hit, route dispatch) and now sends a returned `Response`
  - Cached view lookup now returns `?Response` directly (`ViewCache::getCachedResponse()`)
  - Console adapter normal termination no longer relies on `StopExecutionException`; `start()` now returns explicit exit codes
- **BREAKING:** Removed all static singleton patterns from core services (#381, #382):
  - Migrated all 12 first-party factories (Auth, Archive, Cache, Captcha, Cryptor, FileSystem, Lang, Logger, Mailer, Renderer, Session, View) from static instance caches to DI-managed lifetimes
  - Migrated service singletons to DI ownership: Cookie, Config, Environment, Server, AssetManager, Csrf, Database, MailTrap, Debugger, ViewCache, ErrorHandler, HookManager, ModuleLoader
- **BREAKING:** `Environment` class is no longer a static singleton (#456):
  - Uses `Dotenv::createArrayBacked()` for isolated, deterministic env loading
  - New `environment()` helper function and shorthand check methods: `isProduction()`, `isTesting()`, `isStaging()`, `isDevelopment()`, `isLocal()`
  - `env()` helper now delegates through `environment()->getValue()`
- **BREAKING:** Renamed `Quantum\\Service\\QtService` to `Quantum\\Service\\Service` (#500)
- **BREAKING:** Renamed `Quantum\\Middleware\\QtMiddleware` to `Quantum\\Middleware\\Middleware` (#500)
- **BREAKING:** Renamed `Quantum\\Migration\\QtMigration` to `Quantum\\Migration\\Migration` (#500)
- **BREAKING:** Renamed `Quantum\\Console\\QtCommand` to `Quantum\\Console\\CliCommand` (#500)

- **BREAKING:** Minimum PHP version requirement raised from 7.3 to 7.4
- Modernized codebase with PHP 7.4+ syntax using Rector:
  - Array destructuring: `list()` → `[]`
  - Arrow functions for simple closures
  - Null coalescing assignment operator (`??=`)
  - Class constant references using `::class`
  - Secure random number generation with `random_int()`
  - Modern `setcookie()` array syntax
- Updated CI pipeline to test against PHP 7.4, 8.0, and 8.1
- CI now fails on PHP warnings and deprecations for stricter quality control
- Added `declare(strict_types=1)` to all Exception classes for improved type safety
- Static analysis baseline is now PHPStan level 7

- **BREAKING:** Refactored routing system internals:
  - Routes are now represented as first-class objects (`Route`, `RouteCollection`, `MatchedRoute`)
  - Introduced `RouteBuilder` as a fluent DSL interpreter and route composition engine
  - Introduced `PatternCompiler` for centralized route pattern compilation
  - Introduced `RouteDispatcher` for explicit controller / closure dispatching
  - Route matching now produces a `MatchedRoute` object containing the route and extracted parameters
  - Routing is executed once per request; matched route is stored on the `Request`
  - Global route helper functions (`current_*`, `route_*`) now rely on `MatchedRoute` instead of legacy static route state
  - Middleware ordering is strictly prepend-based (later middleware wraps earlier)
  - Route name uniqueness is now enforced at build time

- **BREAKING:** Controller resolution behavior changed:
  - Routes no longer implicitly resolve controller class names via legacy `RouteController` logic
  - Controllers are now instantiated directly based on the handler defined in the route
  - Projects relying on legacy controller resolution or `RouteController` static state must update accordingly

- **BREAKING:** Refactored model architecture:
  - Introduced a base Model class for non-database models
  - Refactored QtModel into DbModel with persistence-only responsibilities
  - Database fetch methods (first(), findOne(), findOneBy(), get()) now return new model instances instead of mutating existing ones
  - Calling create() resets model state to ensure clean inserts
  - Database-generated primary keys are now synced into the model after save()
  - Models are hydrated only on fetch operations, not implicitly via getters
- `DbModel::save()` now automatically applies timestamps when the model uses the `HasTimestamps` trait
- `SoftDeletes` now uses model timestamp format when available (datetime/unix) for `deleted_at`
- Routing DSL now supports first-class route rate limiting via `->rateLimit(int $limit, int $interval)`.
- Middleware execution pipeline now includes a framework-level middleware stage before module middleware resolution, used for route rate limit enforcement.

### Fixed
- PHP 8.1 compatibility: Fixed null parameter deprecations in `explode()`, `parse_url()`, and `str_replace()`
- PHP 8.4 forward compatibility: Fixed implicitly nullable parameters in database and auth adapters
- PHP 8.4 forward compatibility: Added missing type hint to `SleekDbal::__get()` magic method
- Fixed deprecated `E_STRICT` constant usage in test bootstrap
- Fixed cURL error message assertions for cross-version compatibility
- Fixed SleekDB paginator query-state regression where `count()` could clear criteria before paginated data fetch on the same model instance (#514)
- Standardized `defineValidationRules(Request $request): void` across DemoWeb and DemoApi middleware templates
- Fixed OpenAPI installer route generation to return `Response` objects via `response()->...` helpers and avoid undefined response-variable usage (#520)
- Standardized `defineValidationRules(Request $request): void` in Toolkit middleware templates
- Fixed request uploaded-file parsing to preserve multiple top-level multipart file fields in both real and internal request flows (#526)
- Fixed `ModuleLoader` to skip dependency registration for modules where `enabled` is `false`, aligning dependency loading with route loading behavior (#529)

### Added
- `AppContext` class representing the runtime identity of a single application execution
- `DiContainer` instance-based dependency injection container, isolated per execution
- `BootPipeline` and `BootStageInterface` for declarative, ordered boot sequences
- `environment()` global helper and `Environment` shorthand methods (`isProduction()`, `isTesting()`, etc.)
- Lazy registration guards (`Di::register()` + `Di::isRegistered()`) at all DI call sites for explicit dependency management
- Rector as dev dependency for automated code refactoring
- Additional PHP extensions required in CI: `bcmath`, `gd`, `zip`
- PHPUnit strict testing flags: `--fail-on-warning`, `--fail-on-risky`

- **Routing test coverage**:
  - Comprehensive unit tests for route building, grouping, middleware ordering, caching, and naming
  - Unit tests for route helpers and request–route integration
  - Unit tests for dispatcher behavior with controller and closure routes

- **Cron Scheduler**: New CLI command `php qt cron:run` for running scheduled tasks
  - Task definition via PHP files in `cron/` directory
  - Cron expression parsing using `dragonmantank/cron-expression` library
  - File-based task locking to prevent concurrent execution
  - Comprehensive logging of task execution and errors
  - Support for force mode and specific task execution
  - Automatic cleanup of stale locks (older than 24 hours)
  - Full documentation in `docs/cron-scheduler.md`

- **Opt-in Model Timestamps**: Introduced `HasTimestamps` trait for `DbModel`:
  - Automatically sets `created_at` on insert
  - Automatically sets `updated_at` on insert and update
  - Supports custom timestamp column names via model constants (`CREATED_AT`, `UPDATED_AT`)
  - Supports datetime and unix timestamp formats via `TIMESTAMP_TYPE`
- **Rate Limiting**:
  - Added `RateLimit` package with contract-first architecture (`RateLimitAdapterInterface`, `RateLimiter`, `RateLimiterFactory`)
  - Added file and redis rate limit adapters
  - Added `RateLimitMiddleware` for centralized `429 Too Many Requests` enforcement
  - Added rate limit response headers (`X-RateLimit-Limit`, `X-RateLimit-Remaining`, `Retry-After`)
  - Added route-level metadata support on `Route` and `RouteBuilder` for rate limiting
  - Added unit test coverage for router integration, middleware flow, adapters, factory resolution, and exception behavior
  - Updated `DemoApi` module templates to include default API route throttling and generated `config/rate_limit.php` (#510)
- SleekDB adapter now supports related-model criteria path filtering across join depths while preserving DBAL public API (#508)

### Removed
- Support for PHP 7.3 and earlier versions
- Legacy routing static state and implicit controller resolution via `RouteController`
- `AppTrait` — replaced by boot pipeline stages and adapter-specific traits
- Static singleton patterns from all core services and factories
- `Environment::getInstance()` static singleton accessor
- `HttpRequest`/`HttpResponse` static facade wrapper classes (merged into `Request`/`Response`)
- Implicit auto-registration in `Di::get()` — all dependencies must be explicitly registered
- `RegisterCoreDependenciesStage` and `dependencies.php` — replaced by lazy registration at call sites
