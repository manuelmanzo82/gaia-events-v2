import { existsIn } from '../../../lib/availability';
import { feedRss } from '../../../lib/rss';

// Il feed inglese esiste solo se esiste il Magazine in inglese.
export const GET = () => (existsIn('blog', 'en') ? feedRss('en') : new Response(null, { status: 404 }));
