# Provider adapter notes

The adapters use direct HTTPS requests so provider credentials never enter frontend JavaScript.

## OpenAI

The adapter uses the Responses API with structured JSON Schema output and multimodal input objects.

## Google Gemini

The adapter uses `generateContent`, inline media parts for small uploads, and JSON output configuration using `responseMimeType` and `responseSchema`.

Provider and model identifiers are connector configuration, not hardcoded application branches.
