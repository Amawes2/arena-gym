import { useEffect, useRef } from 'react';
import { useLocation } from 'react-router-dom';

export default function RouteTransition({ children, className = '' }) {
    const location = useLocation();
    const pageRef = useRef(null);
    const previousPathRef = useRef(null);
    const hasChangedPage = previousPathRef.current !== null && previousPathRef.current !== location.pathname;

    previousPathRef.current = location.pathname;

    useEffect(() => {
        const page = pageRef.current;
        const autofocusElement = page?.querySelector(
            '[autofocus], input:not([type="hidden"]):not([tabindex="-1"]):not([name="website"]), select:not(:disabled), textarea:not(:disabled)',
        );

        (autofocusElement ?? page)?.focus({ preventScroll: true });
    }, [location.pathname]);

    return (
        <div
            key={location.pathname}
            ref={pageRef}
            className={`${hasChangedPage ? 'arena-route-enter' : ''} ${className}`}
            tabIndex="-1"
        >
            {children}
        </div>
    );
}
