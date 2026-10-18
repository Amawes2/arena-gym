import { useEffect, useRef, useState } from 'react';

let turnstileScriptPromise;

function loadTurnstileScript() {
    if (window.turnstile) {
        return Promise.resolve();
    }

    if (!turnstileScriptPromise) {
        const scriptPromise = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
            script.async = true;
            script.defer = true;
            script.onload = resolve;
            script.onerror = () => {
                script.remove();
                reject(new Error('Turnstile could not be loaded.'));
            };
            document.head.appendChild(script);
        });

        turnstileScriptPromise = scriptPromise.catch((error) => {
            turnstileScriptPromise = undefined;
            throw error;
        });
    }

    return turnstileScriptPromise;
}

export default function TurnstileWidget({ action, onToken }) {
    const containerRef = useRef(null);
    const onTokenRef = useRef(onToken);
    const siteKey = document.querySelector('meta[name="turnstile-site-key"]')?.content;
    const isEnabled = document.querySelector('meta[name="turnstile-enabled"]')?.content === 'true';
    const isReady = document.querySelector('meta[name="turnstile-ready"]')?.content === 'true';
    const [widgetError, setWidgetError] = useState('');
    const [retryKey, setRetryKey] = useState(0);

    useEffect(() => {
        onTokenRef.current = onToken;
    }, [onToken]);

    useEffect(() => {
        if (!siteKey || !isReady) {
            return undefined;
        }

        let widgetId;
        let cancelled = false;

        loadTurnstileScript()
            .then(() => {
                if (cancelled || !containerRef.current) {
                    return;
                }

                widgetId = window.turnstile.render(containerRef.current, {
                    sitekey: siteKey,
                    action,
                    responseField: false,
                    callback: (token) => {
                        setWidgetError('');
                        onTokenRef.current(token);
                    },
                    'expired-callback': () => {
                        setWidgetError('La vérification a expiré. Recommencez-la.');
                        onTokenRef.current('');
                    },
                    'error-callback': () => {
                        setWidgetError('La vérification anti-robot a échoué. Réessayez.');
                        onTokenRef.current('');
                    },
                });
            })
            .catch(() => {
                if (!cancelled) {
                    setWidgetError('Turnstile ne se charge pas. Vérifiez votre connexion, puis réessayez.');
                    onTokenRef.current('');
                }
            });

        return () => {
            cancelled = true;

            if (widgetId !== undefined && window.turnstile) {
                window.turnstile.remove(widgetId);
            }
        };
    }, [action, isReady, retryKey, siteKey]);

    if (!isEnabled) {
        return null;
    }

    if (!siteKey || !isReady) {
        return <p role="alert" className="mt-5 text-sm text-elite-danger">La protection anti-robot est mal configurée. Contactez l’administrateur du site.</p>;
    }

    return (
        <div className="mt-5 min-h-16" aria-label="Vérification anti-robot">
            <div ref={containerRef} />
            {widgetError && (
                <div className="mt-2">
                    <p role="alert" className="text-sm text-elite-danger">{widgetError}</p>
                    <button
                        type="button"
                        className="mt-1 text-sm text-elite-gold hover:underline"
                        onClick={() => {
                            setWidgetError('');
                            onTokenRef.current('');
                            setRetryKey((current) => current + 1);
                        }}
                    >
                        Réessayer
                    </button>
                </div>
            )}
        </div>
    );
}
