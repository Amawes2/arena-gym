import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import App from './App';
import ErrorBoundary from './components/ErrorBoundary';

const rootEl = document.getElementById('elitefit-root');

if (! rootEl) {
    document.body.innerHTML =
        '<p style="padding:2rem;color:#d04040;font-family:sans-serif">Erreur : conteneur #elitefit-root introuvable.</p>';
} else {
    try {
        createRoot(rootEl).render(
            <StrictMode>
                <ErrorBoundary>
                    <BrowserRouter>
                        <App />
                    </BrowserRouter>
                </ErrorBoundary>
            </StrictMode>,
        );
    } catch (error) {
        console.error('EliteFit mount error:', error);
        rootEl.innerHTML = `<div style="padding:2rem;color:#d04040;font-family:sans-serif"><strong>Erreur au démarrage</strong><pre style="margin-top:1rem;white-space:pre-wrap;color:#e8e2d4">${error?.message ?? error}</pre></div>`;
    }
}
