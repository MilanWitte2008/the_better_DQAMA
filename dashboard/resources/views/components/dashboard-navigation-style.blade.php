/* Gedeelde navigatiestijl. Alleen zijbalk en bovenbalk worden aangepast. */
body.dashboard-navigation .sidebar {
    width:258px; padding:24px 14px 18px; background:#06283d; color:#f3f8ff;
    display:flex; flex-direction:column; flex-shrink:0; align-self:flex-start;
    position:sticky; top:0; height:100vh; overflow-y:auto;
    font-family:'Instrument Sans',Arial,Helvetica,sans-serif;
}
body.dashboard-navigation .sidebar .brand {
    display:flex; align-items:center; flex-wrap:nowrap; gap:12px; padding:4px 8px 24px;
    border-bottom:1px solid #315064; color:#fff; text-decoration:none; letter-spacing:0;
}
body.dashboard-navigation .sidebar .brand-mark {
    display:grid; place-items:center; flex-shrink:0; width:40px; height:40px;
    border-radius:9px; background:#1689e8; color:white; font-size:19px; font-weight:800;
}
body.dashboard-navigation .sidebar .brand strong,
body.dashboard-navigation .sidebar .profile-chip strong {display:block;font-size:14px;line-height:1.4;font-weight:700}
body.dashboard-navigation .sidebar .brand small,
body.dashboard-navigation .sidebar .profile-chip small {display:block;margin-top:4px;color:#bdd4e5;font-size:11px;line-height:1.5}
body.dashboard-navigation .sidebar .profile-chip {
    display:flex; align-items:center; gap:11px; margin:18px 1px 22px; padding:13px 10px;
    border-radius:9px; background:#0b3a53;
}
body.dashboard-navigation .sidebar .avatar,
body.dashboard-navigation .topbar .avatar {
    display:grid;place-items:center;flex-shrink:0;width:37px;height:37px;border-radius:12px;
    background:#e3f2ec;color:#087656;font-size:12px;font-weight:700;
}
body.dashboard-navigation .sidebar nav {display:grid;gap:5px;margin:0;width:100%}
body.dashboard-navigation .sidebar nav a,
body.dashboard-navigation .sidebar nav button {
    display:flex;align-items:center;gap:12px;min-height:42px;height:auto;width:100%;padding:12px 10px;
    border:0;border-radius:7px;background:transparent;color:#e0edf6;text-align:left;text-decoration:none;
    font:13px 'Instrument Sans',Arial,Helvetica,sans-serif;cursor:pointer;box-shadow:none;
    transition:background .18s,color .18s;transform:none;
}
body.dashboard-navigation .sidebar nav a:hover,
body.dashboard-navigation .sidebar nav button:hover,
body.dashboard-navigation .sidebar nav .active {
    background:#10445f;color:#fff;transform:none;
}
body.dashboard-navigation .sidebar nav .active {box-shadow:inset 3px 0 #079ee9;font-weight:700}
body.dashboard-navigation .sidebar nav .side-nav-button>span {width:14px;color:#8bb6d1;text-align:center}
body.dashboard-navigation .sidebar-footer {margin-top:auto;border-top:1px solid #315064;padding:16px 7px 0;font-size:12px}
body.dashboard-navigation .sidebar-footer small {display:block;color:#bdd4e5;margin-top:5px;font-size:11px}
body.dashboard-navigation .topbar {
    min-height:122px;height:auto;padding:24px 38px;display:flex;align-items:center;justify-content:space-between;gap:20px;
    color:#fff;border:0;font-family:'Instrument Sans',Arial,Helvetica,sans-serif;
    background-color:#183c57;
    background-image:linear-gradient(90deg,rgba(5,30,55,.94) 0%,rgba(5,30,55,.76) 46%,rgba(5,30,55,.44) 100%),repeating-linear-gradient(90deg,rgba(224,239,248,.13) 0 2px,transparent 2px 68px),repeating-linear-gradient(0deg,rgba(4,22,40,.62) 0 7px,transparent 7px 58px),linear-gradient(120deg,#123854 12%,#315b72 52%,#765e57);
    background-size:auto,68px 100%,100% 58px,100% 100%;background-position:center;
}
body.dashboard-navigation .topbar h1 {margin:0;font-size:27px;font-weight:600;letter-spacing:-.5px;line-height:1.25;color:#fff}
body.dashboard-navigation .topbar h1>span {display:inline}
body.dashboard-navigation .topbar p {margin:7px 0 0;font-size:13px;color:#e4f0f9;line-height:1.6}
body.dashboard-navigation .topbar-actions {display:flex;align-items:center;gap:18px;flex-shrink:0}
body.dashboard-navigation .topbar-actions form {margin:0}
body.dashboard-navigation .topbar .logout-button {border:0;border-radius:0;padding:0;background:none;color:white;font-size:12px;cursor:pointer}
body.dashboard-navigation .topbar .notification-wrapper {position:relative}
body.dashboard-navigation .topbar .notification {
    position:relative;
    width:44px;height:44px;display:grid;place-items:center;padding:0;border:1px solid rgba(255,255,255,.3);
    border-radius:12px;background:rgba(255,255,255,.1);color:#fff;cursor:pointer;
    transition:background 160ms ease,border-color 160ms ease,transform 160ms ease;
}
body.dashboard-navigation .topbar .notification svg,
body.dashboard-navigation .topbar .notification-panel-icon svg {
    width:21px;height:21px;stroke:currentColor;stroke-linecap:round;stroke-linejoin:round;stroke-width:1.8;
}
body.dashboard-navigation .topbar .notification:hover,
body.dashboard-navigation .topbar .notification:focus-visible,
body.dashboard-navigation .topbar .notification[aria-expanded="true"] {
    border-color:rgba(255,255,255,.65);background:rgba(255,255,255,.2);transform:translateY(-1px);
}
body.dashboard-navigation .topbar .notification:focus-visible {outline:2px solid #fff;outline-offset:2px}
body.dashboard-navigation .topbar .notification-panel {
    position:absolute;z-index:10;top:calc(100% + 14px);right:0;width:min(340px,calc(100vw - 36px));padding:20px;
    border:1px solid #e1eaf0;border-radius:14px;background:#fff;color:#092c4d;
    box-shadow:0 16px 42px rgba(6,40,61,.22);animation:notification-appear 160ms ease-out;
}
body.dashboard-navigation .topbar .notification-panel-heading {display:flex;align-items:center;gap:12px}
body.dashboard-navigation .topbar .notification-panel h2 {margin:0;color:#092c4d;font-size:17px;font-weight:700;line-height:1.3}
body.dashboard-navigation .topbar .notification-panel-icon {
    width:38px;height:38px;display:grid;flex-shrink:0;place-items:center;border-radius:11px;
    background:#eaf5fc;color:#127eb5;
}
body.dashboard-navigation .topbar .notification-panel p {margin:16px 0 0;color:#526a7b;font-size:14px;line-height:1.6}
body.dashboard-navigation .topbar .notification-count {
    position:absolute;top:-6px;right:-6px;display:grid;place-items:center;min-width:19px;height:19px;padding:0 5px;
    border:2px solid #183c57;border-radius:20px;background:#d54848;color:#fff;font-size:10px;font-weight:700;
}
body.dashboard-navigation .topbar .notification-list {display:grid;gap:8px;max-height:300px;overflow:auto;margin:16px 0 0;padding:0;list-style:none}
body.dashboard-navigation .topbar .notification-list li {border-radius:8px;background:#f3f8fb}
body.dashboard-navigation .topbar .notification-list li.notification-unread {background:#eaf5fc}
body.dashboard-navigation .topbar .notification-list form {margin:0}
body.dashboard-navigation .topbar .notification-list button {display:grid;width:100%;gap:5px;padding:11px;border:0;background:transparent;color:#214964;text-align:left;cursor:pointer}
body.dashboard-navigation .topbar .notification-list strong {font-size:12px}
body.dashboard-navigation .topbar .notification-list span,
body.dashboard-navigation .topbar .notification-list time {font-size:11px;line-height:1.5;color:#526a7b}
body.dashboard-navigation .topbar .notification-list time {color:#7792a5}
@keyframes notification-appear {
    from {opacity:0;transform:translateY(-5px)}
    to {opacity:1;transform:translateY(0)}
}
@media(prefers-reduced-motion:reduce) {
    body.dashboard-navigation .topbar .notification,
    body.dashboard-navigation .topbar .notification-panel {animation:none;transition:none}
}
body.docent-page.dashboard-navigation {display:grid;grid-template-columns:258px minmax(0,1fr);align-items:start}
body.docent-page.dashboard-navigation .workspace {min-width:0;margin-left:0}
body.docent-page.dashboard-navigation .sidebar {overflow-y:auto}
body.docent-page.dashboard-navigation .sidebar nav a>span {font-size:13px}
@media(max-width:1100px) {
    body.dashboard-navigation .sidebar{width:220px}
    body.docent-page.dashboard-navigation {grid-template-columns:220px minmax(0,1fr)}
    body.dashboard-navigation .topbar{padding:20px 24px;flex-wrap:wrap}
}
@media(max-width:720px) {
    body.dashboard-navigation .sidebar{width:64px;padding:18px 8px}
    body.docent-page.dashboard-navigation {grid-template-columns:64px minmax(0,1fr)}
    body.docent-page.dashboard-navigation .sidebar{position:fixed;inset:0 auto 0 0}
    body.dashboard-navigation .sidebar .brand{padding:0 4px 18px;gap:0}
    body.dashboard-navigation .sidebar .brand>span:last-child,
    body.dashboard-navigation .sidebar .profile-chip>span:last-child{display:none}
    body.dashboard-navigation .sidebar .profile-chip{padding:7px 4px;justify-content:center;background:transparent}
    body.dashboard-navigation .sidebar nav a,
    body.dashboard-navigation .sidebar nav button{display:flex;font-size:0;justify-content:center;padding:12px 0;gap:0}
    body.dashboard-navigation .sidebar nav .side-nav-button>span{font-size:18px}
    body.docent-page.dashboard-navigation .sidebar nav a{font-size:18px}
    body.docent-page.dashboard-navigation .sidebar nav a>span,
    body.docent-page.dashboard-navigation .sidebar nav a>b{display:none}
    body.dashboard-navigation .sidebar-footer{font-size:0;padding:12px 0}
    body.dashboard-navigation .sidebar-footer small{font-size:0}
    body.dashboard-navigation .topbar{padding:18px;align-items:flex-start;flex-direction:column}
    body.dashboard-navigation .topbar h1{font-size:21px}
    body.dashboard-navigation .topbar-actions{gap:12px;width:100%;flex-wrap:wrap}
}
@media(prefers-reduced-motion:reduce){body.dashboard-navigation .sidebar nav a,body.dashboard-navigation .sidebar nav button{transition:none}}

body.dashboard-navigation .sidebar .firda-nav-logo {
    display:block;width:56px;height:auto;flex-shrink:0;border-radius:8px;
    box-shadow:0 3px 12px #0002;
}
@media(max-width:700px) {
    body.dashboard-navigation .sidebar .firda-nav-logo {width:36px;border-radius:5px;}
}
