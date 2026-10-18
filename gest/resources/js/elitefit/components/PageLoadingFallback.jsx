export default function PageLoadingFallback() {
    return (
        <div className="p-4 text-sm text-elite-muted" role="status" aria-live="polite">
            Chargement de la page…
        </div>
    );
}
