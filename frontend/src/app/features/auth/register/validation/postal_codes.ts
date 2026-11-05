import { CAPPattern } from "../utils/helpers";

export interface PostalCodeFormat { pattern: RegExp; example: string }

/** Keep in sync with app/Rules/PostalCode.php. Values are uppercased before testing. */
export const POSTAL_CODE_FORMATS: Record<string, PostalCodeFormat> = {
    IT: { pattern: CAPPattern, example: '00100' },
    FR: { pattern: /^\d{5}$/, example: '75001' },
    DE: { pattern: /^\d{5}$/, example: '10115' },
    ES: { pattern: /^\d{5}$/, example: '28013' },
    CH: { pattern: /^\d{4}$/, example: '8001' },
    AT: { pattern: /^\d{4}$/, example: '1010' },
    PT: { pattern: /^\d{4}-\d{3}$/, example: '1000-001' },
    NL: { pattern: /^\d{4} ?[A-Z]{2}$/, example: '1011 AB' },
    PL: { pattern: /^\d{2}-\d{3}$/, example: '00-950' },
    US: { pattern: /^\d{5}(-\d{4})?$/, example: '10001' },
    CA: { pattern: /^[ABCEGHJ-NPRSTVXY]\d[ABCEGHJ-NPRSTV-Z] ?\d[ABCEGHJ-NPRSTV-Z]\d$/, example: 'K1A 0B1' },
    GB: { pattern: /^[A-Z]{1,2}\d[A-Z\d]? ?\d[A-Z]{2}$/, example: 'SW1A 1AA' },
};

/** Any other country: 3-11 letters, digits, spaces or hyphens */
export const GENERIC_POSTAL_CODE = /^[A-Z0-9][A-Z0-9 -]{1,9}[A-Z0-9]$/;

export const postalCodePattern = (iso: string): RegExp =>
    POSTAL_CODE_FORMATS[iso]?.pattern ?? GENERIC_POSTAL_CODE;