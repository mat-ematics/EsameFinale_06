/**
 * Helpers Module
 * 
 * Provides utility functions and regex patterns used for form validation and data manipulation.
 * Includes validation patterns for various field types and helper functions for date/string operations.
 */


/* --- REGULAR EXPRESSIONS --- */

/**
 * Name Pattern
 * 
 * Matches names with 3-100 characters containing only letters and spaces.
 * Supports Unicode letters to handle international names.
 */
export const namePattern = /^[\p{L} ]{3,100}$/u;

const emailBody = /(?:[a-z0-9!#$%&'*+\x2f=?^_`\x7b-\x7d~\x2d]+(?:\.[a-z0-9!#$%&'*+\x2f=?^_`\x7b-\x7d~\x2d]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9\x2d]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9\x2d]*[a-z0-9])?|\[(?:(?:(2(5[0-5]|[0-4][0-9])|1[0-9][0-9]|[1-9]?[0-9]))\.){3}(?:(2(5[0-5]|[0-4][0-9])|1[0-9][0-9]|[1-9]?[0-9])|[a-z0-9\x2d]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/;
/**
 * Email Pattern
 * 
 * Matches valid email addresses according to RFC 5322 standard.
 * Supports standard email formats including dots, hyphens, and special characters in the local part.
*/
export const emailPattern = new RegExp(`^(?:${emailBody.source})$`, 'i');

/**
 * Username Pattern
 * 
 * Matches usernames with 3-32 characters containing only alphanumeric characters and underscores.
 */
export const usernamePattern = /^[0-9A-Za-z_]{3,32}$/;

/**
 * Password Pattern
 * 
 * Matches passwords with 8-100 characters that must contain:
 * - At least one uppercase letter
 * - At least one lowercase letter
 * - At least one digit
 * - At least one special character (#?!@$%^&*-)
 */
export const passwordPattern = /^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,100}$/;

/**
 * CAP Pattern (Italian Postal Code)
 * 
 * Matches Italian postal codes (CAP - Codice di Avviamento Postale).
 * Must be exactly 5 digits.
 */
export const CAPPattern = /^\d{5}$/;


/* --- FUNCTIONS --- */

/**
 * Get Age
 * 
 * Calculates the age of a person based on their birth date.
 * Accurately handles leap years and edge cases where the birthday hasn't occurred yet this year.
 * 
 * @param birthDate - The birth date as a string or Date object
 * @returns The person's age in years
 */
export function getAge(birthDate: string | Date): number {
    const today = new Date();
    const BOD = new Date(birthDate);
    let age = today.getFullYear() - BOD.getFullYear();
    const monthDiff = today.getMonth() - BOD.getMonth();

    // If birthday hasn't occurred this year, subtract 1 from age
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < BOD.getDate())) {
        age--;
    }
    
    return age;
}

/**
 * Safe Trim
 * 
 * Safely trims whitespace from a string, handling undefined or null values.
 * Returns an empty string if the input is falsy.
 * 
 * @param value - The string to trim (optional)
 * @returns The trimmed string, or an empty string if input is undefined/null
 */
export function safeTrim(value?: string): string {
    return value ? value.trim() : "";
}

/**
 * Is Empty
 * 
 * Checks if a string is empty, null, undefined, or contains only whitespace.
 * Uses safeTrim internally to handle all edge cases.
 * 
 * @param value - The string to check
 * @returns True if the string is empty or contains only whitespace, false otherwise
 */
export function isEmpty(value: string): boolean {
    const trimmedValue = safeTrim(value);
    return trimmedValue === '' || trimmedValue === null || trimmedValue === undefined;
}
