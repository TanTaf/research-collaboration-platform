<?php  ?>

<?php if (($layout ?? 'app') === 'app'): ?>
  </main>
</div>
<?php else: ?>
</main>
<?php endif; ?>

<footer class="site-footer">
  <div class="wrap">
    <div class="footer-grid">
      <div>
        <div class="footer-brand">
          <img src="<?= url('assets/img/logo.jpg') ?>" alt="Logo">
          <span class="n"><?= e(SITE_NAME) ?></span>
        </div>
        <p class="muted-sm" style="max-width:280px"><?= e(SITE_TAGLINE) ?>. A research collaboration platform built for
          United International University students, faculty and research groups.</p>
      </div>
      <div>
        <h5>Resources</h5>
        <ul>
          <li><a href="<?= url('repository.php') ?>">Open Repository</a></li>
          <li><a href="<?= url('opportunities.php') ?>">Opportunities</a></li>
          <li><a href="<?= url('events.php') ?>">Events &amp; Seminars</a></li>
        </ul>
      </div>
      <div>
        <h5>Community</h5>
        <ul>
          <li><a href="<?= url('communities.php') ?>">Research Communities</a></li>
          <li><a href="<?= url('matchmaking.php') ?>">Smart Matchmaking</a></li>
          <li><a href="<?= url('directory.php') ?>">Researcher Directory</a></li>
        </ul>
      </div>
      <div>
        <h5>Policies</h5>
        <ul>
          <li><a href="<?= url('page.php?p=ethics') ?>">Ethical Guidelines</a></li>
          <li><a href="<?= url('page.php?p=peer-review') ?>">Peer Review Policy</a></li>
          <li><a href="<?= url('page.php?p=support') ?>">Support</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved.</span>
      <span>Built with PHP &amp; MySQL &middot; United International University</span>
    </div>
  </div>
</footer>

<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
