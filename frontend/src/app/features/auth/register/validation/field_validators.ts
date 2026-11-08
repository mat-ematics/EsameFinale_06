/**
 * Field Validators Module
 * 
 * This module provides specialized validation functions for individual form fields.
 * Each validator checks format, length, and other constraints specific to that field type.
 * Validators return consistent FieldValidationResult objects for uniform error handling.
 */


/* --- IMPORTS --- */

import { FieldValidationResult, validFieldResult } from "./validation_types.js";
import { CAPPattern, emailPattern, getAge, isEmpty, namePattern, passwordPattern, safeTrim, usernamePattern } from "../utils/helpers.js";
import { ErrorCodes, TAX_CODE_MISMATCH_CODES, ValidationErrorCode } from "./error_codes.js";
import { postalCodePattern } from "./postal_codes.js";
import { GENERIC_TAX_ID, ITALY_ISO, RegistrationFormData } from "../types/registration_form_data.js";
import { decodeTaxCode, taxCodeMismatches } from "./tax_code.js";


/* --- CONSTANTS --- */

/**
 * Validation Rules Configuration
 * 
 * Centralized definition of validation constraints for various field types.
 * These values are used by the corresponding validator functions to enforce
 * consistent rules across the application.
 */
export const VALIDATION_RULES = {
    name: {
        minLength: 3,
    },
    username: {
        minLength: 3,
        maxLength: 32,
    },
    credit: {
        maxValue: 999999.99,
    }
} as const;


/* --- FUNCTIONS --- */

/**
 * Validate Required
 * 
 * Checks if a value is provided and not empty.
 * This is a basic validation used by most fields.
 * 
 * @param value - The value to check (converted to string)
 * @returns Validation result with REQUIRED error if value is empty
 */
export function validateRequired(value: unknown): FieldValidationResult {
    return isEmpty(String(value)) ? { isValid: false, errorCode: ErrorCodes.REQUIRED } : validFieldResult
}

/**
 * Validate Text
 * 
 * A flexible text validation function that checks multiple constraints:
 * - Required/optional status
 * - Minimum and maximum length
 * - Pattern matching (regex)
 * 
 * This is the base validator used by most field-specific validators.
 * 
 * @param text - The text value to validate
 * @param options - Optional validation constraints:
 *   - required: Whether the field is required (default: true)
 *   - minlength: Minimum character length
 *   - maxlength: Maximum character length
 *   - pattern: Regular expression to match against
 *   - formatErrorCode: Custom error code for pattern failures (default: INVALID_FORMAT)
 * @returns Validation result with appropriate error code if validation fails
 */
export function validateText(
        text: string,
        options?: Partial<{
            required: boolean,
            minlength: number,
            maxlength: number,
            pattern: RegExp,
            formatErrorCode: ValidationErrorCode,
        }>
    ) : FieldValidationResult {

        const value = safeTrim(text);
        const required = options?.required ?? true;
        const formatErrorCode = options?.formatErrorCode ?? ErrorCodes.INVALID_FORMAT;

        // Check if a required field is empty
        if (required && isEmpty(value)) {
            return { isValid: false, errorCode: ErrorCodes.REQUIRED }
        }

        // Check if text is too short
        if (options?.minlength !== undefined && value.length < options.minlength) {
            return { isValid: false, errorCode: ErrorCodes.TOO_SHORT }
        }

        // Check if text is too long
        if (options?.maxlength !== undefined && value.length > options.maxlength) {
            return { isValid: false, errorCode: ErrorCodes.TOO_LONG }
        }

        // Check if text matches the required pattern
        if (options?.pattern !== undefined && !options.pattern.test(value)) {
            return { isValid: false, errorCode: formatErrorCode }
        }

        return validFieldResult
}

/**
 * Validate Name
 * 
 * Validates a name field (first name, surname, etc.).
 * Requires minimum length and must match the namePattern regex.
 * 
 * @param name - The name to validate
 * @returns Validation result
 */
export const validateName = (name: string): FieldValidationResult => {
    return validateText(name, { minlength: VALIDATION_RULES.name.minLength, pattern: namePattern })
}

/**
 * Validate Email
 * 
 * Validates an email field using the emailPattern regex.
 * 
 * @param email - The email address to validate
 * @returns Validation result
 */
export const validateEmail = (email: string): FieldValidationResult => {
    return validateText(email, { pattern: emailPattern, formatErrorCode: ErrorCodes.INVALID_EMAIL });
}

/**
 * Validate Username
 * 
 * Validates a username field with length constraints and pattern matching.
 * Usernames must be between 3 and 32 characters and match the usernamePattern.
 * 
 * @param username - The username to validate
 * @returns Validation result
 */
export const validateUsername = (username: string): FieldValidationResult => {
    return validateText(username, {
        minlength: VALIDATION_RULES.username.minLength,
        maxlength: VALIDATION_RULES.username.maxLength,
        pattern: usernamePattern,
        formatErrorCode: ErrorCodes.INVALID_USERNAME
    });
}

/**
 * Validate Password
 * 
 * Validates a password field using the passwordPattern regex.
 * Pattern typically enforces complexity requirements (uppercase, numbers, special chars, etc.).
 * 
 * @param password - The password to validate
 * @returns Validation result
 */
export const validatePassword = (password: string): FieldValidationResult => {
    return validateText(password, { pattern: passwordPattern, formatErrorCode: ErrorCodes.INVALID_PASSWORD });
}

/**
 * Validate Confirm Password
 *
 * Checks the confirmation is filled in and identical to the password.
 * The comparison is exact (no trimming), since that is what will be sent.
 */
export function validateConfirmPassword(password: string, confirmation: string): FieldValidationResult {
    if (isEmpty(confirmation)) {
        return { isValid: false, errorCode: ErrorCodes.REQUIRED }
    }

    return password === confirmation
        ? validFieldResult
        : { isValid: false, errorCode: ErrorCodes.PASSWORD_MISMATCH }
}

/**
 * Validate Birth Date
 * 
 * Validates a birth date by checking:
 * 1. The date is provided
 * 2. The person is at least 18 years old
 * 
 * @param date - The birth date to validate (string or Date object)
 * @returns Validation result with DATE_TOO_YOUNG error if under 18
 */
export function validateBirthDate(date: string | Date): FieldValidationResult {

    if (isEmpty(String(date))) {
        return { isValid: false, errorCode: ErrorCodes.REQUIRED }
    }

    //Check if it is a valid date
    if (Number.isNaN(Date.parse(String(date)))) {
        return { isValid: false, errorCode: ErrorCodes.INVALID_FORMAT }
    };

    // Check if the person is at least 18 years old
    if (getAge(date) < 18) {
        return { isValid: false, errorCode: ErrorCodes.DATE_TOO_YOUNG }
    }

    return validFieldResult;
}

/**
 * Validate Select
 * 
 * Validates a dropdown select field by ensuring a value is selected.
 * This is used for fields like gender, country, currency, etc.
 * 
 * @param value - The selected value
 * @returns Validation result
 */
export function validateSelect(value: string): FieldValidationResult {
    return isEmpty(value) ? { isValid: false, errorCode: ErrorCodes.REQUIRED } : validFieldResult
}

/**
 * Validate CAP (Italian Postal Code)
 * 
 * Validates an Italian postal code (CAP - Codice di Avviamento Postale).
 * The CAP must match the CAPPattern regex format.
 * 
 * @param cap - The postal code to validate (string or number)
 * @param countryIso The ISO code of the country of the cap
 * @returns Validation result
 */
export const validateCAP = (cap: string, countryIso: string): FieldValidationResult => {
    return validateText(String(cap).trim().toUpperCase(), {
        pattern: postalCodePattern(countryIso),
        formatErrorCode: ErrorCodes.INVALID_CAP,
    });
}

/**
 * Validate Tax Code
 *
 * Italy: required, valid structure and check character, and consistent with
 * the name, surname, birth date, gender and (when chosen) birth municipality.
 * Any other country: optional, free-form with a lenient pattern.
 */
export function validateTaxCode(data: RegistrationFormData): FieldValidationResult {
    const code = safeTrim(data.tax_code).toUpperCase();
    const italy = data.country_iso === ITALY_ISO;

    if (isEmpty(code)) {
        return italy ? { isValid: false, errorCode: ErrorCodes.REQUIRED } : validFieldResult;
    }

    if (!italy) {
        return GENERIC_TAX_ID.test(code)
            ? validFieldResult
            : { isValid: false, errorCode: ErrorCodes.INVALID_TAX_CODE };
    }

    const decoded = decodeTaxCode(code);
    if (!decoded) return { isValid: false, errorCode: ErrorCodes.INVALID_TAX_CODE };

    const mismatch = taxCodeMismatches(decoded, {
        name: data.name,
        surname: data.surname,
        birthdate: data.birthdate,
        gender: data.gender,
        birthPlaceCode: data.birth_catastal_code,
    })[0];

    return mismatch
        ? { isValid: false, errorCode: TAX_CODE_MISMATCH_CODES[mismatch] }
        : validFieldResult;
}

/**
 * Validate Credit
 * 
 * Validates a credit amount by checking:
 * 1. A value is provided
 * 2. The value is a valid number
 * 3. The amount is greater than 0
 * 
 * @param credit - The credit amount to validate (string or number)
 * @returns Validation result with INVALID_CREDIT error if not a positive number
 */
export function validateCredit(credit: string | number): FieldValidationResult {
    if (isEmpty(String(credit))) {
        return { isValid: false, errorCode: ErrorCodes.REQUIRED }
    }

    // Convert to number and validate
    credit = Number(credit);

    // Check if the value is a valid positive number
    if (Number.isNaN(credit) || credit <= 0) {
        return { isValid: false, errorCode: ErrorCodes.INVALID_CREDIT }
    }

    if (credit > VALIDATION_RULES.credit.maxValue) {
        return { isValid: false, errorCode: ErrorCodes.CREDIT_TOO_HIGH }
    }

    return validFieldResult;
}