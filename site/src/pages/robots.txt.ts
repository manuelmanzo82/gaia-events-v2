import type { APIRoute } from 'astro';
import { IS_STAGING, SITE_URL } from '../config/site';

/**
 * robots.txt generato, non scritto a mano: in staging blocca tutto.
 * Nella v1 il file statico puntava a un dominio diverso dal resto del sito.
 */
export const GET: APIRoute = () => {
  const body = IS_STAGING
    ? ['User-agent: *', 'Disallow: /', ''].join('\n')
    : [
        'User-agent: *',
        'Allow: /',
        '',
        `Sitemap: ${SITE_URL}/sitemap.xml`,
        '',
        `# Per gli assistenti AI: ${SITE_URL}/llms.txt`,
        '',
      ].join('\n');

  return new Response(body, {
    headers: { 'Content-Type': 'text/plain; charset=utf-8' },
  });
};
