const header = document.querySelector('[data-header]');
const menuToggle = document.querySelector('[data-menu-toggle]');
const navigation = document.getElementById('site-navigation');

// Sluit het menu op een klein scherm. Zet de focus zo nodig terug op de menuknop.
function closeMenu(returnFocus = false) {
    navigation?.classList.remove('is-open');
    menuToggle?.setAttribute('aria-expanded', 'false');
    menuToggle?.setAttribute('aria-label', 'Menu openen');
    if (returnFocus) menuToggle?.focus();
}

menuToggle?.addEventListener('click', () => {
    const open = menuToggle.getAttribute('aria-expanded') !== 'true';
    menuToggle.setAttribute('aria-expanded', String(open));
    menuToggle.setAttribute('aria-label', open ? 'Menu sluiten' : 'Menu openen');
    navigation?.classList.toggle('is-open', open);
});
navigation?.querySelectorAll('a').forEach(link => link.addEventListener('click', () => closeMenu()));
document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && menuToggle?.getAttribute('aria-expanded') === 'true') closeMenu(true);
});
document.addEventListener('click', event => {
    if (menuToggle?.getAttribute('aria-expanded') === 'true' && !header?.contains(event.target)) closeMenu();
});
window.matchMedia('(min-width: 621px)').addEventListener('change', () => closeMenu());
const updateHeader = () => header?.classList.toggle('is-scrolled', window.scrollY > 12);
window.addEventListener('scroll', updateHeader, { passive: true });
updateHeader();

// Zonder JavaScript blijven alle rollen zichtbaar en werken de links.
const tabList = document.querySelector('[data-role-tabs]');
const tabs = [...document.querySelectorAll('[data-role-tab]')];
const panels = [...document.querySelectorAll('[data-role-panel]')];
// Toon de gekozen rol en verberg de andere voorbeelden.
function selectRole(id, focus = false) {
    if (!tabs.some(tab => tab.dataset.roleTab === id)) return;
    tabs.forEach(tab => {
        const active = tab.dataset.roleTab === id;
        tab.classList.toggle('is-active', active);
        tab.setAttribute('aria-selected', String(active));
        tab.tabIndex = active ? 0 : -1;
        if (active && focus) tab.focus();
    });
    panels.forEach(panel => {
        const active = panel.dataset.rolePanel === id;
        panel.hidden = !active;
        panel.classList.toggle('is-active', active);
    });
}
if (tabList && tabs.length) {
    tabList.setAttribute('role', 'tablist');
    tabs.forEach((tab, index) => {
        tab.setAttribute('role', 'tab');
        tab.setAttribute('aria-controls', `role-${tab.dataset.roleTab}`);
        tab.addEventListener('click', event => {
            event.preventDefault();
            selectRole(tab.dataset.roleTab);
        });
        tab.addEventListener('keydown', event => {
            let next;
            if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
            else if (event.key === 'ArrowLeft') next = (index - 1 + tabs.length) % tabs.length;
            else if (event.key === 'Home') next = 0;
            else if (event.key === 'End') next = tabs.length - 1;
            else return;
            event.preventDefault();
            selectRole(tabs[next].dataset.roleTab, true);
        });
    });
    panels.forEach(panel => {
        panel.setAttribute('role', 'tabpanel');
        panel.tabIndex = 0;
    });
    const initialRole = window.location.hash.startsWith('#role-') ? window.location.hash.slice(6) : tabs[0].dataset.roleTab;
    selectRole(tabs.some(tab => tab.dataset.roleTab === initialRole) ? initialRole : tabs[0].dataset.roleTab);
}
