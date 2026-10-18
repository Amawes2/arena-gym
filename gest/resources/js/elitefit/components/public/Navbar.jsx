import { Link } from 'react-router-dom';
import ArenaLogo from '../brand/ArenaLogo';

function ArenaBrand() {
    return (
        <span className="arena-brand inline-flex items-center gap-2.5">
            <ArenaLogo size={40} />
            <span className="arena-brand-name text-lg font-bold tracking-[0.18em] text-elite-text">
                {'ARENA'.split('').map((letter, index) => <span key={`${letter}-${index}`}>{letter}</span>)}
            </span>
        </span>
    );
}

export default function Navbar() {
    return (
        <header className="sticky top-0 z-50 border-b border-elite-border bg-elite-bg/95 backdrop-blur-md">
            <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-3.5 sm:px-6">
                <Link to="/" aria-label="ARENA GYM — accueil" className="shrink-0">
                    <ArenaBrand />
                </Link>
                <nav aria-label="Navigation principale" className="hidden items-center gap-7 md:flex">
                    <Link to="/#fonctionnalites" className="text-sm text-elite-muted transition hover:text-elite-text">Fonctionnalités</Link>
                </nav>
                <div className="hidden items-center gap-3 sm:flex">
                    <Link to="/connexion" className="inline-flex items-center justify-center rounded-lg bg-elite-gold px-4 py-2.5 text-xs font-semibold text-[#111820] transition hover:brightness-110">
                        Connexion
                    </Link>
                </div>
                <Link to="/connexion" className="inline-flex items-center justify-center rounded-lg bg-elite-gold px-3.5 py-2.5 text-xs font-semibold text-[#111820] transition hover:brightness-110 sm:hidden">
                    Connexion
                </Link>
            </div>
        </header>
    );
}
