<div id="coach-app">
  <p class="coach-loading">De docentpagina wordt geladen...</p>
</div>
<script>
  window.coachPage = <?= json_encode(
      $coachPageData,
      JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
  ) ?>;
</script>
<script type="text/babel" src="docent-app.jsx"></script>
