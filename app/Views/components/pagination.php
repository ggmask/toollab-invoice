<?php if (isset($totalPages) && $totalPages > 1): ?>
  <div class="pagination">
    <div>
      Showing page <strong><?= (int)$page ?></strong> of <strong><?= (int)$totalPages ?></strong>
    </div>
    <div class="pagination-links">
      <?php
      $queryParams = $_GET;
      ?>
      <?php if ($page > 1): ?>
        <?php $queryParams['page'] = $page - 1; ?>
        <a href="?<?= http_build_query($queryParams) ?>" class="page-btn">&laquo; Prev</a>
      <?php endif; ?>

      <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <?php $queryParams['page'] = $i; ?>
        <a href="?<?= http_build_query($queryParams) ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <?php $queryParams['page'] = $page + 1; ?>
        <a href="?<?= http_build_query($queryParams) ?>" class="page-btn">Next &raquo;</a>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>
