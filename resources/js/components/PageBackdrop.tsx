import { lazy, Suspense, useEffect, useState } from 'react';
import Grainient from './reactbits/Grainient';
import { usePrefersReducedMotion, useTheme } from './useTheme';

const HyperspeedBackdrop = lazy(() => import('./HyperspeedBackdrop'));

/**
 * Animated background fixed behind every page.
 * Dark theme: a teal light-trail road. Light theme, reduced motion, or ?bg=grainient:
 * a slow ocean-toned gradient.
 */
export function PageBackdrop() {
    const theme = useTheme();
    const reducedMotion = usePrefersReducedMotion();
    const dark = theme === 'dark';
    const preferGradient = typeof window !== 'undefined' && new URLSearchParams(window.location.search).get('bg') === 'grainient';
    const [idle, setIdle] = useState(false);

    // Start the WebGL road only after the page has painted, so it never competes with first render (LCP).
    useEffect(() => {
        const start = () => setIdle(true);
        if (typeof window.requestIdleCallback === 'function') {
            const handle = window.requestIdleCallback(start, { timeout: 1500 });
            return () => window.cancelIdleCallback(handle);
        }
        const timer = setTimeout(start, 600);
        return () => clearTimeout(timer);
    }, []);

    if (dark && !reducedMotion && !preferGradient) {
        return <div className="page-backdrop is-dark is-hyperspeed" aria-hidden="true">
            {idle && <Suspense fallback={null}><HyperspeedBackdrop /></Suspense>}
        </div>;
    }

    return <div className={`page-backdrop is-${theme}`} aria-hidden="true">
        <Grainient
            key={theme}
            color1={dark ? '#071317' : '#d9f4ef'}
            color2={dark ? '#0f766e' : '#99e5d8'}
            color3={dark ? '#0c191e' : '#f2f7f7'}
            timeSpeed={reducedMotion ? 0 : 0.2}
            warpStrength={1}
            grainAmount={dark ? 0.06 : 0.035}
            grainAnimated={!reducedMotion}
            contrast={1.05}
            saturation={dark ? 0.95 : 0.75}
            lightMode={!dark}
        />
    </div>;
}
