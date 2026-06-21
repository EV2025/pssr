import Container from './Container.jsx';
import { footerNavigation, footerPartners } from '../data/navigation.js';

export default function Footer() {
  return (
    <footer className="relative mt-20 overflow-hidden border-t border-brand-border/40 bg-brand-section">
      <div className="pointer-events-none absolute inset-0 bg-gradient-to-br from-brand-soft/30 via-transparent to-brand-primary/10" />
      <Container className="relative py-16">
        <div className="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
          <section>
            <h2 className="font-bold tracking-tight text-brand-text">Navigation</h2>
            <nav className="mt-4 grid gap-2" aria-label="Navigation du pied de page">
              {footerNavigation.map((link) => <a key={link.href} href={link.href} className="min-h-[34px] text-sm font-medium text-brand-body hover:text-brand-primary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2">{link.label}</a>)}
            </nav>
          </section>
          <section>
            <h2 className="font-bold tracking-tight text-brand-text">Partenaires & institutions</h2>
            <nav className="mt-4 grid gap-2" aria-label="Liens partenaires et institutions">
              {footerPartners.map((link) => <a key={link.href} href={link.href} className="min-h-[34px] text-sm font-medium text-brand-body hover:text-brand-primary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2">{link.label}</a>)}
            </nav>
            <a href="/pssr/partenaires-institutions.html" className="mt-5 inline-flex min-h-[42px] items-center rounded-full border border-brand-primary/40 bg-white/70 px-4 text-sm font-bold text-brand-primary hover:bg-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2">Accès partenaires & institutions</a>
          </section>
          <section>
            <h2 className="font-bold tracking-tight text-brand-text">Contact & informations</h2>
            <div className="mt-4 grid gap-2 text-sm text-brand-body">
              <a href="mailto:equilibrevital.bruxelles@gmail.com">equilibrevital.bruxelles@gmail.com</a>
              <a href="tel:+32492691070">0492/691.070</a>
              <p>BCE : 1019487618</p>
              <p className="pt-3 text-xs text-brand-muted">IBAN : BE17 5230 8164 9221<br />BIC : TRIOBEBB</p>
            </div>
          </section>
        </div>
        <div className="mt-12 flex flex-col gap-4 border-t border-brand-border/40 pt-6 text-sm text-brand-muted sm:flex-row sm:items-center sm:justify-between">
          <span>© Équilibre Vital asbl</span>
          <nav className="flex flex-wrap gap-4" aria-label="Liens légaux">
            <a href="/pssr/mentions-legales.html">Mentions légales</a>
            <a href="/pssr/confidentialite-rgpd.html#rgpd">RGPD</a>
            <a href="/pssr/confidentialite-rgpd.html">Politique de confidentialité</a>
          </nav>
        </div>
      </Container>
    </footer>
  );
}
