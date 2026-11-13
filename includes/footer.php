</main>
<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <h4>Climate Tracker</h4>
      <p>Making climate information accessible, interactive and easier to understand.</p>
    </div>
    <div>
      <h4>Explore</h4>
      <a href="<?= url('news.php') ?>">News</a>
      <a href="<?= url('tracker.php') ?>">Globe</a>
      <a href="<?= url('species.php') ?>">Species</a>
    </div>
    <div>
      <h4>Data sources</h4>
      <a href="https://climate.nasa.gov" target="_blank" rel="noopener">NASA Climate</a>
      <a href="https://climateknowledgeportal.worldbank.org" target="_blank" rel="noopener">World Bank CCKP</a>
      <a href="https://ourworldindata.org" target="_blank" rel="noopener">Our World in Data</a>
      <a href="https://www.iucnredlist.org" target="_blank" rel="noopener">IUCN Red List</a>
    </div>
  </div>
  <p class="copy">&copy; <?= date('Y') ?> Climate Tracker</p>
</footer>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
