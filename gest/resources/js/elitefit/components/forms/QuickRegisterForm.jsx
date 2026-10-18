import { useEffect, useMemo, useState } from 'react';
import api from '../../api/client';
import HoneypotField from './HoneypotField';
import TurnstileWidget from './TurnstileWidget';
import FieldError from './FieldError';
import Button from '../ui/Button';

function addDays(date, days) {
    const copy = new Date(date);
    copy.setDate(copy.getDate() + Number(days || 0));
    return copy;
}

function formatDate(date) {
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(date);
}

export default function QuickRegisterForm({ type = 'seance_unique', forfaits = [], initialForfaitId = '', onSuccess }) {
    const [form, setForm] = useState({ prenom: '', nom: '', telephone: '', forfait_id: '', website: '', turnstile_token: '' });
    const [widgetKey, setWidgetKey] = useState(0);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [fieldErrors, setFieldErrors] = useState({});
    const [success, setSuccess] = useState('');

    useEffect(() => {
        if (type === 'abonnement' && initialForfaitId) {
            setForm((current) => ({ ...current, forfait_id: String(initialForfaitId) }));
        }
    }, [initialForfaitId, type]);

    const selectedForfait = useMemo(
        () => forfaits.find((forfait) => String(forfait.id) === String(form.forfait_id)),
        [forfaits, form.forfait_id],
    );

    const update = (field) => (e) => {
        setForm((f) => ({ ...f, [field]: e.target.value }));
        setFieldErrors((current) => ({ ...current, [field]: undefined }));
    };

    const submit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');
        setFieldErrors({});
        setSuccess('');

        try {
            const payload = {
                ...form,
                type,
                forfait_id: type === 'abonnement' ? Number(form.forfait_id) : undefined,
            };
            const { data } = await api.post('/auth/register-rapide', payload);
            setSuccess(data.message);
            setForm({ prenom: '', nom: '', telephone: '', forfait_id: initialForfaitId ? String(initialForfaitId) : '', website: '', turnstile_token: '' });
            onSuccess?.(data.data);
        } catch (err) {
            const validationErrors = err.response?.data?.errors;
            if (validationErrors) {
                setFieldErrors(Object.fromEntries(Object.entries(validationErrors).map(([field, messages]) => [field, Array.isArray(messages) ? messages[0] : messages])));
                const unassignedError = Object.entries(validationErrors).find(([field]) => !['prenom', 'nom', 'telephone', 'forfait_id'].includes(field))?.[1];
                if (unassignedError) {
                    setError(Array.isArray(unassignedError) ? unassignedError[0] : unassignedError);
                }
            } else {
                setError(err.response?.data?.message || 'Une erreur est survenue.');
            }
        } finally {
            setLoading(false);
            setForm((current) => ({ ...current, turnstile_token: '' }));
            setWidgetKey((current) => current + 1);
        }
    };

    const inputClass =
        'w-full rounded-[8px] border-[0.5px] border-elite-border-light bg-elite-bg-elevated px-4 py-3 text-elite-text placeholder:text-elite-disabled focus:border-elite-gold focus:outline-none';

    return (
        <form onSubmit={submit} className="relative space-y-4">
            <HoneypotField value={form.website} onChange={update('website')} />
            <div className="arena-form-fields-grid gap-4">
                <div>
                    <label htmlFor="quick-register-first-name" className="mb-1.5 block text-xs text-elite-muted">Prénom *</label>
                    <input
                        id="quick-register-first-name"
                        name="prenom"
                        className={inputClass}
                        value={form.prenom}
                        onChange={update('prenom')}
                        required
                        maxLength={50}
                        autoFocus
                        aria-invalid={Boolean(fieldErrors.prenom)}
                        aria-describedby={fieldErrors.prenom ? 'quick-register-first-name-error' : undefined}
                    />
                    <FieldError id="quick-register-first-name-error" message={fieldErrors.prenom} />
                </div>
                <div>
                    <label htmlFor="quick-register-last-name" className="mb-1.5 block text-xs text-elite-muted">Nom *</label>
                    <input id="quick-register-last-name" name="nom" className={inputClass} value={form.nom} onChange={update('nom')} required maxLength={50} aria-invalid={Boolean(fieldErrors.nom)} aria-describedby={fieldErrors.nom ? 'quick-register-last-name-error' : undefined} />
                    <FieldError id="quick-register-last-name-error" message={fieldErrors.nom} />
                </div>
            </div>
            <div>
                <label htmlFor="quick-register-phone" className="mb-1.5 block text-xs text-elite-muted">Téléphone *</label>
                <input
                    id="quick-register-phone"
                    name="telephone"
                    className={inputClass}
                    type="tel"
                    placeholder="07 XX XX XX XX"
                    value={form.telephone}
                    onChange={update('telephone')}
                    required
                    aria-invalid={Boolean(fieldErrors.telephone)}
                    aria-describedby={fieldErrors.telephone ? 'quick-register-phone-error' : undefined}
                />
                <FieldError id="quick-register-phone-error" message={fieldErrors.telephone} />
            </div>

            <TurnstileWidget key={widgetKey} action="quick_registration" onToken={(token) => setForm((current) => ({ ...current, turnstile_token: token }))} />

            {type === 'abonnement' && (
                <div>
                    <label className="mb-2 block text-xs text-elite-muted">Forfait *</label>
                    {forfaits.length === 0 ? (
                        <p className="rounded-[8px] bg-elite-warning-bg px-4 py-3 text-sm text-elite-gold">
                            Aucun forfait actif disponible.
                        </p>
                    ) : (
                        <div className="arena-form-fields-grid arena-form-fields-grid-three gap-3">
                            {forfaits.map((f) => (
                                <label
                                    key={f.id}
                                    className={`cursor-pointer rounded-[12px] border-[0.5px] p-4 transition ${
                                        String(form.forfait_id) === String(f.id)
                                            ? 'border-elite-gold bg-elite-gold/10'
                                            : 'border-elite-border-light hover:border-elite-gold/50'
                                    } ${f.featured ? 'ring-1 ring-elite-gold' : ''}`}
                                >
                                    <input
                                        type="radio"
                                        name="forfait_id"
                                        className="sr-only"
                                        value={f.id}
                                        checked={String(form.forfait_id) === String(f.id)}
                                        onChange={update('forfait_id')}
                                        required
                                    />
                                    <p className="text-sm text-elite-text">{f.nom}</p>
                                    <p className="mt-1 text-elite-gold">
                                        {new Intl.NumberFormat('fr-FR').format(f.prix)} FCFA
                                    </p>
                                </label>
                            ))}
                        </div>
                        <FieldError id="quick-register-plan-error" message={fieldErrors.forfait_id} />
                    )}
                </div>
            )}

            {type === 'abonnement' && selectedForfait && (
                <div className="arena-form-fields-grid arena-form-fields-grid-three gap-3 rounded-[12px] border-[0.5px] border-elite-border-light bg-elite-bg-elevated p-4 text-sm">
                    <div>
                        <p className="text-xs text-elite-muted">Début</p>
                        <p className="mt-1 text-elite-text">{formatDate(new Date())}</p>
                    </div>
                    <div>
                        <p className="text-xs text-elite-muted">Fin</p>
                        <p className="mt-1 text-elite-text">{formatDate(addDays(new Date(), selectedForfait.duree_jours))}</p>
                    </div>
                    <div>
                        <p className="text-xs text-elite-muted">Montant</p>
                        <p className="mt-1 text-elite-gold">
                            {new Intl.NumberFormat('fr-FR').format(selectedForfait.prix)} FCFA
                        </p>
                    </div>
                </div>
            )}

            {error && <p className="rounded-[8px] bg-elite-danger-bg px-4 py-2 text-sm text-elite-danger">{error}</p>}
            {success && (
                <p className="rounded-[8px] bg-elite-success-bg px-4 py-2 text-sm text-elite-success">{success}</p>
            )}

            <Button type="submit" className="w-full" disabled={loading || (type === 'abonnement' && !form.forfait_id)}>
                {loading ? 'Enregistrement...' : 'Envoyer la demande'}
            </Button>
            <p className="text-center text-xs text-elite-muted">Votre accès sera activé après confirmation du paiement par l'accueil.</p>
        </form>
    );
}
