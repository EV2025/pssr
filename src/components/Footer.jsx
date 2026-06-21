import Container from './Container.jsx';
import { actionItems, footerColumns, navItems } from '../data/navigation.js';

export default function Footer() {
  const legalLinks = [
    { label: 'Mentions légales', href: '/mentions-legales' },
    { label: 'RGPD', href: '/confidentialite-rgpd' },
    { label: 'Politique de confidentialité', href: '/confidentialite-rgpd' },
  ];

  return (
    <footer className="mt-20 bg-brand-text text-white">
      <Container className="py-12">
        <div className="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
          <section>
            <h2 className="font-bold">Menu principal</h2>
            <nav className="mt-3 grid gap-2" aria-label="Menu principal">
              {[...navItems, ...actionItems].map((link) => (
                <a key={link.href} href={link.href} className="text-sm text-white/75 hover:text-brand-soft">{link.label}</a>
              ))}
            </nav>
          </section>
          {footerColumns.filter((column) => column.title === 'Ressources').map((column) => (
            <section key={column.title}>
              <h2 className="font-bold">{column.title}</h2>
              <nav className="mt-3 grid gap-2" aria-label={column.title}>
                {column.links.map((link) => (
                  <a key={link.href} href={link.href} className="text-sm text-white/75 hover:text-brand-soft">{link.label}</a>
                ))}
              </nav>
            </section>
          ))}
          <section>
            <h2 className="font-bold">Contact & informations</h2>
            <p><a href="mailto:equilibrevital.bruxelles@gmail.com">equilibrevital.bruxelles@gmail.com</a></p>
            <p><a href="tel:+32492691070">0492/691.070</a></p>
            <p>BCE : 1019487618</p>
          </section>
          <section>
            <h2 className="font-bold">Paiement & légal</h2>
            <p className="text-sm text-white/60">IBAN : BE17 5230 8164 9221<br />BIC : TRIOBEBB</p>
            <nav className="mt-3 grid gap-2" aria-label="Liens légaux">
              {legalLinks.map((link) => (
                <a key={link.href} href={link.href} className="text-sm text-white/75 hover:text-brand-soft">{link.label}</a>
              ))}
            </nav>
          </section>
        </div>
      </Container>
    </footer>
  );
}
