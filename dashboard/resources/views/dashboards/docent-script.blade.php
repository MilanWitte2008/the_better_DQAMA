/* De actieve navigatie volgt de URL, ook bij terug/vooruit en toetsenbordbediening. */
(() => {
const links = [...document.querySelectorAll('.sidebar nav a')];
const title = document.getElementById('section-title');
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const activate = (id, animate = false) => {
const target = id === 'student-detail' ? 'studenten' : id;
links.forEach(link => {
const active = link.hash === '#' + target;
link.classList.toggle('active', active);
if (active) {
link.setAttribute('aria-current', 'location');
title.textContent = link.querySelector('span').textContent;
} else link.removeAttribute('aria-current');
});
const section = document.getElementById(id);
if (animate && section && !reducedMotion.matches) {
section.animate([{opacity: .5, transform: 'translateY(8px)'}, {opacity: 1, transform: 'translateY(0)'}], {duration: 300,
easing: 'ease-out'});
}
};
const known = id => links.some(link => link.hash === '#' + id) || id === 'student-detail';
const fromHash = () => {
const id = location.hash.slice(1);
activate(known(id) ? id : 'inhoud', true);
};
links.forEach(link => link.addEventListener('click', () => {
if (location.hash === link.hash) activate(link.hash.slice(1), true);
}));
window.addEventListener('hashchange', fromHash);
fromHash();
const error = document.querySelector('.validation-feedback');
if (error) { error.focus(); error.scrollIntoView({block: 'center'}); }
const feedback = document.querySelector('.save-feedback');
if (feedback) {
const close = document.createElement('button');
close.type = 'button'; close.textContent = '×'; close.setAttribute('aria-label', 'Melding sluiten');
close.addEventListener('click', () => feedback.remove()); feedback.append(close);
}
document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => {
if (!confirm(form.dataset.confirm)) event.preventDefault();
}));
})();
