/**
 * Registration Form Data Module
 * 
 * Defines the data structure for the registration form, including:
 * - Form field types and unions
 * - The complete RegistrationFormData interface
 * - Utility function to extract form data from DOM
 * - Country and currency constants
 */


/* --- CONSTANTS --- */

/**
 * Country Italy ID Constant
 * 
 * The identifier used to represent Italy in the country select field.
 * Used to determine when the Italian municipality field should be required.
 */
export const COUNTRY_ITALY_ID = '1';


/* --- TYPES & INTERFACES --- */

/**
 * Gender Union Type
 * 
 * Represents the possible gender values for the registration form.
 */
export type gender = "male" | "female" | "other";

/**
 * Currency Union Type
 * 
 * Represents the supported currencies for credit transactions.
 */
export type currency = "EUR" | "USD";

/**
 * Registration Form Data Interface
 * 
 * Defines the complete structure of the registration form data.
 * 
 * Required fields:
 * - Personal: name, surname, email, username, password, birthdate, gender
 * - Location: country_id, street_address, house_number, cap
 * - Payment: credit, currency
 * 
 * Optional fields:
 * - italian_municipality_id (required only when country is Italy)
 * - locality (additional location info)
 * - additional_info (user notes)
 */
export interface RegistrationFormData {
    name: string,
    surname: string,
    email: string,
    username: string,
    password: string,
    birthdate: string,
    gender: gender,
    country_id: string,
    italian_municipality_id?: string,
    street_address: string,
    house_number: string,
    cap: string,
    locality?: string,
    additional_info?: string,
    credit: string,
    currency: currency,
}

/**
 * Field Name Type
 * 
 * A union type of all field names in the registration form.
 * Automatically derived from the RegistrationFormData interface keys.
 * Provides type-safe access to field names throughout the application.
 */
export type FieldName = keyof RegistrationFormData;


/* --- FUNCTIONS --- */

/**
 * Extract Registration Form Data
 * 
 * Extracts all form field values from an HTML form element and converts them
 * into a RegistrationFormData object.
 * 
 * Process:
 * 1. Create a FormData object from the form
 * 2. Extract each field value using the field name
 * 3. Cast values to appropriate types
 * 4. Handle optional fields with fallback to empty strings
 * 5. Return the typed RegistrationFormData object
 * 
 * @param form - The HTML form element to extract data from
 * @returns A RegistrationFormData object with all form values
 */
export function extractRegistrationFormData(form: HTMLFormElement): RegistrationFormData {
    const data = new FormData(form);

    return {
        name: data.get("name") as string,
        surname: data.get("surname") as string,
        email: data.get("email") as string,
        username: data.get("username") as string,
        password: data.get("password") as string,
        birthdate: data.get("birthdate") as string,
        gender: data.get("gender") as gender,
        country_id: data.get("country_id") as string,
        // Optional field with fallback to empty string
        italian_municipality_id: (data.get("italian_municipality_id") as string) ?? '',
        street_address: data.get("street_address") as string,
        house_number: data.get("house_number") as string,
        cap: data.get("cap") as string,
        // Optional fields
        locality: data.get("locality") as string,
        additional_info: data.get("additional_info") as string,
        credit: data.get("credit") as string,
        currency: data.get("currency") as currency,
    }
}