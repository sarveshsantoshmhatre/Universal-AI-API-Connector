import { Head, Link, usePage } from '@inertiajs/react';
import { Activity, ArrowUpRight, BookOpen, Clock, Cpu, FilePlus2, Layers, Plus, Search, SearchX, Zap } from 'lucide-react';
import { useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { AppShell } from '../../components/AppShell';
import { ProviderBadge, StatusBadge } from '../../components/Badges';
import { CopyButton } from '../../components/CopyButton';
import { RelativeTime } from '../../components/RelativeTime';
import { buildCurl } from '../../curl';
import type { Connector, ProviderStatus } from '../../types';

type Props = { connectors: Connector[] };
type Filter = 'all' | Connector['status'];

const filters: Filter[] = ['all', 'active', 'draft', 'disabled'];

export default function ConnectorIndex({ connectors }: Props) {
    const [query, setQuery] = useState('');
    const [filter, setFilter] = useState<Filter>('all');
    const search = useRef<HTMLInputElement>(null);
    const { providerStatus } = usePage<{ providerStatus: ProviderStatus[] }>().props;
    const quickstart = connectors.find((connector) => connector.slug === 'article-writer' && connector.status === 'active' && connector.auth_mode === 'none')
        ?? connectors.find((connector) => connector.status === 'active' && connector.auth_mode === 'none' && connector.input_schema.every((field) => field.type !== 'image' && field.type !== 'file'))
        ?? null;
    const quickstartCurl = quickstart
        ? buildCurl(quickstart, Object.fromEntries(quickstart.input_schema.filter((field) => field.example && field.required).map((field) => [field.name, field.example as string])), {})
        : null;

    useEffect(() => {
        const focusSearch = (event: KeyboardEvent) => {
            const target = event.target as HTMLElement;
            if (event.key !== '/' || target.closest('input, textarea, select, [contenteditable]')) return;
            event.preventDefault();
            search.current?.focus();
        };
        window.addEventListener('keydown', focusSearch);
        return () => window.removeEventListener('keydown', focusSearch);
    }, []);

    const counts = useMemo(() => Object.fromEntries(filters.map((status) => [status, status === 'all' ? connectors.length : connectors.filter((connector) => connector.status === status).length])) as Record<Filter, number>, [connectors]);
    const totalRequests = connectors.reduce((sum, connector) => sum + connector.request_count, 0);
    const providerCount = new Set(connectors.map((connector) => connector.provider)).size;

    const visible = useMemo(() => {
        const term = query.trim().toLowerCase();
        return connectors.filter((connector) => (filter === 'all' || connector.status === filter)
            && (!term || [connector.name, connector.slug, connector.description ?? '', connector.provider, connector.model].some((value) => value.toLowerCase().includes(term))));
    }, [connectors, filter, query]);

    return <AppShell>
        <Head title="Connectors" />
        <section className="page-header page-header-3d">
            <div className="hero-visual" aria-hidden="true">
                <span className="hero-visual-orbit hero-visual-orbit-a" />
                <span className="hero-visual-orbit hero-visual-orbit-b" />
                <span className="hero-visual-orbit hero-visual-orbit-c" />
                <span className="hero-visual-core" />
                <span className="hero-visual-node hero-visual-node-a" />
                <span className="hero-visual-node hero-visual-node-b" />
                <span className="hero-visual-base" />
            </div>
            <div className="page-header-copy">
                <h1>Connectors</h1>
                <p className="page-subtitle">Turn an AI prompt into a validated HTTP API. Each connector below is a live endpoint with its own docs, playground and usage log.</p>
            </div>
            <div className="header-actions">
                <Link href="/connectors/create" className="button button-primary"><Plus size={15} /> New connector</Link>
            </div>
        </section>

        {connectors.length === 0 ? <EmptyState /> : <>
            <section className="metrics" aria-label="Workspace summary">
                <Metric icon={<Layers size={14} />} label="Connectors" value={connectors.length} />
                <Metric icon={<Zap size={14} />} label="Active" value={counts.active} hint={`of ${connectors.length}`} />
                <Metric icon={<Activity size={14} />} label="Total requests" value={totalRequests} />
                <Metric icon={<Cpu size={14} />} label="Providers" value={providerCount} />
            </section>

            <div className="toolbar">
                <label className="search">
                    <Search size={15} />
                    <span className="sr-only">Search connectors</span>
                    <input ref={search} className="input" type="search" value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Search by name, slug or model" />
                    {!query && <kbd>/</kbd>}
                </label>
                <div className="tab-strip" role="group" aria-label="Filter by status">
                    {filters.map((status) => <button key={status} type="button" className={filter === status ? 'is-active' : ''} aria-pressed={filter === status} onClick={() => setFilter(status)}>
                        <span style={{ textTransform: 'capitalize' }}>{status}</span><span className="segmented-count">{counts[status]}</span>
                    </button>)}
                </div>
            </div>

            {visible.length === 0
                ? <div className="card empty-inline"><SearchX size={26} /><strong>No connectors match</strong><span>Try a different search or status filter.</span><button type="button" className="button button-secondary button-sm" onClick={() => { setQuery(''); setFilter('all'); }}>Clear filters</button></div>
                : <section className="connector-grid" aria-label="Configured connectors">{visible.map((connector) => <ConnectorCard key={connector.id} connector={connector} />)}</section>}

            {quickstart && quickstartCurl && <section className="card quickstart" aria-label="Quick start">
                <div className="quickstart-head">
                    <div>
                        <h2>Quick start</h2>
                        <p>Call a live endpoint from your terminal.</p>
                    </div>
                </div>
                <div className="quickstart-code">
                    <pre>{quickstartCurl}</pre>
                    <CopyButton value={quickstartCurl} label="Copy command" showLabel />
                </div>
                <div className="quickstart-foot">
                    <Link href={`/connectors/${quickstart.id}/docs`} className="inline-link"><BookOpen size={14} style={{ verticalAlign: '-2px' }} /> {quickstart.name} API reference</Link>
                    <ul className="provider-status" aria-label="Provider status">
                        {providerStatus.map((provider) => <li key={provider.id} className={provider.configured ? 'is-ready' : 'is-missing'} title={provider.configured ? 'Credentials configured on the server' : 'No API key configured on the server'}>
                            <span className="status-dot" />{provider.label}<span className="muted">{provider.configured ? 'ready' : 'not configured'}</span>
                        </li>)}
                    </ul>
                </div>
            </section>}
        </>}
    </AppShell>;
}

function ConnectorCard({ connector }: { connector: Connector }) {
    const types = [...new Set(connector.input_schema.map((field) => field.type))];

    return <Link href={`/connectors/${connector.id}`} className="card connector-card" prefetch>
        <div className="connector-card-top"><ProviderBadge provider={connector.provider} /><StatusBadge status={connector.status} /></div>
        <div>
            <h3>{connector.name}<ArrowUpRight size={15} /></h3>
            <p className="connector-card-desc">{connector.description || <span className="muted">No description</span>}</p>
        </div>
        <div className="endpoint-line"><span className="method">POST</span><span>/api/connectors/{connector.slug}</span></div>
        <div className="chip-list">{types.map((type) => <span key={type} className="chip">{type}</span>)}<span className="chip mono">{connector.model}</span></div>
        <div className="connector-card-footer">
            <span><Layers size={13} />{connector.input_schema.length} input{connector.input_schema.length === 1 ? '' : 's'}</span>
            <span><Activity size={13} />{connector.request_count.toLocaleString()} req</span>
            <span><Clock size={13} /><RelativeTime value={connector.last_used_at} fallback="Never used" /></span>
        </div>
    </Link>;
}

function Metric({ icon, label, value, hint }: { icon: ReactNode; label: string; value: number; hint?: string }) {
    return <div className="metric">
        <div className="metric-label">{icon}{label}</div>
        <div className="metric-value">{value.toLocaleString()}{hint && <span className="metric-hint">{hint}</span>}</div>
    </div>;
}

function EmptyState() {
    return <section className="empty-state">
        <span className="empty-icon"><FilePlus2 size={24} /></span>
        <h2>Build your first connector</h2>
        <p>A connector wraps an AI model behind a stable, validated HTTP endpoint.</p>
        <div className="steps">
            <div className="step"><strong><span className="step-number">1</span>Describe inputs</strong><p>Text, numbers, images, files or JSON, with validation built in.</p></div>
            <div className="step"><strong><span className="step-number">2</span>Pick a model</strong><p>Choose Gemini or Groq and write the instructions.</p></div>
            <div className="step"><strong><span className="step-number">3</span>Call the endpoint</strong><p>Responses are checked against your JSON Schema.</p></div>
        </div>
        <Link href="/connectors/create" className="button button-primary button-lg"><Plus size={17} /> Create connector</Link>
    </section>;
}
