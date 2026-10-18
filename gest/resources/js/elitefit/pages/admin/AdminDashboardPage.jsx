import { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import {
    IconAlertTriangle,
    IconArrowUpRight,
    IconBell,
    IconClipboardCheck,
    IconCreditCard,
    IconUsers,
} from '@tabler/icons-react';
import api, { formatFcfa } from '../../api/client';

function AnimatedValue({ value, currency = false, loading = false }) {
    const target = Number(value);
    const [currentValue, setCurrentValue] = useState(0);
    const currentValueRef = useRef(0);

    useEffect(() => {
        if (!Number.isFinite(target)) {
            return undefined;
        }

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            currentValueRef.current = target;
            setCurrentValue(target);

            return undefined;
        }

        const startValue = currentValueRef.current;
        const startTime = performance.now();
        let animationFrame;

        const updateValue = (time) => {
            const progress = Math.min((time - startTime) / 900, 1);
            const easedProgress = 1 - ((1 - progress) ** 3);
            const nextValue = startValue + ((target - startValue) * easedProgress);

            currentValueRef.current = nextValue;
            setCurrentValue(nextValue);

            if (progress < 1) {
                animationFrame = window.requestAnimationFrame(updateValue);
            }
        };

        animationFrame = window.requestAnimationFrame(updateValue);

        return () => window.cancelAnimationFrame(animationFrame);
    }, [target]);

    if (loading) {
        return <div className="arena-skeleton arena-skeleton-value" role="status" aria-label="Chargement de l’indicateur" />;
    }

    if (!Number.isFinite(target)) {
        return value;
    }

    const formattedValue = new Intl.NumberFormat('fr-FR').format(Math.round(currentValue));

    return <span className="arena-kpi-value">{currency ? `${formattedValue} FCFA` : formattedValue}</span>;
}

function StatCard({ label, value, note, noteTone = 'muted', icon: Icon, currency = false, loading = false }) {
    const noteClasses = {
        success: 'text-success',
        warning: 'text-warning-emphasis',
        danger: 'text-danger',
        muted: 'arena-admin-text-muted',
    };

    return (
        <article className="card h-full">
            <div className="card-body">
                <div className="d-flex align-items-start justify-content-between gap-3">
                    <p className="mb-0 small arena-admin-text-muted">{label}</p>
                    <span className="arena-admin-stat-icon d-inline-flex flex-shrink-0 align-items-center justify-content-center rounded-3">
                        <Icon size={21} stroke={1.7} />
                    </span>
                </div>
                <p className="mt-3 mb-1 fs-3 fw-bold text-dark">
                    <AnimatedValue value={value} currency={currency} loading={loading} />
                </p>
                {note && <span className={`small fw-semibold ${noteClasses[noteTone]}`}>{note}</span>}
            </div>
        </article>
    );
}

function PanelCard({ title, subtitle, action, children, className = '' }) {
    return (
        <section className={`card h-full ${className}`}>
            <div className="card-header d-flex align-items-center justify-content-between gap-3">
                <div>
                    <h2 className="mb-0 fs-6 fw-bold text-dark">{title}</h2>
                    {subtitle && <p className="mt-1 mb-0 small arena-admin-text-muted">{subtitle}</p>}
                </div>
                {action}
            </div>
            {children}
        </section>
    );
}

function AttendanceChart({ rows }) {
    const chartWidth = 660;
    const chartHeight = 190;
    const chartTop = 14;
    const chartBottom = 154;
    const maxPresence = Math.max(...rows.map((row) => Number(row.total) || 0), 1);
    const points = rows.map((row, index) => {
        const x = rows.length < 2 ? chartWidth / 2 : 20 + (index * (chartWidth - 40)) / (rows.length - 1);
        const y = chartBottom - ((Number(row.total) || 0) / maxPresence) * (chartBottom - chartTop);

        return { ...row, x, y, value: Number(row.total) || 0 };
    });
    const linePath = points.map(({ x, y }, index) => `${index === 0 ? 'M' : 'L'} ${x} ${y}`).join(' ');
    const areaPath = points.length > 0
        ? `${linePath} L ${points.at(-1).x} ${chartBottom} L ${points[0].x} ${chartBottom} Z`
        : '';

    return (
        <div className="px-3 md:px-4 pb-3">
            {points.length === 0 ? (
                <p className="mb-0 py-5 text-center small arena-admin-text-muted">Aucune présence enregistrée sur cette période.</p>
            ) : (
                <>
                    <svg className="arena-admin-chart" viewBox={`0 0 ${chartWidth} ${chartHeight}`} role="img" aria-label="Évolution des présences sur les sept derniers jours">
                        <defs>
                            <linearGradient id="arena-chart-fill" x1="0" x2="0" y1="0" y2="1">
                                <stop offset="0%" stopColor="#2185d5" stopOpacity="0.2" />
                                <stop offset="100%" stopColor="#2185d5" stopOpacity="0.01" />
                            </linearGradient>
                        </defs>
                        {[20, 65, 110, chartBottom].map((y) => (
                            <line key={y} x1="18" x2={chartWidth - 18} y1={y} y2={y} className="arena-admin-chart-grid" />
                        ))}
                        <path d={areaPath} pathLength="1" className="arena-admin-chart-area" />
                        <path d={linePath} pathLength="1" className="arena-admin-chart-line" />
                        {points.map(({ date, jour, total, x, y }) => (
                            <circle key={date} cx={x} cy={y} r="4" className="arena-admin-chart-point">
                                <title>{`${jour} : ${total} présence(s)`}</title>
                            </circle>
                        ))}
                        {points.map(({ date, jour, x }) => (
                            <text key={`${date}-label`} x={x} y="182" textAnchor="middle" fill="#8994a0" fontSize="11">
                                {jour.slice(0, 3)}
                            </text>
                        ))}
                    </svg>
                    <div className="d-flex align-items-center gap-2 small arena-admin-text-muted">
                        <span className="d-inline-block rounded-circle bg-primary" style={{ width: 8, height: 8 }} />
                        Présences enregistrées
                    </div>
                </>
            )}
        </div>
    );
}

function MemberAvatar({ member }) {
    const initials = `${member.prenom?.[0] ?? ''}${member.nom?.[0] ?? ''}`.toUpperCase();

    return (
        <span className="arena-admin-avatar d-inline-flex flex-shrink-0 align-items-center justify-content-center rounded-circle">
            {initials || 'M'}
        </span>
    );
}

export default function AdminDashboardPage() {
    const [data, setData] = useState(null);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let ignore = false;
        setIsLoading(true);
        setError('');

        api.get('/admin/stats')
            .then(({ data: responseData }) => {
                if (!ignore) {
                    setData(responseData);
                }
            })
            .catch((requestError) => {
                if (!ignore) {
                    setError(requestError.response?.data?.message || 'Impossible de charger les données du tableau de bord.');
                }
            })
            .finally(() => {
                if (!ignore) {
                    setIsLoading(false);
                }
            });

        return () => {
            ignore = true;
        };
    }, []);

    const kpis = data?.kpis;
    const presenceRows = data?.presences_7j ?? [];
    const dateLabel = new Intl.DateTimeFormat('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date());
    const revenueDelta = Number(kpis?.ca_mois_delta_pct ?? 0);
    const revenueNote = `${revenueDelta > 0 ? '+' : ''}${revenueDelta}% par rapport au mois dernier`;
    const revenueTone = revenueDelta > 0 ? 'success' : revenueDelta < 0 ? 'danger' : 'muted';

    return (
        <div className="container-fluid px-3 xl:px-4 py-4 xl:py-5">
            <header className="mb-4 d-flex flex-column md:flex-row md:align-items-end justify-content-between gap-3">
                <div>
                    <p className="mb-2 small fw-bold text-uppercase text-primary" style={{ letterSpacing: '0.14em' }}>Vue d’ensemble</p>
                    <h1 className="arena-admin-page-title mb-1 fw-bold text-dark">Tableau de bord</h1>
                    <p className="mb-0 small arena-admin-text-muted">Voici l’activité de votre salle pour aujourd’hui.</p>
                </div>
                <div className="d-flex align-items-center gap-3">
                    <span className="small text-capitalize arena-admin-text-muted">{dateLabel}</span>
                    <Link to="/admin/membres" className="btn btn-solid theme-primary d-inline-flex align-items-center gap-2">
                        <IconUsers size={17} />
                        <span>Nouveau membre</span>
                    </Link>
                </div>
            </header>

            {error && (
                <div role="alert" className="alert alert-danger mb-4">
                    {error}
                </div>
            )}

            <div className="row g-3">
                <div className="col-12 sm:col-6 xl:col-3">
                    <StatCard
                        label="Membres avec abonnement actif"
                        value={kpis?.membres_actifs ?? '—'}
                        loading={isLoading}
                        note={kpis ? `+${kpis.membres_actifs_delta} nouveau(x) ce mois` : null}
                        noteTone="success"
                        icon={IconUsers}
                    />
                </div>
                <div className="col-12 sm:col-6 xl:col-3">
                    <StatCard
                        label="Présences aujourd’hui"
                        value={kpis?.presences_aujourdhui ?? '—'}
                        loading={isLoading}
                        note="Pointages de la journée"
                        icon={IconClipboardCheck}
                    />
                </div>
                <div className="col-12 sm:col-6 xl:col-3">
                    <StatCard
                        label="Revenus encaissés ce mois"
                        value={kpis?.ca_mois ?? '—'}
                        currency
                        loading={isLoading}
                        note={kpis ? revenueNote : null}
                        noteTone={revenueTone}
                        icon={IconCreditCard}
                    />
                </div>
                <div className="col-12 sm:col-6 xl:col-3">
                    <StatCard
                        label="Abonnements à renouveler sous 7 jours"
                        value={kpis?.abonnements_expirant ?? '—'}
                        loading={isLoading}
                        note={kpis ? 'Échéances à suivre' : null}
                        noteTone="warning"
                        icon={IconAlertTriangle}
                    />
                </div>
            </div>

            <div className="row g-3 mt-1">
                <div className="col-12 xl:col-8">
                    <PanelCard
                        title="Fréquentation de la salle"
                        subtitle="Présences enregistrées sur les sept derniers jours"
                        action={<Link to="/admin/rapports" className="btn btn-outline theme-primary btn-sm">Voir les rapports</Link>}
                    >
                        {isLoading ? (
                            <div className="card-body d-grid gap-3 py-5" role="status" aria-label="Chargement des présences">
                                <div className="arena-skeleton arena-skeleton-line" />
                                <div className="arena-skeleton arena-skeleton-value w-100" />
                                <div className="arena-skeleton arena-skeleton-line w-75" />
                            </div>
                        ) : (
                            <AttendanceChart rows={presenceRows} />
                        )}
                    </PanelCard>
                </div>
                <div className="col-12 xl:col-4">
                    <PanelCard
                        title="Derniers membres inscrits"
                        subtitle="Les nouveaux profils de votre salle"
                        action={<Link to="/admin/membres" className="btn btn-text btn-sm text-primary">Tout voir <IconArrowUpRight size={15} /></Link>}
                    >
                        {isLoading ? (
                            <div className="card-body d-grid gap-3" role="status" aria-label="Chargement des membres">
                                {[0, 1, 2].map((row) => (
                                    <div key={row} className="d-flex align-items-center gap-3">
                                        <div className="arena-skeleton rounded-circle" style={{ width: 38, height: 38 }} />
                                        <div className="d-grid flex-grow-1 gap-2">
                                            <div className="arena-skeleton arena-skeleton-line w-75" />
                                            <div className="arena-skeleton arena-skeleton-line w-50" />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (data?.derniers_membres ?? []).length === 0 ? (
                            <div className="card-body py-5 text-center small arena-admin-text-muted">Aucun membre enregistré pour le moment.</div>
                        ) : (
                            <ul className="list-group list-group-flush">
                                {data.derniers_membres.map((member) => (
                                    <li key={member.id} className="list-group-item d-flex align-items-center gap-3 px-4 py-3">
                                        <MemberAvatar member={member} />
                                        <div className="min-w-0 flex-grow-1">
                                            <p className="mb-1 text-truncate small fw-semibold text-dark">{member.prenom} {member.nom}</p>
                                            <p className="mb-0 small arena-admin-text-muted">{member.inscrit_il_y_a || 'Inscription récente'}</p>
                                        </div>
                                        <span className="badge rounded-pill arena-admin-status">{member.badge}</span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </PanelCard>
                </div>
            </div>

            <div className="row g-3 mt-1">
                <div className="col-12">
                    <PanelCard
                        title="Dernières commandes"
                        subtitle="Suivez les achats et leur statut"
                        action={<Link to="/admin/commandes" className="btn btn-outline theme-primary btn-sm">Toutes les commandes</Link>}
                    >
                        {isLoading ? (
                            <div className="card-body d-grid gap-3" role="status" aria-label="Chargement des commandes">
                                {[0, 1, 2].map((row) => <div key={row} className="arena-skeleton arena-skeleton-line" />)}
                            </div>
                        ) : (data?.dernieres_commandes ?? []).length === 0 ? (
                            <div className="card-body py-4 small arena-admin-text-muted">Aucune commande enregistrée pour le moment.</div>
                        ) : (
                            <div className="table-responsive">
                                <table className="table arena-admin-table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th className="px-4 py-3">Commande</th>
                                            <th className="px-4 py-3">Client</th>
                                            <th className="px-4 py-3">Produits</th>
                                            <th className="px-4 py-3">Statut</th>
                                            <th className="px-4 py-3 text-end">Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {data.dernieres_commandes.map((order) => (
                                            <tr key={order.id}>
                                                <td data-label="Commande" className="px-4 py-3 fw-semibold text-dark">{order.id}</td>
                                                <td data-label="Client" className="px-4 py-3">{order.client}</td>
                                                <td data-label="Produits" className="px-4 py-3">{order.produits}</td>
                                                <td data-label="Statut" className="px-4 py-3"><span className="badge rounded-pill text-bg-light">{order.statut}</span></td>
                                                <td data-label="Montant" className="px-4 py-3 text-end fw-semibold">{formatFcfa(order.montant)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </PanelCard>
                </div>
            </div>
        </div>
    );
}
