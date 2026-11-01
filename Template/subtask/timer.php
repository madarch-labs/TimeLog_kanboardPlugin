<span class="subtask-time-tracking">
    <?php if (! empty($subtask['time_spent'])): ?>
        <?= t('%sh spent', n($subtask['time_spent'])) ?>
    <?php endif ?>

    <?php if (! empty($subtask['time_spent']) && ! empty($subtask['time_estimated'])): ?>/<?php endif ?>

    <?php if (! empty($subtask['time_estimated'])): ?>
        <?= t('%sh estimated', n($subtask['time_estimated'])) ?>
    <?php endif ?>
</span>
