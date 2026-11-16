<?php [$sl, $sbg, $sfg] = status_info($s['conservation_status']); ?>
<a class="sp-card" href="<?= url('species_view.php?id=' . (int)$s['id']) ?>">
  <div class="sp-img">
    <?php if (!empty($s['image_url'])): ?>
      <img src="<?= e($s['image_url']) ?>" alt="">
    <?php else: ?>
      <span><?= category_emoji($s['category']) ?></span>
    <?php endif; ?>
  </div>
  <div class="sp-body">
    <strong><?= e($s['name']) ?></strong>
    <?php if (!empty($s['scientific_name'])): ?><em><?= e($s['scientific_name']) ?></em><?php endif; ?>
    <span class="badge" style="background:<?= $sbg ?>;color:<?= $sfg ?>"><?= e($sl) ?></span>
    <small><?= e(ucfirst($s['category'])) ?><?= !empty($s['habitat']) ? ' · ' . e($s['habitat']) : '' ?></small>
  </div>
</a>
