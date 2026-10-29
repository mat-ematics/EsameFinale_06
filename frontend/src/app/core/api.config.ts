import { environment } from '../../environments/environment';

const stripSlashes = (value: string) => value.replace(/^\/+|\/+$/g, '');

/** Percorso base API versione 1 */
export const API_V1 = (() => {
  const base = stripSlashes(environment.apiUrl);
  const version = stripSlashes(environment.apiVersion);

  // if the base URL already ends with the version, don't append it again
  return base.endsWith(`/${version}`) ? base : `${base}/${version}`;
})();