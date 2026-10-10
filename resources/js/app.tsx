import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import type { ComponentType } from 'react';

createInertiaApp({
    resolve: async (name) => {
        const pages = import.meta.glob<{ default: ComponentType }>(`./pages/**/*.tsx`);
        const page = await pages[`./pages/${name}.tsx`]!();

        return page.default;
    },
    setup({ el, App, props }) {
        if (!el) return;
        createRoot(el).render(<App {...props} />);
    },
    progress: { color: '#2563eb' },
});
