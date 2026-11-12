const notificationToggle = document.querySelector('[data-notification-toggle]');
const notificationPanel = document.querySelector('[data-notification-panel]');

if (notificationToggle && notificationPanel) {
    const closeNotifications = () => {
        notificationToggle.setAttribute('aria-expanded', 'false');
        notificationPanel.hidden = true;
    };

    notificationToggle.addEventListener('click', () => {
        const isExpanded = notificationToggle.getAttribute('aria-expanded') === 'true';
        notificationToggle.setAttribute('aria-expanded', String(!isExpanded));
        notificationPanel.hidden = isExpanded;
    });

    document.addEventListener('click', (event) => {
        if (event.target instanceof Element && !event.target.closest('.notification-wrapper')) {
            closeNotifications();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && notificationToggle.getAttribute('aria-expanded') === 'true') {
            closeNotifications();
            notificationToggle.focus();
        }
    });
}
