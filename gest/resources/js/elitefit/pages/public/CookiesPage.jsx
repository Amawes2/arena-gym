export default function CookiesPage() {
    return (
        <article className="mx-auto max-w-3xl px-6 py-16 sm:py-24">
            <p className="text-xs tracking-[0.18em] text-elite-gold">NAVIGATION</p>
            <h1 className="mt-3 text-4xl text-elite-text">Politique relative aux cookies</h1>
            <p className="mt-5 text-sm leading-7 text-elite-muted">Cette page vous informe sur les cookies et le stockage local pouvant être utilisés pendant votre visite sur le site ARENA GYM.</p>
            <div className="mt-10 space-y-8">
                <section><h2 className="text-xl text-elite-text">Cookies nécessaires</h2><p className="mt-3 text-sm leading-7 text-elite-muted">Le site peut utiliser des cookies techniques nécessaires à la sécurité, à la navigation et au bon fonctionnement de votre session.</p></section>
                <section><h2 className="text-xl text-elite-text">Préférences et session</h2><p className="mt-3 text-sm leading-7 text-elite-muted">Des informations locales peuvent être conservées sur votre appareil pour maintenir votre session ou mémoriser un choix nécessaire au service. Les données de session de connexion ne doivent pas être partagées avec d’autres personnes.</p></section>
                <section><h2 className="text-xl text-elite-text">Gérer les cookies</h2><p className="mt-3 text-sm leading-7 text-elite-muted">Vous pouvez supprimer ou bloquer les cookies depuis les paramètres de votre navigateur. Certaines fonctions, comme la connexion, pourraient alors ne plus fonctionner correctement.</p></section>
                <section><h2 className="text-xl text-elite-text">Nous contacter</h2><p className="mt-3 text-sm leading-7 text-elite-muted">Pour toute question sur les cookies ou vos données, adressez-vous à l’accueil de votre salle.</p></section>
            </div>
            <p className="mt-12 border-t border-elite-border pt-6 text-xs text-elite-muted">Dernière mise à jour : septembre 2026</p>
        </article>
    );
}
