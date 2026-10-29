import {
    IconArrowUpRight,
    IconClipboardCheck,
    IconCreditCard,
    IconUsers,
} from '@tabler/icons-react';
import { Link } from 'react-router-dom';

const highlights = [
    {
        name: 'Membres & abonnements',
        description: 'Gardez les profils, les formules et les renouvellements au même endroit.',
        icon: IconUsers,
    },
    {
        name: 'Présences',
        description: 'Enregistrez les entrées de vos membres en quelques instants.',
        icon: IconClipboardCheck,
    },
    {
        name: 'Paiements',
        description: 'Suivez les paiements et l’activité de votre salle.',
        icon: IconCreditCard,
    },
];

function PrimaryLink({ to, children }) {
    return (
        <Link
            to={to}
            className="inline-flex min-h-11 items-center justify-center rounded-lg bg-elite-gold px-5 py-3 text-sm font-semibold text-[#111820] transition hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-elite-gold/60 focus:ring-offset-2 focus:ring-offset-elite-bg"
        >
            {children}
        </Link>
    );
}

export default function HomePage() {
    return (
        <>
            <section className="relative isolate overflow-hidden border-b border-elite-border px-6 py-20 sm:py-28">
                <div className="pointer-events-none absolute inset-0 -z-20 bg-[radial-gradient(ellipse_at_78%_35%,rgba(255,122,0,0.13),transparent_38%),linear-gradient(120deg,#0b0f14_12%,#111820_100%)]" />
                <div className="mx-auto max-w-4xl text-center">
                    <p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-elite-gold">
                        Gestion pour salles de sport
                    </p>
                    <h1 className="mt-5 text-4xl font-bold leading-[1.05] tracking-tight text-elite-text sm:text-6xl">
                        La gestion de votre salle,
                        <span className="mt-2 block text-elite-gold">au même endroit.</span>
                    </h1>
                    <div className="mt-8 flex flex-wrap justify-center gap-3">
                        <PrimaryLink to="/inscription-salle">
                            Créer ma salle <IconArrowUpRight size={17} className="ml-2" />
                        </PrimaryLink>
                        <Link
                            to="/connexion"
                            className="inline-flex min-h-11 items-center justify-center rounded-lg border border-white/15 px-5 py-3 text-sm font-semibold text-elite-text transition hover:border-elite-gold/60 hover:text-elite-gold"
                        >
                            Se connecter
                        </Link>
                    </div>
                </div>
            </section>

            <section id="fonctionnalites" aria-labelledby="highlights-title" className="bg-elite-bg-elevated px-6 py-14 sm:py-18">
                <div className="mx-auto max-w-6xl">
                    <div className="mb-7">
                        <h2 id="highlights-title" className="text-xl font-semibold text-elite-text sm:text-2xl">
                            L’essentiel pour gérer votre salle
                        </h2>
                    </div>
                    <div className="arena-feature-grid gap-3">
                        {highlights.map(({ name, description, icon: Icon }) => (
                            <article key={name} className="flex items-start gap-4 rounded-xl border border-elite-border-light bg-elite-card p-5">
                                <span className="arena-center-grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-elite-gold/10 text-elite-gold">
                                    <Icon size={20} stroke={1.7} />
                                </span>
                                <div>
                                    <h3 className="text-sm font-semibold text-elite-text">{name}</h3>
                                    <p className="mt-1 text-sm leading-6 text-elite-muted">{description}</p>
                                </div>
                            </article>
                        ))}
                    </div>
                </div>
            </section>
        </>
    );
}
