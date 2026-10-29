/**
 * Validation Types Module
 * 
 * This module defines TypeScript types and interfaces for form validation results,
 * including:
 * - Individual field validation results with error codes
 * - Complete form validation results for all fields
 * - Factory functions for creating validation result objects
 */


/* --- IMPORTS --- */

import { RegistrationFormData } from "../types/registration_form_data.js";
import { ValidationErrorCode } from "./error_codes.js";


/* --- TYPES & INTERFACES --- */

/**
 * Field Validation Result Interface
 * 
 * Represents the validation status of a single form field.
 * 
 * @property isValid - Whether the field passed validation
 * @property errorCode - The specific error code if validation failed, or null if valid
 */
export interface FieldValidationResult {
    isValid: boolean,
    errorCode: ValidationErrorCode | null,
}

/**
 * Form Fields Validation Result Type
 * 
 * A mapped type that creates a validation result for each field in the RegistrationFormData.
 * This ensures type safety by requiring validation results for every form field.
 * Each field key maps to its corresponding FieldValidationResult.
 */
export type FormFieldsValidationResult = {
    [K in keyof RegistrationFormData]: FieldValidationResult;
}

/**
 * Form Validation Result Interface
 * 
 * Represents the complete validation status of the entire registration form.
 * 
 * @property isValid - Whether all fields in the form passed validation
 * @property fields - Individual validation results for each form field
 */
export interface FormValidationResult {
    isValid: boolean,
    fields: FormFieldsValidationResult,
}


/* --- CONSTANTS --- */

/**
 * Immutable Valid Field Result
 * 
 * A constant that represents a successful field validation (no errors).
 * Used as the base template for creating valid field results.
 * Marked as `const` to prevent accidental modifications.
 */
export const validFieldResult: FieldValidationResult = {
    isValid: true,
    errorCode: null,
} as const satisfies FieldValidationResult;


/* --- FUNCTIONS --- */

/**
 * Create Valid Field Result Factory Function
 * 
 * Creates a new valid field validation result with optional property overrides.
 * Useful for creating variations of the base valid result without modifying the
 * immutable `validFieldResult` constant.
 * 
 * @param overrides - Partial object to override default properties
 * @returns A new FieldValidationResult object with applied overrides
 */
export const createValidFieldResult = (
    overrides?: Partial<FieldValidationResult>
): FieldValidationResult => ({
    ...validFieldResult,
    ...overrides,
})

