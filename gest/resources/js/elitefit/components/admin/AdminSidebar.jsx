import { NavLink } from 'react-router-dom';
import {
    IconArrowUpRight,
    IconBox,
    IconChartBar,
    IconClipboardCheck,
    IconLayoutDashboard,
    IconPackage,
    IconShoppingCart,
    IconUsers,
    IconIdBadge2,
    IconX,
    IconHelpCircle,
    IconCreditCard,
} from '@tabler/icons-react';
import ArenaLogo from '../brand/ArenaLogo';

const sections = [
    {
        items: [{ to: '/admin', label: 'Tableau de bord', icon: IconLayoutDashboard, end: true }],
    },
    {
        title: 'SALLE DE SPORT',
        items: [
            { to: '/admin/membres', label: 'Membres', icon: IconUsers },
            { to: '/admin/abonnements', label: 'Abonnements', icon: IconIdBadge2 },
            { to: '/admin/presences', label: 'Présences', icon: IconClipboardCheck },
        ],
    },
    {
        title: 'BOUTIQUE',
        items: [
            { to: '/admin/produits', label: 'Produits', icon: IconBox },
            { to: '/admin/commandes', label: 'Commandes', icon: IconShoppingCart },
            { to: '/admin/stock', label: 'Stock', icon: IconPackage },
        ],
    },
    {
        title: 'SUIVI',
        items: [
            { to: '/admin/rapports', label: 'Rapports', icon: IconChartBar },
                { to: '/admin/abonnement-saas', label: 'Mon abonnement', icon: IconCreditCard },
                { to: '/admin/aide', label: 'Aide & échanges', icon: IconHelpCircle },
        ],
    },
];

export default function AdminSidebar({ isOpen, onClose }) {
    return (
        <aside className={`arena-admin-sidebar fixed inset-y-0 left-0 z-40 flex w-[260px] shrink-0 flex-col border-r transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 ${isOpen ? 'translate-x-0' : '-translate-x-full'}`}>
            <div className="flex items-center justify-between border-b border-white/10 px-5 py-5">
                <a href="/" aria-label="ARENA GYM — accueil" className="flex items-center gap-2.5">
                    <ArenaLogo size={40} />
                    <span className="text-sm font-bold tracking-[0.17em] text-elite-text">ARENA</span>
                </a>
                <button type="button" aria-label="Fermer le menu" onClick={onClose} className="arena-center-grid h-8 w-8 place-items-center rounded-lg text-elite-muted hover:bg-white/10 lg:hidden">
                    <IconX size={18} />
                </button>
            </div>
            <div className="border-b border-white/10 px-5 py-3">
                <p className="text-[10px] uppercase tracking-[0.18em] text-elite-muted">Gestion de salle</p>
            </div>
            <nav className="flex-1 overflow-y-auto px-3 py-4" aria-label="Navigation d’administration">
                {sections.map((section) => (
                    <div key={section.title ?? 'main'} className="mb-6">
                        {section.title && (
                            <p className="mb-2 px-3 text-[10px] font-semibold tracking-[0.12em] text-elite-disabled">{section.title}</p>
                        )}
                        <ul className="space-y-1">
                            {section.items.map(({ to, label, icon: Icon, end }) => (
                                <li key={to}>
                                    <NavLink
                                        to={to}
                                        end={end}
                                        onClick={onClose}
                                        className={({ isActive }) =>
                                            `flex min-h-[44px] items-center gap-3 rounded-lg border-l-2 px-3 py-2.5 text-sm transition-[transform,opacity] duration-200 ${
                                                isActive
                                                    ? 'border-elite-gold bg-elite-gold text-[#111820]'
                                                    : 'border-transparent text-elite-muted hover:bg-white/7 hover:text-white'
                                            }`
                                        }
                                    >
                                        <Icon size={18} stroke={1.7} />
                                        {label}
                                    </NavLink>
                                </li>
                            ))}
                        </ul>
                    </div>
                ))}
            </nav>
            <div className="border-t border-white/10 p-4">
                <a href="/backoffice/login" className="flex items-center justify-between gap-2 rounded-lg border border-white/10 px-3 py-2.5 text-xs font-medium text-elite-muted transition hover:border-elite-gold/40 hover:text-elite-gold">
                    <span>Backoffice Filament</span>
                    <IconArrowUpRight size={16} stroke={1.7} />
                </a>
            </div>
        </aside>
    );
}
