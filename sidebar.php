<aside id="sidebar-root" class="nav" aria-label="Hoofdnavigatie"></aside>

<script>
  window.sidebarData = <?= json_encode(
      $sidebarData,
      JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
  ) ?>;
</script>
<script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
<script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
<script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
<script type="text/babel">
  const sidebar = window.sidebarData;
  const icon = (name) => name === "home" ? (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="m4 11 8-7 8 7v9h-6v-6h-4v6H4z" />
    </svg>
  ) : (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <circle cx="12" cy="12" r="7" />
      <circle cx="12" cy="12" r="2" />
    </svg>
  );

  function Sidebar() {
    return (
      <>
        <div className="nav-brand">
          <div className="brand-mark">{sidebar.brandMark}</div>
          <div>
            <b>{sidebar.brand}</b>
            <small>{sidebar.tagline}</small>
          </div>
        </div>
        <div className="nav-user">
          <span className={`avatar ${sidebar.avatarClass}`}>{sidebar.initials}</span>
          <div>
            <b>{sidebar.name}</b>
            <small>{sidebar.roleLabel}</small>
          </div>
        </div>
        <nav className="nav-links" aria-label="Navigatie">
          {sidebar.items.map((item) => (
            <a
              key={item.page}
              href={item.href}
              className={item.page === sidebar.activePage ? "active" : ""}
              aria-current={item.page === sidebar.activePage ? "page" : undefined}
            >
              <span className="nav-icon">{icon(item.icon)}</span>
              <span>{item.label}</span>
            </a>
          ))}
        </nav>
        <div className="nav-footer">
          <b>Systeem online</b>
          <small>Voorbeeldomgeving</small>
        </div>
      </>
    );
  }

  ReactDOM.createRoot(document.getElementById("sidebar-root")).render(<Sidebar />);
</script>