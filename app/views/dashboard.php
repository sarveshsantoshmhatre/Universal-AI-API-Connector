<?php declare(strict_types=1); ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Universal AI API Hub</title><link rel="stylesheet" href="/assets/styles.css"></head>
<body>
<header class="topbar"><div><div class="eyebrow">Universal AI API Connector</div><h1>API Hub</h1></div><div class="header-actions"><label class="admin-key">Admin key<input id="adminKey" type="password" placeholder="Optional"></label><button class="btn secondary" id="saveKey">Save</button><button class="btn primary" id="newConnector">Create connector</button></div></header>
<main class="page">
<section class="kpis"><div class="kpi"><span>Connectors</span><strong id="kpiConnectors">0</strong></div><div class="kpi"><span>Requests</span><strong id="kpiRequests">0</strong></div><div class="kpi"><span>Successful</span><strong id="kpiSuccess">0</strong></div><div class="kpi"><span>Failed</span><strong id="kpiFailed">0</strong></div></section>
<section class="card"><div class="section-head"><div><h2>Configured connectors</h2><p>Manage endpoints, providers, schemas, tests, documentation, and usage.</p></div><button class="btn secondary" onclick="loadConnectors()">Refresh</button></div><div class="table-wrap"><table><thead><tr><th>Name</th><th>Provider / model</th><th>Inputs</th><th>Status</th><th>Requests</th><th>Last used</th><th>Actions</th></tr></thead><tbody id="connectorRows"><tr><td colspan="7" class="muted">Loading...</td></tr></tbody></table></div></section>
<section class="grid-2">
<div class="card"><div class="section-head"><div><h2 id="editorTitle">Create connector</h2><p>Define the reusable AI endpoint contract.</p></div></div>
<form id="connectorForm"><input type="hidden" id="connectorId"><div class="form-grid">
<label>Name<input id="name" required></label><label>Provider<select id="provider"><option value="openai">OpenAI</option><option value="gemini">Google Gemini</option></select></label>
<label class="wide">Description<textarea id="description" rows="2"></textarea></label>
<label>Model<input id="model" required placeholder="Provider model id"></label><label class="model-actions"><span>&nbsp;</span><button class="btn secondary" type="button" id="refreshModels">Refresh models</button></label>
<label class="wide">System instructions / prompt<textarea id="instructions" rows="7" required placeholder="You are a ... Return only valid JSON ..."></textarea></label></div>
<div class="subhead"><h3>Input parameters</h3><button class="btn small" type="button" id="addField">Add parameter</button></div><div id="inputFields"></div>
<div class="subhead"><h3>Output schema</h3><button class="btn small" type="button" id="loadSchemaExample">Load example</button></div>
<textarea id="outputSchema" class="codebox" rows="12" required></textarea>
<div class="form-grid compact"><label class="check"><input type="checkbox" id="active" checked> Active</label></div>
<div class="form-actions"><button class="btn primary" type="submit">Save connector</button><button class="btn secondary" type="button" id="resetForm">Reset</button></div><div id="formMessage" class="message"></div></form></div>
<div class="card"><div class="section-head"><div><h2>Test API</h2><p>Run a request using the selected connector schema.</p></div></div><form id="testForm"></form><div id="testStatus" class="message"></div><pre id="testOutput" class="response-box">{}</pre></div>
</section>
<section class="card"><div class="section-head"><div><h2>Request history</h2><p>Latest operational logs for a selected connector.</p></div></div><div class="table-wrap"><table><thead><tr><th>Request</th><th>Status</th><th>Latency</th><th>Tokens</th><th>Cost</th><th>Provider / model</th><th>Time</th><th>Error</th></tr></thead><tbody id="logRows"><tr><td colspan="8" class="muted">Select Logs.</td></tr></tbody></table></div></section>
</main><script src="/assets/app.js"></script></body></html>
