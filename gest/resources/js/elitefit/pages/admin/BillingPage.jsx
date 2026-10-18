import { useEffect, useState } from 'react';
import { IconAlertCircle, IconCheck, IconCreditCard, IconRefresh } from '@tabler/icons-react';
import api from '../../api/client';

function formatCurrency(amount, currency) {
    if (amount === null || amount === undefined) {
        return null;
    }

    try {
        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency, maximumFractionDigits: 2 }).format(amount);
    } catch {
        return `${new Intl.NumberFormat('fr-FR').format(amount)} ${currency}`;
    }
}

export default function BillingPage() {
    const [status, setStatus] = useState(null);
    const [error, setError] = useState('');
    const [notice, setNotice] = useState('');
    const [loading, setLoading] = useState(true);
    const [sending, setSending] = useState(false);

    const refresh = () => api.get('/billing/status').then(({ data }) => setStatus(data));

    useEffect(() => {
        let ignore = false;

        api.get('/billing/status')
            .then(({ data }) => {
                if (!ignore) {
                    setStatus(data);
                }
            })
            .catch((requestError) => {
                if (!ignore) {
                    setError(requestError.response?.data?.message || 'Impossible de charger le statut de l’abonnement.');
                }
            })
            .finally(() => {
                if (!ignore) {
                    setLoading(false);
                }
            });

        return () => {
            ignore = true;
        };
    }, []);

    const requestRenewal = async () => {
        setSending(true);
        setNotice('');
        setError('');
        try {
            const { data } = await api.post('/billing/renewal-request');
            setNotice(data.message);
        } catch (requestError) {
            setError(requestError.response?.data?.message || 'La demande n’a pas pu être envoyée.');
        } finally {
            setSending(false);
        }
    };

    if (loading) {
        return <div className="p-6 text-sm text-elite-muted">Chargement de votre abonnement…</div>;
    }

    const gym = status?.gym;
    const price = status?.price;

    return (
        <section className="space-y-6 p-4 sm:p-6 lg:p-8">
            <header>
                <p className="text-xs font-semibold uppercase tracking-[0.16em] text-elite-gold">Espace Arena</p>
                <h1 className="mt-2 text-2xl font-semibold text-elite-text">Mon abonnement</h1>
                <p className="mt-2 text-sm text-elite-muted">Statut et renouvellement de {gym?.name ?? 'votre salle'}.</p>
            </header>
            {error && <p role="alert" className="rounded-xl border border-red-400/30 bg-red-500/10 px-4 py-3 text-sm text-red-100">{error}</p>}
            {notice && <p role="status" className="rounded-xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">{notice}</p>}

            <div className="grid gap-5 lg:grid-cols-2">
                <article className="rounded-2xl border border-elite-border-light bg-elite-card p-6">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <p className="text-sm text-elite-muted">État actuel</p>
                            <h2 className="mt-2 text-xl font-semibold text-elite-text">
                                {!gym?.is_approved ? 'Validation en attente' : gym?.is_read_only ? 'Lecture seule' : 'Accès actif'}
                            </h2>
                        </div>
                        {gym?.is_read_only ? <IconAlertCircle className="text-amber-400" /> : <IconCheck className="text-emerald-400" />}
                    </div>
                    <p className="mt-4 text-sm leading-6 text-elite-muted">
                        {!gym?.is_approved
                            ? `Vous pouvez créer jusqu’à ${gym?.subscriptions_limit ?? 3} abonnements de membre avant validation. Créés : ${gym?.subscriptions_created ?? 0}.`
                            : gym?.is_read_only
                                ? 'Vos anciennes données restent consultables. Les modifications sont bloquées jusqu’à confirmation du renouvellement par le Super Admin.'
                                : `Salle validée${gym?.expires_at ? ` · échéance ${new Date(`${gym.expires_at}T00:00:00`).toLocaleDateString('fr-FR')}` : ''}.`}
                    </p>
                </article>

                <article className="rounded-2xl border border-elite-border-light bg-elite-card p-6">
                    <div className="flex items-center gap-3">
                        <span className="grid h-11 w-11 place-items-center rounded-xl bg-elite-gold/15 text-elite-gold"><IconCreditCard size={22} /></span>
                        <div>
                            <p className="text-sm text-elite-muted">Renouvellement Arena</p>
                            <h2 className="text-xl font-semibold text-elite-text">{formatCurrency(price?.local_amount, price?.currency_code) ?? `${new Intl.NumberFormat('fr-FR').format(price?.base_amount ?? 7999)} FCFA`}</h2>
                        </div>
                    </div>
                    <p className="mt-4 text-sm leading-6 text-elite-muted">
                        {price?.estimate
                            ? price.rate_source === 'parity'
                                ? 'Équivalent local indicatif, calculé depuis la parité XOF/EUR. Le règlement demandé reste en FCFA.'
                                : `Équivalent local indicatif, calculé à partir du taux quotidien Frankfurter (${price.rate_date ?? 'dernière disponibilité'}). Le règlement demandé reste en FCFA.`
                            : 'Tarif de référence. Le règlement manuel est effectué en FCFA.'}
                    </p>
                    <button type="button" onClick={requestRenewal} disabled={sending} className="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-elite-gold px-4 py-3 text-sm font-semibold text-slate-950 disabled:cursor-wait disabled:opacity-60">
                        {sending ? <IconRefresh className="animate-spin" size={18} /> : <IconCreditCard size={18} />}
                        {sending ? 'Envoi de la demande…' : 'Demander le renouvellement'}
                    </button>
                    <p className="mt-3 text-xs text-elite-muted">La salle sera réactivée par le Super Admin après confirmation du paiement manuel. Aucun paiement n’est prélevé sur cette page.</p>
                </article>
            </div>
            <button type="button" onClick={() => refresh()} className="text-sm text-elite-gold underline underline-offset-4">Actualiser le statut</button>
        </section>
    );
}
