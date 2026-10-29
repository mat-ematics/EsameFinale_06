/**
 * Error Codes Module
 * 
 * Defines all validation error codes used throughout the form validation system.
 * These codes represent specific validation failures and are mapped to user-friendly
 * error messages in the UI layer.
 */


/* --- IMPORTS --- */
//Eventual imports here


/* --- CONSTANTS --- */

/**
 * Validation Error Codes
 * 
 * Centralized definition of all possible validation error codes:
 * - REQUIRED: Field value is missing or empty
 * - TOO_SHORT: Text input is shorter than the minimum length
 * - TOO_LONG: Text input is longer than the maximum length
 * - INVALID_FORMAT: Value does not match the required pattern
 * - INVALID_EMAIL: Email address format is invalid
 * - INVALID_USERNAME: Username format is invalid
 * - INVALID_PASSWORD: Password does not meet complexity requirements
 * - DATE_TOO_YOUNG: User is under 18 years old
 * - INVALID_CAP: Italian postal code (CAP) format is invalid
 * - INVALID_CREDIT: Credit amount is not a positive number
 * - MUNICIPALITY_REQUIRED: Municipality must be selected when country is Italy
 */
export const ErrorCodes = {
    REQUIRED: "REQUIRED",
    TOO_SHORT: "TOO_SHORT",
    TOO_LONG: "TOO_LONG",
    INVALID_FORMAT: "INVALID_FORMAT",
    INVALID_EMAIL: "INVALID_EMAIL",
    INVALID_USERNAME: "INVALID_USERNAME",
    INVALID_PASSWORD: "INVALID_PASSWORD",
    DATE_TOO_YOUNG: "DATE_TOO_YOUNG",
    INVALID_CAP: "INVALID_CAP",
    INVALID_CREDIT: "INVALID_CREDIT",
    MUNICIPALITY_REQUIRED: "MUNICIPALITY_REQUIRED",
} as const;


/* --- TYPES & INTERFACES --- */

/**
 * Validation Error Code Type
 * 
 * A union type of all valid error code string literals.
 * This ensures type safety when working with error codes throughout the application.
 * Automatically inferred from the ErrorCodes object to prevent inconsistencies.
 */
export type ValidationErrorCode = typeof ErrorCodes[keyof typeof ErrorCodes];