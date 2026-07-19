import { describe, it, expect } from 'vitest';
import { translateJsError } from './jsErrorTranslator';

describe('translateJsError', () => {
  it('returns null for no error', () => {
    expect(translateJsError(null)).toBeNull();
    expect(translateJsError(undefined)).toBeNull();
    expect(translateJsError('')).toBeNull();
  });

  it('returns null for a non-string input', () => {
    expect(translateJsError(42)).toBeNull();
    expect(translateJsError({ message: 'oops' })).toBeNull();
  });

  it('always preserves the original technical message, trimmed', () => {
    const result = translateJsError('  ReferenceError: x is not defined  ');
    expect(result.technical).toBe('ReferenceError: x is not defined');
  });

  it('translates SyntaxError: Unexpected token, capturing the token', () => {
    const result = translateJsError("SyntaxError: Unexpected token '}'");
    expect(result.friendly).toContain('"}"');
  });

  it('translates SyntaxError: Unexpected end of input', () => {
    const result = translateJsError('SyntaxError: Unexpected end of input');
    expect(result.friendly).toMatch(/accolade|parenthèse/);
  });

  it('translates SyntaxError: Missing initializer in const declaration', () => {
    const result = translateJsError('SyntaxError: Missing initializer in const declaration');
    expect(result.friendly).toContain('const');
  });

  it('translates ReferenceError, capturing the identifier name', () => {
    const result = translateJsError('ReferenceError: maVariable is not defined');
    expect(result.friendly).toContain('"maVariable"');
  });

  it('translates TypeError: Cannot read property of undefined, capturing the property', () => {
    const result = translateJsError(
      "TypeError: Cannot read properties of undefined (reading 'nom')"
    );
    expect(result.friendly).toContain('"nom"');
    expect(result.friendly).toMatch(/undefined/);
  });

  it('translates TypeError: Cannot read property of null, capturing the property', () => {
    const result = translateJsError(
      "TypeError: Cannot read properties of null (reading 'age')"
    );
    expect(result.friendly).toContain('"age"');
    expect(result.friendly).toMatch(/null/);
  });

  it('translates TypeError: X is not a function, capturing the identifier', () => {
    const result = translateJsError('TypeError: calculerRemise is not a function');
    expect(result.friendly).toContain('"calculerRemise"');
  });

  it('translates TypeError: Assignment to constant variable', () => {
    const result = translateJsError('TypeError: Assignment to constant variable.');
    expect(result.friendly).toContain('const');
    expect(result.friendly).toMatch(/let/);
  });

  it('translates RangeError: Maximum call stack size exceeded', () => {
    const result = translateJsError('RangeError: Maximum call stack size exceeded');
    expect(result.friendly).toMatch(/récursion/);
  });

  it('falls back to a generic friendly message for an unrecognized error', () => {
    const result = translateJsError('WeirdCustomError: something exotic happened');
    expect(result.friendly).toBe(
      "JavaScript a rencontré une erreur en exécutant votre code. Le détail technique ci-dessous peut vous aider à localiser le problème."
    );
    expect(result.technical).toBe('WeirdCustomError: something exotic happened');
  });

  it('matches patterns anchored at the start, not anywhere in the string', () => {
    // le message doit commencer par le nom de l'erreur -- un message qui
    // contient juste "is not defined" plus loin ne doit pas être confondu
    // avec le pattern ReferenceError s'il ne commence pas ainsi
    const result = translateJsError('Some wrapper: caused by ReferenceError: x is not defined');
    expect(result.friendly).toBe(
      "JavaScript a rencontré une erreur en exécutant votre code. Le détail technique ci-dessous peut vous aider à localiser le problème."
    );
  });
});
