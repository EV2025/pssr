import Container from './Container.jsx';
import { footerColumns } from '../data/navigation.js';

export default function Footer() {
  return (
    <footer className="mt-20 rounded-t-[2rem] bg-brand-text text-white">
      <Container className="py-12">
        <div className="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
          {footerColumns.map((column) => (
            <section key={column.title}>
              <h2 className="text-center font-bold">{column.title}</h2>
              <nav className="mt-3 grid justify-items-center gap-2 text-center" aria-label={column.title}>
                {column.links.map((link) => (
                  <a key={`${column.title}-${link.href}`} href={link.href} className="min-h-[32px] text-sm text-white/85 hover:text-brand-soft focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2">
                    {link.label}
                  </a>
                ))}
              </nav>
            </section>
          ))}
          <section>
            <h2 className="text-center font-bold">Contact & informations</h2>
            <div className="mt-3 grid justify-items-center gap-2 text-center text-sm text-white/85">
              <a href="mailto:equilibrevital.bruxelles@gmail.com">equilibrevital.bruxelles@gmail.com</a>
              <a href="tel:+32492691070">0492/691.070</a>
              <p>BCE : 1019487618</p>
              <p>IBAN : BE17 5230 8164 9221<br />BIC : TRIOBEBB</p>
            </div>
          </section>
        </div>
        <div className="mt-10 flex flex-wrap items-center justify-center gap-4 border-t border-white/20 pt-5 text-sm text-white/75">
          <span>EV© 2026 — Tous droits réservés.</span>
          <a href="/mentions-legales">Mentions légales</a>
          <a href="/confidentialite-rgpd">Confidentialité / RGPD</a>
        </div>
      </Container>
    </footer>
  );
}
