import Alpine from 'alpinejs';
import { registerMenuPages } from './menu';

window.Alpine = Alpine;

registerMenuPages(Alpine);

Alpine.start();
