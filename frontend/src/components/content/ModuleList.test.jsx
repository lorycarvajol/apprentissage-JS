import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import ModuleList from './ModuleList';

const renderWithRouter = (ui) => render(<MemoryRouter>{ui}</MemoryRouter>);

const sampleModules = [
  {
    id: 1,
    order_index: 1,
    title: 'Bases du langage',
    description: 'Variables, types, opérateurs.',
    is_published: true,
  },
  {
    id: 3,
    order_index: 3,
    title: 'Fonctions',
    description: 'Déclaration, expression, arrow, closures.',
    is_published: false,
  },
];

describe('ModuleList', () => {
  it('shows a loading state', () => {
    renderWithRouter(<ModuleList modules={[]} loading />);

    expect(screen.getByText(/chargement des modules/i)).toBeInTheDocument();
  });

  it('shows an empty state when there are no modules', () => {
    renderWithRouter(<ModuleList modules={[]} />);

    expect(screen.getByText(/aucun module disponible/i)).toBeInTheDocument();
  });

  it('shows an empty state when modules is null/undefined', () => {
    renderWithRouter(<ModuleList modules={null} />);

    expect(screen.getByText(/aucun module disponible/i)).toBeInTheDocument();
  });

  it('renders one card per module with title, number and description', () => {
    renderWithRouter(<ModuleList modules={sampleModules} />);

    expect(screen.getByText('Bases du langage')).toBeInTheDocument();
    expect(screen.getByText('Module 1')).toBeInTheDocument();
    expect(screen.getByText('Variables, types, opérateurs.')).toBeInTheDocument();

    expect(screen.getByText('Fonctions')).toBeInTheDocument();
    expect(screen.getByText('Module 3')).toBeInTheDocument();
  });

  it('links each card to its module detail page', () => {
    renderWithRouter(<ModuleList modules={sampleModules} />);

    const link = screen.getByRole('link', { name: /bases du langage/i });
    expect(link).toHaveAttribute('href', '/modules/1');
  });

  it('shows a draft badge only for unpublished modules', () => {
    renderWithRouter(<ModuleList modules={sampleModules} />);

    const draftBadges = screen.getAllByText('Brouillon');
    expect(draftBadges).toHaveLength(1);

    // le brouillon appartient à la carte "Fonctions" (is_published: false),
    // pas à "Bases du langage" (is_published: true)
    const fonctionsLink = screen.getByRole('link', { name: /fonctions/i });
    expect(fonctionsLink).toContainElement(draftBadges[0]);
  });
});
