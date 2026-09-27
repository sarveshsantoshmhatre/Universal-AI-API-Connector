# Architecture notes

## Data model

`connectors` stores the reusable endpoint contract: provider, model, instructions, dynamic input schema, output JSON schema, endpoint slug, active state, and a SHA-256 hash of the connector API key.

`api_requests` stores execution telemetry: request ID, result, latency, token usage, estimated cost when configured, provider/model, error type/message, and timestamp.

## Request lifecycle

1. Match `/api/{slug}`.
2. Load connector and check active state.
3. Verify connector API key.
4. Parse JSON or multipart form data.
5. Validate dynamic inputs.
6. Build the provider prompt.
7. Select a provider adapter.
8. Call the provider with a server-side API key.
9. Parse/validate structured JSON output.
10. Persist request telemetry.
11. Return the stable `success/data/error/meta` envelope.

## Provider abstraction

`ProviderAdapter` is the extension seam. Each adapter owns credential lookup, provider-specific request encoding, structured-output configuration, multimodal data encoding, usage extraction, and model discovery.

## Reliability

Validation and provider failures are converted to stable API errors. Unexpected exceptions are logged server-side and returned as a generic error. Request logging is best-effort so a logging failure does not invalidate an otherwise successful provider response.

## Deployment

Use SQLite locally. The container includes PostgreSQL support for production. Managed PostgreSQL is recommended when the web filesystem is ephemeral.
