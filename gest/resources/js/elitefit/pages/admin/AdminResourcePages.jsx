import { useEffect, useMemo, useRef, useState } from 'react';
import {
    IconArrowUpRight,
    IconBox,
    IconClipboardCheck,
    IconPackage,
    IconPlus,
    IconPencil,
    IconSearch,
    IconCheck,
    IconX,
    IconShoppingCart,
    IconUsers,
} from '@tabler/icons-react';
import api, { formatFcfa } from '../../api/client';

const emptyMessage = 'Aucune donnée enregistrée pour le moment.';

function PageShell({ title, subtitle, icon: Icon, children, action }) {
    return (
        <div className="container-fluid min-w-0 px-3 sm:px-4 xl:px-5 py-4 xl:py-5">
            <header className="d-flex flex-column gap-3 border-bottom pb-4 md:flex-row md:align-items-start md:justify-content-between">
                <div className="d-flex align-items-start gap-3">
                    <span className="arena-admin-page-icon d-inline-flex flex-shrink-0 align-items-center justify-content-center rounded-3">
                        <Icon size={21} stroke={1.7} />
                    </span>
                    <div>
                        <p className="mb-2 small fw-bold text-uppercase text-primary arena-admin-eyebrow">Espace de gestion</p>
                        <h1 className="arena-admin-page-title mb-1 fw-bold text-dark">{title}</h1>
                        {subtitle && <p className="mb-0 small leading-6 arena-admin-text-muted">{subtitle}</p>}
                    </div>
                </div>
                {action}
            </header>
            <div className="mt-4">{children}</div>
        </div>
    );
}

function DataPanel({ children }) {
    return <section className="card min-w-0 overflow-hidden">{children}</section>;
}

function StatusBadge({ children, tone = 'warning' }) {
    const tones = {
        success: 'arena-admin-status',
        warning: 'arena-admin-status-warning',
        danger: 'arena-admin-status-danger',
        muted: 'arena-admin-status-muted',
    };

    return <span className={`badge rounded-pill ${tones[tone]}`}>{children}</span>;
}

function ErrorState({ message }) {
    return <div role="alert" className="alert alert-danger mb-0">{message}</div>;
}

function EmptyState() {
    return <div className="card-body py-5 text-center small arena-admin-text-muted">{emptyMessage}</div>;
}

function BackofficeLink({ children = 'Ouvrir le backoffice' }) {
    return (
        <a href="/backoffice/login" className="btn btn-solid theme-primary d-inline-flex align-items-center gap-2 text-nowrap">
            {children}
            <IconArrowUpRight size={15} stroke={1.8} />
        </a>
    );
}

function useApiList(endpoint, params = {}) {
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [reloadKey, setReloadKey] = useState(0);
    const stableParams = JSON.stringify(params);

    useEffect(() => {
        let ignore = false;
        setLoading(true);
        setError('');

        api.get(endpoint, { params })
            .then((response) => {
                if (!ignore) {
                    setItems(Array.isArray(response.data?.data) ? response.data.data : []);
                }
            })
            .catch((requestError) => {
                if (!ignore) {
                    setError(requestError.response?.data?.message || 'Impossible de charger les données.');
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
    }, [endpoint, stableParams, reloadKey]);

    return { items, loading, error, reload: () => setReloadKey((current) => current + 1) };
}

function TableLoading({ loading, error, children }) {
    if (error) {
        return <ErrorState message={error} />;
    }

    if (loading) {
        return (
            <div className="card card-body d-grid gap-3 py-5" role="status" aria-label="Chargement des données">
                <div className="arena-skeleton arena-skeleton-line" />
                <div className="arena-skeleton arena-skeleton-line w-75" />
                <div className="arena-skeleton arena-skeleton-line w-50" />
            </div>
        );
    }

    return children;
}

function useAccessibleDialog(onClose) {
    const dialogRef = useRef(null);
    const onCloseRef = useRef(onClose);
    const handlersRef = useRef({});
    const closeTimerRef = useRef(null);
    const [isClosing, setIsClosing] = useState(false);

    onCloseRef.current = onClose;

    const closeWith = (callback = onCloseRef.current) => {
        if (closeTimerRef.current !== null) {
            return;
        }

        setIsClosing(true);
        closeTimerRef.current = window.setTimeout(() => {
            closeTimerRef.current = null;
            callback();
        }, 180);
    };
    const close = () => closeWith();
    handlersRef.current = { close };

    useEffect(() => {
        const dialog = dialogRef.current;
        const previousFocus = document.activeElement;
        dialog?.querySelector(
            '[autofocus], input:not([type="hidden"]):not([tabindex="-1"]), select:not(:disabled), textarea:not(:disabled)',
        )?.focus();

        const handleKeyDown = (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                handlersRef.current.close();
                return;
            }

            if (event.key !== 'Tab' || !dialog) {
                return;
            }

            const focusableElements = [...dialog.querySelectorAll(
                'a[href], button:not(:disabled), input:not(:disabled):not([type="hidden"]), select:not(:disabled), textarea:not(:disabled), [tabindex]:not([tabindex="-1"])',
            )].filter((element) => element.offsetParent !== null && element.tabIndex >= 0);

            if (focusableElements.length === 0) {
                event.preventDefault();
                return;
            }

            const firstElement = focusableElements[0];
            const lastElement = focusableElements.at(-1);

            if (event.shiftKey && document.activeElement === firstElement) {
                event.preventDefault();
                lastElement.focus();
            } else if (!event.shiftKey && document.activeElement === lastElement) {
                event.preventDefault();
                firstElement.focus();
            }
        };

        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('keydown', handleKeyDown);
            window.clearTimeout(closeTimerRef.current);
            if (previousFocus instanceof HTMLElement) {
                previousFocus.focus();
            }
        };
    }, []);

    return { dialogRef, isClosing, close, closeWith };
}

function StatusNotice({ message, onDismiss }) {
    const [isClosing, setIsClosing] = useState(false);
    const onDismissRef = useRef(onDismiss);
    onDismissRef.current = onDismiss;

    useEffect(() => {
        const closeTimer = window.setTimeout(() => setIsClosing(true), 4800);
        const dismissTimer = window.setTimeout(() => onDismissRef.current(), 5000);

        return () => {
            window.clearTimeout(closeTimer);
            window.clearTimeout(dismissTimer);
        };
    }, []);

    return (
        <div
            role="status"
            aria-live="polite"
            className={`alert alert-success arena-notice arena-notice-enter d-flex align-items-center justify-content-between gap-3 ${isClosing ? 'is-closing' : ''}`}
            onAnimationEnd={() => {
                if (isClosing) {
                    onDismissRef.current();
                }
            }}
        >
            <span className="d-inline-flex align-items-center gap-2"><IconCheck size={17} className="arena-check-draw" />{message}</span>
            <button type="button" className="btn-close" aria-label="Fermer le message" onClick={() => setIsClosing(true)} />
            <span aria-hidden="true" className="arena-notice-timer" />
        </div>
    );
}

function MemberFormDialog({ member, onClose, onSaved }) {
    const [form, setForm] = useState(() => ({
        prenom: member?.prenom ?? '',
        nom: member?.nom ?? '',
        telephone: member?.telephone ?? '',
        email: member?.email ?? '',
    }));
    const [error, setError] = useState('');
    const [fieldErrors, setFieldErrors] = useState({});
    const [isSaving, setIsSaving] = useState(false);
    const { dialogRef, isClosing, close, closeWith } = useAccessibleDialog(onClose);

    const updateField = (event) => {
        setForm((current) => ({ ...current, [event.target.name]: event.target.value }));
        setFieldErrors((current) => ({ ...current, [event.target.name]: undefined }));
    };

    const submit = async (event) => {
        event.preventDefault();
        setError('');
        setFieldErrors({});
        setIsSaving(true);

        try {
            const payload = { ...form, email: form.email.trim() || null };
            const response = member
                ? await api.put(`/membres/${member.id}`, payload)
                : await api.post('/membres', { ...payload, type: 'walk_in' });

            closeWith(() => onSaved(response.data?.message || (member ? 'Membre modifié.' : 'Membre ajouté.')));
        } catch (requestError) {
            const validationErrors = requestError.response?.data?.errors;
            if (validationErrors) {
                setFieldErrors(Object.fromEntries(
                    Object.entries(validationErrors).map(([field, messages]) => [field, Array.isArray(messages) ? messages[0] : messages]),
                ));
            } else {
                setError(requestError.response?.data?.message || 'Impossible d’enregistrer ce membre.');
            }
        } finally {
            setIsSaving(false);
        }
    };

    return (
        <div className={`arena-admin-modal-backdrop ${isClosing ? 'is-closing' : ''}`} onMouseDown={(event) => {
            if (event.target === event.currentTarget) {
                close();
            }
        }}>
            <section ref={dialogRef} className={`arena-admin-modal card ${isClosing ? 'is-closing' : ''}`} role="dialog" aria-modal="true" aria-labelledby="member-form-title">
                <form onSubmit={submit}>
                    <div className="card-header d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <h2 id="member-form-title" className="mb-0 fs-6 fw-bold text-dark">
                                {member ? 'Modifier le membre' : 'Ajouter un membre'}
                            </h2>
                            <p className="mt-1 mb-0 small arena-admin-text-muted">
                                {member ? 'Mettez à jour ses coordonnées.' : 'Le nouveau membre sera enregistré sans abonnement.'}
                            </p>
                        </div>
                        <button type="button" className="btn btn-outline-secondary btn-sm" onClick={close} aria-label="Fermer" disabled={isClosing}>
                            <IconX size={17} />
                        </button>
                    </div>
                    <div className="card-body d-grid gap-3">
                        {error && <div role="alert" className="alert alert-danger mb-0">{error}</div>}
                        <div className="row g-3">
                            <div className="col-12 col-sm-6">
                                <label className="form-label small fw-semibold" htmlFor="member-first-name">Prénom</label>
                                <input id="member-first-name" autoFocus className="form-control" name="prenom" value={form.prenom} onChange={updateField} maxLength={50} required aria-invalid={Boolean(fieldErrors.prenom)} aria-describedby={fieldErrors.prenom ? 'member-first-name-error' : undefined} />
                                {fieldErrors.prenom && <div id="member-first-name-error" role="alert" className="invalid-feedback d-block">{fieldErrors.prenom}</div>}
                            </div>
                            <div className="col-12 col-sm-6">
                                <label className="form-label small fw-semibold" htmlFor="member-last-name">Nom</label>
                                <input id="member-last-name" className="form-control" name="nom" value={form.nom} onChange={updateField} maxLength={50} required aria-invalid={Boolean(fieldErrors.nom)} aria-describedby={fieldErrors.nom ? 'member-last-name-error' : undefined} />
                                {fieldErrors.nom && <div id="member-last-name-error" role="alert" className="invalid-feedback d-block">{fieldErrors.nom}</div>}
                            </div>
                        </div>
                        <div>
                            <label className="form-label small fw-semibold" htmlFor="member-phone">Téléphone</label>
                            <input id="member-phone" className="form-control" name="telephone" value={form.telephone} onChange={updateField} inputMode="tel" autoComplete="tel" maxLength={20} required aria-invalid={Boolean(fieldErrors.telephone)} aria-describedby={fieldErrors.telephone ? 'member-phone-error' : undefined} />
                            <div className="form-text">Saisissez entre 8 et 15 chiffres, éventuellement précédés de +.</div>
                            {fieldErrors.telephone && <div id="member-phone-error" role="alert" className="invalid-feedback d-block">{fieldErrors.telephone}</div>}
                        </div>
                        <div>
                            <label className="form-label small fw-semibold" htmlFor="member-email">E-mail (facultatif)</label>
                            <input id="member-email" className="form-control" name="email" type="email" autoComplete="email" value={form.email} onChange={updateField} aria-invalid={Boolean(fieldErrors.email)} aria-describedby={fieldErrors.email ? 'member-email-error' : undefined} />
                            {fieldErrors.email && <div id="member-email-error" role="alert" className="invalid-feedback d-block">{fieldErrors.email}</div>}
                        </div>
                    </div>
                    <div className="card-footer d-flex justify-content-end gap-2">
                        <button type="button" className="btn btn-outline-secondary" onClick={close} disabled={isSaving || isClosing}>Annuler</button>
                        <button type="submit" className="btn btn-solid theme-primary d-inline-flex align-items-center gap-2" disabled={isSaving || isClosing}>
                            <IconCheck size={16} />
                            {isSaving ? 'Enregistrement…' : 'Enregistrer'}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    );
}

function CheckInForm({ onClose, onSaved }) {
    const [query, setQuery] = useState('');
    const [selectedMemberId, setSelectedMemberId] = useState('');
    const [notes, setNotes] = useState('');
    const [error, setError] = useState('');
    const [fieldErrors, setFieldErrors] = useState({});
    const [isSaving, setIsSaving] = useState(false);
    const { dialogRef, isClosing, close, closeWith } = useAccessibleDialog(onClose);
    const { items: members, loading: isLoadingMembers, error: memberError } = useApiList('/membres', query ? { q: query } : {});

    const submit = async (event) => {
        event.preventDefault();
        setError('');
        setFieldErrors({});
        setIsSaving(true);

        try {
            const response = await api.post('/presences', {
                member_id: Number(selectedMemberId),
                notes: notes.trim() || null,
            });

            closeWith(() => onSaved(response.data?.message || 'Présence enregistrée.'));
        } catch (requestError) {
            const validationErrors = requestError.response?.data?.errors;
            if (validationErrors) {
                setFieldErrors(Object.fromEntries(
                    Object.entries(validationErrors).map(([field, messages]) => [field, Array.isArray(messages) ? messages[0] : messages]),
                ));
            } else {
                setError(requestError.response?.data?.message || 'Impossible d’enregistrer cette présence.');
            }
        } finally {
            setIsSaving(false);
        }
    };

    return (
        <div className={`arena-admin-modal-backdrop ${isClosing ? 'is-closing' : ''}`} onMouseDown={(event) => {
            if (event.target === event.currentTarget) {
                close();
            }
        }}>
            <section ref={dialogRef} className={`arena-admin-modal card ${isClosing ? 'is-closing' : ''}`} role="dialog" aria-modal="true" aria-labelledby="check-in-title">
                <form onSubmit={submit}>
                    <div className="card-header d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <h2 id="check-in-title" className="mb-0 fs-6 fw-bold text-dark">Pointer une entrée</h2>
                            <p className="mt-1 mb-0 small arena-admin-text-muted">Sélectionnez le membre qui arrive à la salle.</p>
                        </div>
                        <button type="button" className="btn btn-outline-secondary btn-sm" onClick={close} aria-label="Fermer" disabled={isClosing}>
                            <IconX size={17} />
                        </button>
                    </div>
                    <div className="card-body d-grid gap-3">
                        {error && <div role="alert" className="alert alert-danger mb-0">{error}</div>}
                        {memberError && <div role="alert" className="alert alert-danger mb-0">{memberError}</div>}
                        <div>
                            <label className="form-label small fw-semibold" htmlFor="check-in-search">Rechercher un membre</label>
                            <input id="check-in-search" autoFocus className="form-control mb-2" value={query} onChange={(event) => {
                                setQuery(event.target.value);
                                setSelectedMemberId('');
                            }} placeholder="Nom ou téléphone" />
                            <select
                                className="form-select"
                                aria-label="Membre à pointer"
                                value={selectedMemberId}
                                onChange={(event) => {
                                    setSelectedMemberId(event.target.value);
                                    setFieldErrors((current) => ({ ...current, member_id: undefined }));
                                }}
                                required
                                disabled={isLoadingMembers || members.length === 0}
                                aria-invalid={Boolean(fieldErrors.member_id)}
                                aria-describedby={fieldErrors.member_id ? 'check-in-member-error' : undefined}
                            >
                                <option value="">{isLoadingMembers ? 'Chargement des membres…' : 'Choisir un membre'}</option>
                                {members.map((member) => (
                                    <option key={member.id} value={member.id}>
                                        {member.prenom} {member.nom} — {member.telephone}
                                    </option>
                                ))}
                            </select>
                            {fieldErrors.member_id && <div id="check-in-member-error" role="alert" className="invalid-feedback d-block">{fieldErrors.member_id}</div>}
                            {!isLoadingMembers && members.length === 0 && !memberError && (
                                <div className="form-text">Aucun membre trouvé. Ajoutez d’abord sa fiche dans la page Membres.</div>
                            )}
                        </div>
                        <div>
                            <label className="form-label small fw-semibold" htmlFor="check-in-notes">Note (facultative)</label>
                            <input id="check-in-notes" className="form-control" value={notes} onChange={(event) => {
                                setNotes(event.target.value);
                                setFieldErrors((current) => ({ ...current, notes: undefined }));
                            }} maxLength={190} aria-invalid={Boolean(fieldErrors.notes)} aria-describedby={fieldErrors.notes ? 'check-in-notes-error' : undefined} />
                            {fieldErrors.notes && <div id="check-in-notes-error" role="alert" className="invalid-feedback d-block">{fieldErrors.notes}</div>}
                        </div>
                        <p className="mb-0 small arena-admin-text-muted">L’abonnement actif sera utilisé automatiquement ; sans abonnement valide, l’entrée sera enregistrée en séance unique.</p>
                    </div>
                    <div className="card-footer d-flex justify-content-end gap-2">
                        <button type="button" className="btn btn-outline-secondary" onClick={close} disabled={isSaving || isClosing}>Annuler</button>
                        <button type="submit" className="btn btn-solid theme-primary d-inline-flex align-items-center gap-2" disabled={isSaving || isClosing || !selectedMemberId}>
                            <IconCheck size={16} />
                            {isSaving ? 'Pointage…' : 'Enregistrer la présence'}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    );
}

export function AdminMembresPage() {
    const [query, setQuery] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [memberToEdit, setMemberToEdit] = useState(null);
    const [isCreatingMember, setIsCreatingMember] = useState(false);
    const [notice, setNotice] = useState('');
    const { items, loading, error, reload } = useApiList('/membres', query ? { q: query } : {});
    const pageSize = 10;
    const pageCount = Math.max(1, Math.ceil(items.length / pageSize));
    const visibleMembers = items.slice((currentPage - 1) * pageSize, currentPage * pageSize);

    useEffect(() => {
        setCurrentPage(1);
    }, [query]);

    return (
        <PageShell
            title="Membres"
            subtitle="Fiches membres enregistrées dans votre salle."
            icon={IconUsers}
            action={
                <button type="button" className="btn btn-solid theme-primary d-inline-flex align-items-center gap-2 text-nowrap" onClick={() => setIsCreatingMember(true)}>
                    <IconPlus size={17} />
                    Ajouter un membre
                </button>
            }
        >
            {notice && <StatusNotice message={notice} onDismiss={() => setNotice('')} />}
            <label className="arena-admin-search input-group mb-4">
                <span className="input-group-text bg-white text-secondary">
                    <IconSearch size={18} stroke={1.7} />
                </span>
                <span className="visually-hidden">Rechercher un membre</span>
                <input
                    value={query}
                    onChange={(event) => setQuery(event.target.value)}
                    placeholder="Rechercher par nom ou téléphone"
                    className="form-control"
                    aria-label="Rechercher un membre par nom ou téléphone"
                />
            </label>
            <TableLoading loading={loading} error={error}>
                <DataPanel>
                    {items.length === 0 ? <EmptyState /> : (
                        <div className="table-responsive">
                            <table className="table arena-admin-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Membre</th>
                                        <th scope="col">Téléphone</th>
                                        <th scope="col">E-mail</th>
                                        <th scope="col">Type</th>
                                        <th scope="col" className="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {visibleMembers.map((member) => (
                                        <tr key={member.id}>
                                            <td data-label="Membre" className="fw-semibold text-dark">{member.prenom} {member.nom}</td>
                                            <td data-label="Téléphone" className="arena-admin-text-muted">{member.telephone}</td>
                                            <td data-label="E-mail" className="arena-admin-text-muted">{member.email || '—'}</td>
                                            <td data-label="Type"><StatusBadge tone={member.type === 'subscriber' ? 'success' : 'muted'}>{member.type === 'subscriber' ? 'Abonné' : 'Séance unique'}</StatusBadge></td>
                                            <td data-label="Actions" className="text-end">
                                                <button type="button" className="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2" onClick={() => setMemberToEdit(member)}>
                                                    <IconPencil size={15} />
                                                    Modifier
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                    {items.length > pageSize && (
                        <nav className="d-flex align-items-center justify-content-between gap-3 border-top px-3 py-2" aria-label="Pagination des membres">
                            <span className="small arena-admin-text-muted">Page {currentPage} sur {pageCount}</span>
                            <div className="d-flex gap-2">
                                <button type="button" className="btn btn-outline-secondary btn-sm" onClick={() => setCurrentPage((page) => Math.max(1, page - 1))} disabled={currentPage === 1}>Précédent</button>
                                <button type="button" className="btn btn-outline-secondary btn-sm" onClick={() => setCurrentPage((page) => Math.min(pageCount, page + 1))} disabled={currentPage === pageCount}>Suivant</button>
                            </div>
                        </nav>
                    )}
                </DataPanel>
            </TableLoading>
            {(isCreatingMember || memberToEdit) && (
                <MemberFormDialog
                    key={memberToEdit?.id ?? 'new-member'}
                    member={memberToEdit}
                    onClose={() => {
                        setIsCreatingMember(false);
                        setMemberToEdit(null);
                    }}
                    onSaved={(message) => {
                        setIsCreatingMember(false);
                        setMemberToEdit(null);
                        setNotice(message);
                        reload();
                    }}
                />
            )}
        </PageShell>
    );
}

export function AdminPresencesPage() {
    const [isCheckInOpen, setIsCheckInOpen] = useState(false);
    const [notice, setNotice] = useState('');
    const { items, loading, error, reload } = useApiList('/seances');

    return (
        <PageShell
            title="Présences"
            subtitle="Historique des entrées enregistrées dans votre salle."
            icon={IconClipboardCheck}
            action={
                <button type="button" className="btn btn-solid theme-primary d-inline-flex align-items-center gap-2 text-nowrap" onClick={() => setIsCheckInOpen(true)}>
                    <IconPlus size={17} />
                    Pointer une entrée
                </button>
            }
        >
            {notice && <StatusNotice message={notice} onDismiss={() => setNotice('')} />}
            <TableLoading loading={loading} error={error}>
                <DataPanel>
                    {items.length === 0 ? <EmptyState /> : (
                        <div className="table-responsive">
                            <table className="table arena-admin-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Date et heure</th>
                                        <th scope="col">Membre</th>
                                        <th scope="col">Téléphone</th>
                                        <th scope="col">Type d’entrée</th>
                                        <th scope="col">Formule</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((entry) => (
                                        <tr key={entry.id}>
                                            <td data-label="Date et heure" className="arena-admin-text-muted">{entry.date_heure ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(entry.date_heure)) : '—'}</td>
                                            <td data-label="Membre" className="fw-semibold text-dark">{entry.membre?.prenom} {entry.membre?.nom}</td>
                                            <td data-label="Téléphone" className="arena-admin-text-muted">{entry.membre?.telephone || '—'}</td>
                                            <td data-label="Type d’entrée"><StatusBadge tone={entry.type === 'subscription' ? 'success' : 'muted'}>{entry.type === 'subscription' ? 'Abonnement' : 'Séance unique'}</StatusBadge></td>
                                            <td data-label="Formule" className="arena-admin-text-muted">{entry.forfait || '—'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </DataPanel>
            </TableLoading>
            {isCheckInOpen && (
                <CheckInForm
                    onClose={() => setIsCheckInOpen(false)}
                    onSaved={(message) => {
                        setIsCheckInOpen(false);
                        setNotice(message);
                        reload();
                    }}
                />
            )}
        </PageShell>
    );
}

export function AdminCommandesPage() {
    const { items, loading, error } = useApiList('/commandes');

    return (
        <PageShell title="Commandes" subtitle="Commandes enregistrées dans la boutique." icon={IconShoppingCart}>
            <TableLoading loading={loading} error={error}>
                <DataPanel>
                    {items.length === 0 ? <EmptyState /> : (
                        <div className="table-responsive">
                            <table className="table arena-admin-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Commande</th>
                                        <th scope="col">Client</th>
                                        <th scope="col">Statut</th>
                                        <th scope="col" className="text-end">Montant</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((order) => (
                                        <tr key={order.id}>
                                            <td data-label="Commande" className="fw-semibold text-dark">{order.numero}</td>
                                            <td data-label="Client" className="arena-admin-text-muted">{order.nom_client}</td>
                                            <td data-label="Statut"><StatusBadge>{order.statut_label}</StatusBadge></td>
                                            <td data-label="Montant" className="text-end fw-semibold text-dark">{formatFcfa(order.montant_total)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </DataPanel>
            </TableLoading>
        </PageShell>
    );
}

export function AdminProduitsPage() {
    const { items, loading, error } = useApiList('/produits');

    return (
        <PageShell title="Produits" subtitle="Catalogue des produits actifs de votre salle." icon={IconBox}>
            <ProductTable items={items} loading={loading} error={error} />
        </PageShell>
    );
}

export function AdminStockPage() {
    const { items, loading, error } = useApiList('/produits');
    const sortedItems = useMemo(() => [...items].sort((first, second) => first.stock - second.stock), [items]);

    return (
        <PageShell title="Stock" subtitle="Quantités disponibles par produit." icon={IconPackage}>
            <ProductTable items={sortedItems} loading={loading} error={error} showStockAlert />
        </PageShell>
    );
}

export function AdminModulePage({ title, subtitle, icon: Icon }) {
    return (
        <PageShell title={title} subtitle={subtitle} icon={Icon}>
            <section className="card card-body d-flex flex-column gap-4 sm:flex-row sm:align-items-center sm:justify-content-between">
                <div>
                    <h2 className="mb-2 fs-6 fw-bold text-dark">Cette fonction se trouve dans le backoffice Filament.</h2>
                    <p className="mb-0 small leading-6 arena-admin-text-muted">Connectez-vous au panel de gestion pour accéder à cette page et à ses actions.</p>
                </div>
                <BackofficeLink />
            </section>
        </PageShell>
    );
}

function ProductTable({ items, loading, error, showStockAlert = false }) {
    return (
        <TableLoading loading={loading} error={error}>
            <DataPanel>
                {items.length === 0 ? <EmptyState /> : (
                    <div className="table-responsive">
                        <table className="table arena-admin-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Produit</th>
                                    <th scope="col">Catégorie</th>
                                    <th scope="col" className="text-end">Prix</th>
                                    <th scope="col" className="text-end">Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.map((product) => (
                                    <tr key={product.id}>
                                        <td data-label="Produit" className="fw-semibold text-dark">{product.nom}</td>
                                        <td data-label="Catégorie" className="arena-admin-text-muted">{product.categorie}</td>
                                        <td data-label="Prix" className="text-end text-dark">{formatFcfa(product.prix)}</td>
                                        <td data-label="Stock" className="text-end">
                                            {showStockAlert && product.stock <= 5 ? <StatusBadge tone="danger">{product.stock}</StatusBadge> : <span className="text-dark">{product.stock}</span>}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </DataPanel>
        </TableLoading>
    );
}
