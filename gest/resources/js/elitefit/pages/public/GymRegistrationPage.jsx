import { useEffect, useRef, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { web } from '../../api/client';
import HoneypotField from '../../components/forms/HoneypotField';
import TurnstileWidget from '../../components/forms/TurnstileWidget';
import FieldError from '../../components/forms/FieldError';
import Button from '../../components/ui/Button';
import api from '../../api/client';

const initialForm = {
    gym_name: '',
    country_code: 'CI',
    currency_code: 'XOF',
    prenom: '',
    nom: '',
    telephone: '',
    email: '',
    password: '',
    password_confirmation: '',
    website: '',
    turnstile_token: '',
};

export default function GymRegistrationPage() {
    const navigate = useNavigate();
    const [form, setForm] = useState(initialForm);
    const [error, setError] = useState('');
    const [fieldErrors, setFieldErrors] = useState({});
    const [loading, setLoading] = useState(false);
    const [widgetKey, setWidgetKey] = useState(0);
    const [countries, setCountries] = useState([]);
    const [countryOptionsLoaded, setCountryOptionsLoaded] = useState(false);
    const countrySelectionChanged = useRef(false);

    useEffect(() => {
        let ignore = false;

        api.get('/billing/countries')
            .then(({ data }) => {
                if (!ignore) {
                    setCountries(data.data ?? []);

                    const detectedCountry = data.data?.find((country) => country.code === data.detected_country_code);

                    if (detectedCountry && !countrySelectionChanged.current) {
                        setForm((current) => ({
                            ...current,
                            country_code: detectedCountry.code,
                            currency_code: detectedCountry.currency_code,
                        }));
                    }
                }
            })
            .catch(() => {
                if (!ignore) {
                    setCountries([{ code: 'CI', name: 'Côte d’Ivoire', currency_code: 'XOF' }]);
                }
            })
            .finally(() => {
                if (!ignore) {
                    setCountryOptionsLoaded(true);
                }
            });

        return () => {
            ignore = true;
        };
    }, []);

    const update = (field) => (event) => {
        setForm((current) => ({ ...current, [field]: event.target.value }));
        setFieldErrors((current) => ({ ...current, [field]: undefined }));
    };

    const submit = async (event) => {
        event.preventDefault();
        setError('');
        setFieldErrors({});
        setLoading(true);

        try {
            await web.post('/inscription-salle', form);
            navigate('/admin', { replace: true });
        } catch (requestError) {
            const validationErrors = requestError.response?.data?.errors;
            if (validationErrors) {
                setFieldErrors(Object.fromEntries(Object.entries(validationErrors).map(([field, messages]) => [field, Array.isArray(messages) ? messages[0] : messages])));
                const unassignedError = Object.entries(validationErrors).find(([field]) => !['gym_name', 'country_code', 'currency_code', 'prenom', 'nom', 'telephone', 'email', 'password', 'password_confirmation'].includes(field))?.[1];
                if (unassignedError) {
                    setError(Array.isArray(unassignedError) ? unassignedError[0] : unassignedError);
                }
            } else {
                setError(requestError.response?.data?.message || 'Impossible de créer votre espace pour le moment.');
            }
        } finally {
            setLoading(false);
            setForm((current) => ({ ...current, turnstile_token: '' }));
            setWidgetKey((current) => current + 1);
        }
    };

    const inputClass = 'mt-2 w-full rounded-xl border border-elite-border-light bg-elite-bg px-4 py-3 text-elite-text placeholder:text-elite-disabled focus:border-elite-gold focus:outline-none';

    return (
        <section className="relative isolate overflow-hidden px-6 py-12 sm:py-20">
            <div className="arena-glow pointer-events-none absolute -right-40 top-0 -z-10 h-96 w-96 rounded-full" />
            <div className="arena-form-layout mx-auto max-w-5xl gap-12 lg:items-start">
                <div className="pt-2">
                    <p className="text-xs tracking-[0.18em] text-elite-gold">VOTRE SALLE, VOTRE ESPACE</p>
                    <h1 className="mt-4 text-4xl leading-tight text-elite-text sm:text-5xl">Gérez votre salle dès aujourd’hui.</h1>
                    <p className="mt-5 text-sm leading-7 text-elite-muted">
                        
                    </p>
                    <p className="mt-8 text-sm text-elite-muted">
                        Vous avez déjà un espace ? <Link to="/connexion" className="text-elite-gold hover:underline">Connectez-vous</Link>
                    </p>
                </div>
                <form onSubmit={submit} className="rounded-2xl border border-elite-border-light bg-elite-card p-6 shadow-2xl shadow-black/20 sm:p-9">
                    <HoneypotField value={form.website} onChange={update('website')} />
                    <h2 className="text-xl text-elite-text">Créer l’espace de ma salle</h2>
                    <p className="mt-2 text-sm text-elite-muted">
                        <label className="text-xs text-elite-muted sm:col-span-2">
                            Pays de la salle
                            <select
                                className={inputClass}
                                value={form.country_code}
                                onChange={(event) => {
                                    countrySelectionChanged.current = true;
                                    const country = countries.find((item) => item.code === event.target.value);
                                    setForm((current) => ({ ...current, country_code: event.target.value, currency_code: country?.currency_code ?? 'XOF' }));
                                }}
                                aria-invalid={Boolean(fieldErrors.country_code)}
                                required
                            >
                                {(countries.length ? countries : [{ code: 'CI', name: 'Côte d’Ivoire', currency_code: 'XOF' }]).map((country) => (
                                    <option key={country.code} value={country.code}>{country.name}</option>
                                ))}
                            </select>
                            <span className="mt-1 block text-xs text-elite-muted">Le pays peut être détecté automatiquement et reste modifiable.</span>
                            <FieldError message={fieldErrors.country_code} />
                        </label>
                        <label className="text-xs text-elite-muted sm:col-span-2">
                            Nom de la salle
                            <input className={inputClass} autoFocus autoComplete="organization" value={form.gym_name} onChange={update('gym_name')} maxLength={120} required aria-invalid={Boolean(fieldErrors.gym_name)} aria-describedby={fieldErrors.gym_name ? 'gym-name-error' : undefined} />
                            <FieldError id="gym-name-error" message={fieldErrors.gym_name} />
                        </label>
                        <label className="text-xs text-elite-muted">
                            Prénom
                            <input className={inputClass} autoComplete="given-name" value={form.prenom} onChange={update('prenom')} maxLength={50} required aria-invalid={Boolean(fieldErrors.prenom)} aria-describedby={fieldErrors.prenom ? 'gym-first-name-error' : undefined} />
                            <FieldError id="gym-first-name-error" message={fieldErrors.prenom} />
                        </label>
                        <label className="text-xs text-elite-muted">
                            Nom
                            <input className={inputClass} autoComplete="family-name" value={form.nom} onChange={update('nom')} maxLength={50} required aria-invalid={Boolean(fieldErrors.nom)} aria-describedby={fieldErrors.nom ? 'gym-last-name-error' : undefined} />
                            <FieldError id="gym-last-name-error" message={fieldErrors.nom} />
                        </label>
                        <label className="text-xs text-elite-muted sm:col-span-2">
                            Téléphone
                            <input className={inputClass} type="tel" autoComplete="tel" placeholder="07 XX XX XX XX" value={form.telephone} onChange={update('telephone')} required aria-invalid={Boolean(fieldErrors.telephone)} aria-describedby={fieldErrors.telephone ? 'gym-phone-error' : undefined} />
                            <FieldError id="gym-phone-error" message={fieldErrors.telephone} />
                        </label>
                        <label className="text-xs text-elite-muted sm:col-span-2">
                            Adresse e-mail
                            <input className={inputClass} type="email" autoComplete="email" placeholder="vous@exemple.com" value={form.email} onChange={update('email')} required aria-invalid={Boolean(fieldErrors.email)} aria-describedby={fieldErrors.email ? 'gym-email-error' : undefined} />
                            <FieldError id="gym-email-error" message={fieldErrors.email} />
                        </label>
                        <label className="text-xs text-elite-muted">
                            Mot de passe
                            <input className={inputClass} type="password" autoComplete="new-password" minLength={12} value={form.password} onChange={update('password')} required aria-invalid={Boolean(fieldErrors.password)} aria-describedby={fieldErrors.password ? 'gym-password-hint gym-password-error' : 'gym-password-hint'} />
                            <span id="gym-password-hint" className="mt-1 block">12 caractères minimum, avec une majuscule, une minuscule, un chiffre et un symbole.</span>
                            <FieldError id="gym-password-error" message={fieldErrors.password} />
                        </label>
                        <label className="text-xs text-elite-muted">
                            Confirmer le mot de passe
                            <input className={inputClass} type="password" autoComplete="new-password" minLength={12} value={form.password_confirmation} onChange={update('password_confirmation')} required aria-invalid={Boolean(fieldErrors.password_confirmation)} aria-describedby={fieldErrors.password_confirmation ? 'gym-password-confirmation-error' : undefined} />
                            <FieldError id="gym-password-confirmation-error" message={fieldErrors.password_confirmation} />
                        </label>
                    </div>
                    <TurnstileWidget key={widgetKey} action="gym_registration" onToken={(token) => setForm((current) => ({ ...current, turnstile_token: token }))} />
                    {error && <p role="alert" className="mt-5 rounded-lg bg-elite-danger-bg px-4 py-3 text-sm text-elite-danger">{error}</p>}
                    <Button type="submit" className="mt-7 w-full !py-3.5" disabled={loading || !countryOptionsLoaded}>
                        {!countryOptionsLoaded ? 'Chargement des pays…' : loading ? 'Création de votre espace…' : 'Créer ma salle et continuer'}
                    </Button>
                    <p className="mt-4 text-center text-xs text-elite-muted">
                        En continuant, vous acceptez notre <Link to="/politique-confidentialite" className="text-elite-gold hover:underline">politique de confidentialité</Link>.
                    </p>
                </form>
            </div>
        </section>
    );
}
