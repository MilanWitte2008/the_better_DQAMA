const coachPage = window.coachPage;

const sectionLabels = {
  overzicht: "Overzicht",
  leeruitkomsten: "Leeruitkomsten",
  doelen: "Doelen",
  gesprekken: "Gesprekken",
  notities: "Notities",
};

function coachPageUrl(page, student, section = "overzicht") {
  const params = new URLSearchParams({ page });
  if (student) params.set("student", student.idstudent);
  if (section !== "overzicht") params.set("section", section);
  if (coachPage.search) params.set("q", coachPage.search);
  return `docent.php?${params}`;
}

function studentProgress(student) {
  return Math.max(0, Math.min(100, Number(student?.progress) || 0));
}

function initials(name) {
  return (name || "Student")
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => Array.from(part)[0] || "")
    .join("")
    .toUpperCase();
}

function dutchDate(value) {
  if (!value) return "Nog niet bijgewerkt";
  const date = new Date(`${value.slice(0, 10)}T12:00:00`);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat("nl-NL", {
    day: "numeric",
    month: "short",
    year: "numeric",
  }).format(date);
}

function statusProgress(outcome) {
  if (outcome.vooruitgang !== null && outcome.vooruitgang !== "") {
    return Math.max(0, Math.min(100, Number(outcome.vooruitgang) || 0));
  }
  if ((outcome.status || "").toLowerCase() === "behaald") return 100;
  if ((outcome.status || "").toLowerCase() === "bezig") return 60;
  return 0;
}

function Notice({ children, kind = "info" }) {
  if (!children) return null;
  return <div className={`coach-notice ${kind}`} role={kind === "error" ? "alert" : "status"}>{children}</div>;
}

function StudentList({ compact = false }) {
  return (
    <section className={`coach-card student-roster ${compact ? "compact-roster" : ""}`}>
      <div className="roster-heading">
        <div>
          <h3>Studenten</h3>
          <p>{coachPage.totalStudents} toegewezen studenten</p>
        </div>
        {compact && <a className="text-link" href="docent.php?page=studenten">Alle studenten <span aria-hidden="true">→</span></a>}
      </div>
      {!compact && (
        <form className="coach-filters" method="get" action="docent.php">
          <input type="hidden" name="page" value="studenten" />
          <label className="sr-only" htmlFor="student-search">Zoek op naam of studentnummer</label>
          <input
            id="student-search"
            className="coach-filter-input"
            type="search"
            name="q"
            placeholder="Zoek op naam of studentnummer"
            defaultValue={coachPage.search}
          />
          <label className="sr-only" htmlFor="education-filter">Filter op opleiding</label>
          <select
            id="education-filter"
            className="coach-filter-select"
            name="opleiding"
            defaultValue={coachPage.educationFilter}
          >
            <option value="">Alle opleidingen</option>
            {coachPage.educations.map((education) => <option key={education} value={education}>{education}</option>)}
          </select>
          <button type="submit">Zoeken</button>
        </form>
      )}
      {coachPage.students.length > 0 ? (
        <div className="roster-list">
          {coachPage.students.map((student) => (
            <a
              className={`roster-row ${String(student.idstudent) === String(coachPage.selectedStudent?.idstudent) ? "selected" : ""}`}
              href={coachPageUrl(coachPage.page, student)}
              key={student.idstudent}
            >
              <span className="avatar roster-avatar">{student.initials || initials(student.name)}</span>
              <span className="roster-person">
                <b>{student.name}</b>
                <small>{student.opleiding || "Opleiding niet ingevuld"}{student.klas ? ` · ${student.klas}` : ""}</small>
              </span>
              <span className="roster-progress">
                <span className="progress-track"><i style={{ width: `${studentProgress(student)}%` }} /></span>
                <b>{studentProgress(student)}%</b>
              </span>
              <span className="roster-arrow" aria-hidden="true">›</span>
            </a>
          ))}
        </div>
      ) : (
        <p className="coach-empty">
          {coachPage.error || (coachPage.search || coachPage.educationFilter
            ? "Geen studenten gevonden met deze zoekopdracht."
            : "Er zijn nog geen studenten aan je coachaccount toegewezen.")}
        </p>
      )}
    </section>
  );
}

function StudentTabs({ student }) {
  return (
    <nav className="student-tabs" aria-label="Studentoverzicht">
      {Object.entries(sectionLabels).map(([section, label]) => (
        <a
          key={section}
          className={coachPage.activeSection === section ? "active" : ""}
          href={coachPageUrl(coachPage.page, student, section)}
          aria-current={coachPage.activeSection === section ? "page" : undefined}
        >
          {label}
        </a>
      ))}
    </nav>
  );
}

function StudentHeader({ student }) {
  return (
    <div className="student-profile">
      <span className="avatar student-profile-avatar">{student.initials || initials(student.name)}</span>
      <div className="student-profile-name">
        <b>{student.name}</b>
        <small>{student.opleiding || "Opleiding niet ingevuld"}{student.klas ? ` · ${student.klas}` : ""}</small>
        <span className="growth-pill">Voortgang {studentProgress(student)}%</span>
      </div>
      <a
        className="primary-button schedule-button"
        href={coachPageUrl("coachgesprekken", student)}
      >
        Gesprek plannen
      </a>
    </div>
  );
}

function OverviewStats({ student }) {
  const latestUpdate = coachPage.outcomes
    .map((outcome) => outcome.laatste_update)
    .filter(Boolean)
    .sort()
    .at(-1);
  const count = student.outcome_count || 0;

  return (
    <div className="student-stats">
      <div className="stat-card stat-progress">
        <progress-ring style={{ "--progress-angle": `${studentProgress(student) * 3.6}deg` }}>
          <span>{studentProgress(student)}%</span>
        </progress-ring>
        <span>Totale voortgang</span>
      </div>
      <div className="stat-card"><strong>{count}</strong><span>Leeruitkomsten</span></div>
      <div className="stat-card"><strong>{coachPage.meetings.length}</strong><span>Coachgesprekken</span></div>
      <div className="stat-card"><strong>{dutchDate(latestUpdate)}</strong><span>Laatste update</span></div>
    </div>
  );
}

function Outcomes() {
  if (!coachPage.outcomes.length) return <p className="coach-empty">Er zijn nog geen leeruitkomsten voor deze student vastgelegd.</p>;
  return (
    <section className="coach-card">
      <h3>Leeruitkomsten</h3>
      <div className="outcome-list">
        {coachPage.outcomes.map((outcome) => (
          <div className="outcome-row" key={outcome.code || outcome.titel}>
            <div className="outcome-label"><span>{outcome.titel}</span><b>{statusProgress(outcome)}%</b></div>
            {outcome.phase_name && <small>{outcome.phase_name}</small>}
            <span className="progress-track"><i style={{ width: `${statusProgress(outcome)}%` }} /></span>
          </div>
        ))}
      </div>
    </section>
  );
}

function Goals() {
  return (
    <section className="coach-card">
      <h3>Leerdoelen en voorbereiding leerbedrijf</h3>
      {coachPage.goals.length ? (
        <ul className="coach-list">
          {coachPage.goals.map((goal, index) => (
            <li key={`${goal.titel}-${index}`}>
              <span><b>{goal.titel}</b>{goal.omschrijving && <small>{goal.omschrijving}</small>}</span>
              <em>{goal.status || "Te doen"}</em>
            </li>
          ))}
        </ul>
      ) : <p className="coach-empty">Er zijn nog geen leerdoelen voor deze student vastgelegd.</p>}
    </section>
  );
}

function Notes({ student, page = coachPage.page }) {
  return (
    <section className="coach-card">
      <h3>Coachnotities</h3>
      {coachPage.notes.length ? (
        <ul className="coach-list dated-list">
          {coachPage.notes.map((note, index) => (
            <li key={`${note.datum}-${index}`}>
              <span>{dutchDate(note.datum)} · {note.inhoud}</span>
            </li>
          ))}
        </ul>
      ) : <p className="coach-empty">Er zijn nog geen coachnotities voor deze student.</p>}
      <form className="dashboard-form" method="post" action="docent.php">
        <input type="hidden" name="_token" value={coachPage.csrfToken} />
        <input type="hidden" name="form_action" value="save-note" />
        <input type="hidden" name="student_id" value={student.idstudent} />
        <input type="hidden" name="return_page" value={page} />
        <label htmlFor="coach-note">Notitie voor {student.name}</label>
        <textarea id="coach-note" name="note" rows="3" placeholder="Schrijf een coachnotitie" required />
        <button type="submit">Notitie bewaren</button>
      </form>
    </section>
  );
}

function Meetings({ student, page = coachPage.page }) {
  return (
    <section className="coach-card">
      <h3>Coachgesprekken</h3>
      {coachPage.meetings.length ? (
        <ul className="coach-list dated-list">
          {coachPage.meetings.map((meeting, index) => (
            <li key={`${meeting.datum}-${index}`}>
              <span><b>{dutchDate(meeting.datum)}</b> · {meeting.onderwerp || "Coachgesprek"}</span>
              {meeting.inhoud && <small>{meeting.inhoud}</small>}
            </li>
          ))}
        </ul>
      ) : <p className="coach-empty">Er zijn nog geen coachgesprekken voor deze student.</p>}
      <form className="dashboard-form" method="post" action="docent.php">
        <input type="hidden" name="_token" value={coachPage.csrfToken} />
        <input type="hidden" name="form_action" value="save-meeting" />
        <input type="hidden" name="student_id" value={student.idstudent} />
        <input type="hidden" name="return_page" value={page} />
        <label htmlFor="meeting-date">Datum gesprek</label>
        <input id="meeting-date" type="date" name="meeting_date" min={coachPage.today} required />
        <label htmlFor="meeting-subject">Onderwerp</label>
        <input id="meeting-subject" type="text" name="subject" placeholder="Bijvoorbeeld: voortgang leerdoelen" maxLength="255" required />
        <button type="submit">Gesprek opslaan</button>
      </form>
    </section>
  );
}

function StudentDetail({ student, full = false }) {
  if (!student) {
    return (
      <section className="coach-card student-detail-empty">
        <h3>Studentoverzicht</h3>
        <p className="coach-empty">Selecteer een student om de voortgang en coachgegevens te bekijken.</p>
      </section>
    );
  }

  let section = coachPage.activeSection;
  if (!full) section = "overzicht";

  return (
    <section className={`coach-card student-detail ${full ? "full-detail" : ""}`}>
      <StudentHeader student={student} />
      <StudentTabs student={student} />
      {section === "overzicht" && <OverviewStats student={student} />}
      {section === "overzicht" && full && <Outcomes />}
      {section === "overzicht" && full && <Goals />}
      {section === "leeruitkomsten" && <Outcomes />}
      {section === "doelen" && <Goals />}
      {section === "gesprekken" && <Meetings student={student} />}
      {section === "notities" && <Notes student={student} />}
    </section>
  );
}

function Dashboard() {
  const student = coachPage.selectedStudent;
  return (
    <>
      <div className="coach-page-heading">
        <div><h2>Dashboard</h2><p>Een actueel overzicht van jouw studenten en hun ontwikkeling.</p></div>
        <span className="student-count">{coachPage.totalStudents} studenten</span>
      </div>
      <Notice kind="error">{coachPage.error}</Notice>
      <Notice>{coachPage.message}</Notice>
      <StudentList compact />
      <StudentDetail student={student} full />
      {student && (
        <div className="coach-two-column">
          <section className="coach-card phase-card">
            <h3>Ontwikkeling per fase</h3>
            {coachPage.phases.length ? coachPage.phases.map((phase) => {
              const progress = Math.round(Number(phase.progress) || 0);
              return (
                <div className="outcome-row" key={phase.naam}>
                  <div className="outcome-label"><span>{phase.naam}</span><b>{progress}%</b></div>
                  <span className="progress-track"><i style={{ width: `${progress}%` }} /></span>
                </div>
              );
            }) : <p className="coach-empty">Er zijn nog geen fasegegevens beschikbaar.</p>}
          </section>
          <Goals />
        </div>
      )}
      {student && (
        <div className="coach-two-column">
          <Notes student={student} page="dashboard" />
          <Meetings student={student} page="dashboard" />
        </div>
      )}
    </>
  );
}

function StudentsPage() {
  return (
    <>
      <div className="coach-page-heading"><div><h2>Studenten</h2><p>Zoek een student en open het persoonlijke voortgangsoverzicht.</p></div></div>
      <Notice kind="error">{coachPage.error}</Notice>
      <Notice>{coachPage.message}</Notice>
      <StudentList />
      <StudentDetail student={coachPage.selectedStudent} full />
    </>
  );
}

function MeetingsPage() {
  return (
    <>
      <div className="coach-page-heading"><div><h2>Coachgesprekken</h2><p>Bekijk eerdere gesprekken of leg een nieuw gesprek vast.</p></div></div>
      <Notice kind="error">{coachPage.error}</Notice>
      <Notice>{coachPage.message}</Notice>
      {coachPage.selectedStudent ? (
        <div className="coach-page-stack">
          <StudentHeader student={coachPage.selectedStudent} />
          <Meetings student={coachPage.selectedStudent} />
        </div>
      ) : <p className="coach-empty">Wijs eerst een student toe aan je account om gesprekken te plannen.</p>}
    </>
  );
}

function NotesPage() {
  return (
    <>
      <div className="coach-page-heading"><div><h2>Notities</h2><p>Bewaar observaties bij de geselecteerde student.</p></div></div>
      <Notice kind="error">{coachPage.error}</Notice>
      <Notice>{coachPage.message}</Notice>
      {coachPage.selectedStudent
        ? <Notes student={coachPage.selectedStudent} />
        : <p className="coach-empty">Wijs eerst een student toe aan je account om coachnotities te bewaren.</p>}
    </>
  );
}

function ReportsPage() {
  return (
    <>
      <div className="coach-page-heading"><div><h2>Rapportages</h2><p>Vergelijk voortgang en bekijk de actuele leeruitkomsten.</p></div></div>
      <Notice kind="error">{coachPage.error}</Notice>
      <section className="coach-card">
        <h3>Ontwikkeling over tijd</h3>
        <label className="report-period" htmlFor="report-period">Vergelijk met</label>
        <select id="report-period" defaultValue="6"><option value="3">3 maanden geleden</option><option value="6">6 maanden geleden</option><option value="12">12 maanden geleden</option></select>
        <p className="comparison-summary">Bekijk de individuele voortgang en de actuele leeruitkomsten bij de geselecteerde student.</p>
      </section>
      {coachPage.selectedStudent ? <Outcomes /> : <p className="coach-empty">Er zijn geen studenten beschikbaar voor een rapportage.</p>}
    </>
  );
}

function SettingsPage() {
  return (
    <>
      <div className="coach-page-heading"><div><h2>Instellingen</h2><p>Bekijk de voorkeuren voor je studentenoverzicht en herinneringen.</p></div></div>
      <section className="coach-card settings-card">
        <h3>Instellingen</h3>
        <ul className="coach-list">
          <li><span>Standaard periode</span><b>Afgelopen 6 maanden</b></li>
          <li><span>Studentenfilter</span><b>Alle toegewezen opleidingen</b></li>
          <li><span>Gespreksherinnering</span><b>7 dagen voor afspraak</b></li>
        </ul>
        <p className="muted">De voorkeuren gelden alleen voor jouw coachoverzicht.</p>
      </section>
    </>
  );
}

function CoachApp() {
  if (coachPage.page === "studenten") return <StudentsPage />;
  if (coachPage.page === "coachgesprekken") return <MeetingsPage />;
  if (coachPage.page === "notities") return <NotesPage />;
  if (coachPage.page === "rapportages") return <ReportsPage />;
  if (coachPage.page === "instellingen") return <SettingsPage />;
  return <Dashboard />;
}

ReactDOM.createRoot(document.getElementById("coach-app")).render(<CoachApp />);
