export default function SectionHeading({ eyebrow, title, children, as: Tag = 'h2' }) {
  return (
    <div className="mx-auto max-w-3xl text-center">
      {eyebrow ? <p className="mx-auto inline-flex justify-center text-sm font-semibold text-brand-primary">{eyebrow}</p> : null}
      <Tag className="mt-3 text-3xl font-bold tracking-tight text-brand-text sm:text-4xl">{title}</Tag>
      {children ? <p className="mx-auto mt-4 max-w-2xl text-base leading-relaxed text-brand-body sm:text-lg">{children}</p> : null}
    </div>
  );
}
