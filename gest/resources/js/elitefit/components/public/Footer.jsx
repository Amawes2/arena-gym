import { Link } from 'react-router-dom';

export default function Footer() {
    return (
        <footer className="border-t border-elite-border bg-elite-bg px-6 py-8">
            <div className="mx-auto flex max-w-7xl flex-col gap-5 text-xs text-elite-muted sm:flex-row sm:items-center sm:justify-between">
                <p>© 2026 ARENA GYM</p>
                <nav aria-label="Informations légales" className="flex flex-wrap gap-x-6 gap-y-3">
                    <Link to="/politique-confidentialite" className="transition hover:text-elite-text">Confidentialité</Link>
                    <Link to="/cookies" className="transition hover:text-elite-text">Cookies</Link>
                    <Link to="/connexion" className="transition hover:text-elite-text">Connexion</Link>
                </nav>
            </div>
        </footer>
    );
}
