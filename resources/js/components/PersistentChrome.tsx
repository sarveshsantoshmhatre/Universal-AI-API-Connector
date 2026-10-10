import { PageBackdrop } from './PageBackdrop';
import ClickSpark from './reactbits/ClickSpark';
import { SmoothScroll } from './SmoothScroll';
import { useTheme } from './useTheme';

/**
 * Site-wide layers that must survive Inertia page visits: the WebGL background, click sparks and the smooth-scroll
 * engine. Mounted once in its own React root, so navigating never tears down and rebuilds the WebGL scene.
 */
export function PersistentChrome() {
    const theme = useTheme();

    return <>
        <PageBackdrop />
        <ClickSpark sparkColor={theme === 'dark' ? '#5eead4' : '#0f766e'} sparkSize={8} sparkRadius={20} sparkCount={7} duration={420} />
        <SmoothScroll />
    </>;
}
