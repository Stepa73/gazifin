import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import incomeCalculator from './calculator/component.js';

window.Alpine = Alpine;

Alpine.plugin(collapse);
Alpine.data('incomeCalculator', incomeCalculator);

Alpine.start();
