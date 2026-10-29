<?php if (!empty($flashSuccess)): ?>
  <div class="alert alert-success">
    <span><?= $flashSuccess ?></span>
    <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; cursor: pointer; color: inherit; font-size: 16px;">&times;</button>
  </div>
<?php endif; ?>

<?php if (!empty($flashError)): ?>
  <div class="alert alert-error">
    <span><?= $flashError ?></span>
    <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; cursor: pointer; color: inherit; font-size: 16px;">&times;</button>
  </div>
<?php endif; ?>
