<?php

use CodeIgniter\Pager\PagerRenderer;

/**
 * @var PagerRenderer $pager
 */
if (! isset($pager) || $pager->getPageCount() <= 1) {
    return;
}

$pager->setSurroundCount(2);
$currentPage = $pager->getCurrentPageNumber();
$pageCount = $pager->getPageCount();
?>
<div class="pagination-wrap portal-pagination-wrap mt-4">
    <div class="portal-pagination-meta">
        <span>
            Page <strong><?= number_format($currentPage) ?></strong>
            of <strong><?= number_format($pageCount) ?></strong>
        </span>
    </div>
    <nav aria-label="Pagination">
        <div class="custom-pagination portal-pagination">
            <?php if ($pager->hasPrevious()): ?>
                <a class="prev" href="<?= $pager->getPrevious() ?>" aria-label="Previous page">Prev</a>
            <?php endif; ?>

            <?php foreach ($pager->links() as $link): ?>
                <a
                    href="<?= $link['uri'] ?>"
                    class="<?= $link['active'] ? 'active' : '' ?>"
                    <?= $link['active'] ? 'aria-current="page"' : '' ?>
                >
                    <?= $link['title'] ?>
                </a>
            <?php endforeach; ?>

            <?php if ($pager->hasNext()): ?>
                <a class="next" href="<?= $pager->getNext() ?>" aria-label="Next page">Next</a>
            <?php endif; ?>
        </div>
    </nav>
</div>
