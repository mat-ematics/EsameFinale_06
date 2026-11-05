/**
 * Italian Tax Code (Codice Fiscale) Module
 *
 * Pure functions: generation, decoding, check character and consistency
 * checks. No DOM and no Angular, so they are trivially testable.
 * Keep in sync with app/Support/ItalianTaxCode.php.
 */

const MONTH_LETTERS = 'ABCDEHLMPRST';
const OMOCODIA = 'LMNPQRSTUV';                       // index = the digit it replaces
const OMOCODIA_POSITIONS = [6, 7, 9, 10, 12, 13, 14];
/** Values of A..Z (and 0..9 as A..J) in odd positions */
const ODD_VALUES = [1, 0, 5, 7, 9, 13, 15, 17, 19, 21, 2, 4, 18, 20, 11, 3, 6, 8, 12, 14, 16, 10, 22, 25, 24, 23];
const STRUCTURE = /^[A-Z]{6}[0-9LMNPQRSTUV]{2}[ABCDEHLMPRST][0-9LMNPQRSTUV]{2}[A-Z][0-9LMNPQRSTUV]{3}[A-Z]$/;

/** The year has 2 digits: we assume registrants are at least this old to pick the century */
export const MIN_REGISTRATION_AGE = 18;

export interface TaxCodeInput {
    name: string,
    surname: string,
    birthdate: string,        // YYYY-MM-DD (from <input type="date">)
    gender: string,           // only 'male' and 'female' can be encoded
    birthPlaceCode: string,   // catastal code, e.g. H501
}

export interface DecodedTaxCode {
    surnamePart: string,
    namePart: string,
    yy: number,
    month: number,
    day: number,
    birthdate: string,        // YYYY-MM-DD, century resolved as described above
    gender: 'male' | 'female',
    birthPlaceCode: string,
    abroad: boolean,          // Z + 3 digits: a foreign state
}

export type TaxCodeMismatch = 'name' | 'surname' | 'birthdate' | 'gender' | 'birthplace';

/* --- helpers --- */

/** Strips accents and everything that is not A-Z */
const letters = (text: string): string =>
    text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase().replace(/[^A-Z]/g, '');

const split = (text: string) => ({
    consonants: text.replace(/[AEIOU]/g, ''),
    vowels: text.replace(/[^AEIOU]/g, ''),
});

const pad = (value: number) => String(value).padStart(2, '0');

function parseIsoDate(value: string): { year: number, month: number, day: number } | null {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value.trim());
    if (!match) return null;

    const [year, month, day] = [Number(match[1]), Number(match[2]), Number(match[3])];
    const date = new Date(Date.UTC(year, month - 1, day));
    const real = date.getUTCFullYear() === year && date.getUTCMonth() === month - 1 && date.getUTCDate() === day;

    return real ? { year, month, day } : null;
}

/* --- encoding --- */

export function surnameCode(surname: string): string | null {
    const text = letters(surname);
    if (!text) return null;

    const { consonants, vowels } = split(text);
    return (consonants + vowels + 'XXX').slice(0, 3);
}

export function nameCode(name: string): string | null {
    const text = letters(name);
    if (!text) return null;

    const { consonants, vowels } = split(text);
    if (consonants.length >= 4) return consonants[0] + consonants[2] + consonants[3];
    return (consonants + vowels + 'XXX').slice(0, 3);
}

/** Check character of the first 15 characters (position 1 is "odd") */
export function checkChar(first15: string): string {
    let sum = 0;
    for (let i = 0; i < 15; i++) {
        const ch = first15[i];
        const index = ch >= '0' && ch <= '9' ? Number(ch) : ch.charCodeAt(0) - 65;
        sum += i % 2 === 0 ? ODD_VALUES[index] : index;
    }
    return String.fromCharCode(65 + (sum % 26));
}

/** Returns the full code, or null while some input is missing or cannot be encoded */
export function generateTaxCode(input: TaxCodeInput): string | null {
    const date = parseIsoDate(input.birthdate);
    const surname = surnameCode(input.surname);
    const name = nameCode(input.name);
    const place = input.birthPlaceCode.trim().toUpperCase();
    const female = input.gender === 'female';

    if (!date || !surname || !name || !/^[A-Z]\d{3}$/.test(place)) return null;
    if (input.gender !== 'male' && !female) return null;

    const body = surname + name
        + pad(date.year % 100)
        + MONTH_LETTERS[date.month - 1]
        + pad(date.day + (female ? 40 : 0))
        + place;

    return body + checkChar(body);
}

/* --- decoding --- */

/** Returns the decoded data, or null if the structure, the date or the check character are wrong */
export function decodeTaxCode(raw: string, today = new Date()): DecodedTaxCode | null {
    const code = raw.trim().toUpperCase();
    if (!STRUCTURE.test(code) || checkChar(code.slice(0, 15)) !== code[15]) return null;

    // Omocodia: letters standing in for digits go back to digits
    const digits = [...code];
    for (const position of OMOCODIA_POSITIONS) {
        const value = OMOCODIA.indexOf(digits[position]);
        if (value !== -1) digits[position] = String(value);
    }

    const yy = Number(digits[6] + digits[7]);
    const month = MONTH_LETTERS.indexOf(code[8]) + 1;
    const rawDay = Number(digits[9] + digits[10]);
    const female = rawDay > 40;
    const day = female ? rawDay - 40 : rawDay;

    const latestYear = today.getFullYear() - MIN_REGISTRATION_AGE;
    const year = 2000 + yy <= latestYear ? 2000 + yy : 1900 + yy;

    const birthdate = `${year}-${pad(month)}-${pad(day)}`;
    if (!parseIsoDate(birthdate)) return null;

    const birthPlaceCode = code[11] + digits[12] + digits[13] + digits[14];

    return {
        surnamePart: code.slice(0, 3),
        namePart: code.slice(3, 6),
        yy, month, day, birthdate,
        gender: female ? 'female' : 'male',
        birthPlaceCode,
        abroad: birthPlaceCode.startsWith('Z'),
    };
}

/** Compares a decoded code with the data typed in the form. Only the provided data is compared */
export function taxCodeMismatches(decoded: DecodedTaxCode, input: Partial<TaxCodeInput>): TaxCodeMismatch[] {
    const out: TaxCodeMismatch[] = [];

    const name = input.name ? nameCode(input.name) : null;
    if (name && name !== decoded.namePart) out.push('name');

    const surname = input.surname ? surnameCode(input.surname) : null;
    if (surname && surname !== decoded.surnamePart) out.push('surname');

    const date = input.birthdate ? parseIsoDate(input.birthdate) : null;
    if (date && (date.year % 100 !== decoded.yy || date.month !== decoded.month || date.day !== decoded.day)) {
        out.push('birthdate');
    }

    if ((input.gender === 'male' || input.gender === 'female') && input.gender !== decoded.gender) {
        out.push('gender');
    }

    const place = input.birthPlaceCode?.trim().toUpperCase();
    if (place && place !== decoded.birthPlaceCode) out.push('birthplace');

    return out;
}