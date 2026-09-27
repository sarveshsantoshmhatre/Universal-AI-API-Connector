# Universal AI API Connector & API Hub

Production-style PHP 8.3 application implementing the supplied examination assignment.

## Included
- Configurable connectors: name, description, provider, model, system prompt, dynamic input schema, output JSON schema, active state, connector API key.
- OpenAI and Google Gemini provider adapters.
- Provider model refresh actions.
- Dynamic Text, Number, Boolean, Image, File and JSON inputs with validation/defaults.
- Prompt composition and structured JSON output validation.
- Real generated POST endpoints with connector API-key authentication.
- Generated developer docs at /docs/<slug>.
- Browser API test playground with dynamic fields, timing, usage and errors.
- Persistent request logs and usage statistics.
- Responsive dashboard.
- SQLite local mode, PostgreSQL production mode, Docker, Render blueprint, health check, CI smoke tests.

## Run locally
1. Copy .env.example to .env.
2. Set OPENAI_API_KEY and/or GEMINI_API_KEY.
3. Run: php -S 127.0.0.1:8080 -t public
4. Open http://127.0.0.1:8080
5. Optional: php seed.php

seed.php creates two demonstration connectors using different providers:
- Card Scanner: image input -> OpenAI -> structured contact JSON.
- Article Writer: text/number/JSON input -> Gemini -> structured article JSON.

## API response format
Success responses contain success=true, data, error=null, and meta with request_id, response_time_ms, provider and model.
Failure responses contain success=false, data=null, and an error object with type and message.

## Security
Provider credentials are read from server environment variables only. Connector keys are stored as SHA-256 hashes and are only shown when created. Request size limits and image MIME validation are enforced. Raw server errors are hidden from API consumers.

## Deployment
Dockerfile provides PHP 8.3 with cURL, PDO SQLite and PDO PostgreSQL. render.yaml provides a deployment blueprint. For persistent production statistics on an ephemeral host, use managed PostgreSQL or durable storage.

## Assignment handoff
The supplied assignment requires a live URL, working endpoints, docs, two working connectors on multiple providers, sample inputs/responses, architecture, hosting details and bonus features. The repository contains the full application and deployment configuration. A live deployment still requires provider credentials and a persistent production database to be configured in the hosting environment.