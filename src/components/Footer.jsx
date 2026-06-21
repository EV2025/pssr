import Container from './Container.jsx';
import { footerColumns } from '../data/navigation.js';

export default function Footer() {
  return (
    <footer className="mt-20 rounded-t-[2rem] bg-brand-text text-white">
      <Container className="py-12">
        <div className="grid grid-cols-1 gap-8 text-center sm:grid-cols-2 lg:grid-cols-4">
          {footerColumns.map((column) => (
            <section key={column.title}>
              <h2 className="font-bold tracking-tight">{column.title}</h2>
              <nav className="mt-3 grid justify-items-center gap-2" aria-label={column.title}>
                {column.links.map((link) => (
                  <a key={link.href} href={link.href} className="min-h-[28px] text-sm text-white/75 transition-colors hover:text-brand-soft focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4">
                    {link.label}
                  </a>
                ))}
              </nav>
            </section>
          ))}
          <section>
            <h2 className="font-bold tracking-tight">Contact & informations</h2>
            <div className="mt-3 space-y-2 text-sm text-white/75">
              <p><a href="mailto:equilibrevital.bruxelles@gmail.com">equilibrevital.bruxelles@gmail.com</a></p>
              <p><a href="tel:+32492691070">0492/691.070</a></p>
              <p>BCE : 1019487618</p>
              <p>IBAN : BE17 5230 8164 9221<br />BIC : TRIOBEBB</p>
            </div>
          </section>
        </div>
      </Container>
    </footer>
  );
}
