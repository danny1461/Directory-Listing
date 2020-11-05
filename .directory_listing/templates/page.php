<?php
$folderInfo = new FolderInfo();
if (!$folderInfo->isValid()) {
	return include __DIR__ . '/404.php';
}

$scriptPath = dirname(substr($_SERVER['SCRIPT_FILENAME'], strlen($_SERVER['DOCUMENT_ROOT'])));
$scriptPath = '//' . $_SERVER['SERVER_NAME'] . rtrim(str_replace('\\', '/', $scriptPath), '/') . '/' . basename($_SERVER['SCRIPT_FILENAME']);
$pathPrefix = rtrim($_SERVER['REQUEST_URI'], '/') . '/';

include __DIR__ . '/header.php';
$i = 0;
?>
<script type="text/javascript">
	var pathPrefix = <?= json_encode($pathPrefix) ?>;
</script>

<header class="py-3">
	<div class="container">
		<p class="h4">Index of <em><?= $folderInfo->relativePath ?></em></p>
		<div class="search-container p-3">
			<div class="input-group">
				<input type="text" class="form-control" id="search-text" placeholder="Search for...">
				<button class="show-hidden-btn btn" type="button" id="toggle-hidden"><i class="fa fa-eye-slash"></i></button>
			</div>
		</div>
	</div>
</header>

<div class="container">
	<div class="pills position-relative">
		<?php if ($_SERVER['REQUEST_URI'] != '/') : ?>
			<div id="pill-return" class="pill" style="position: relative">
				<a href="<?= dirname($folderInfo->relativePath) ?>" class="name-entry text-truncate">
					<span class="text">Return to <?= dirname($folderInfo->relativePath) ?></span>
				</a>
			</div>
		<?php endif; ?>
		<?php foreach ($folderInfo->getSubDirs() as $info) : ?>
			<div id="pill-<?= $i++ ?>" class="pill is-dir<?= $info['hidden'] ? ' fs-hidden' : '' ?>" style="position: relative" data-name="<?= htmlentities($info['name']) ?>" data-clean="<?= htmlentities($info['clean']) ?>" data-tags="<?= implode(',', $info['tags']) ?>">
				<div class="row">
					<a href="<?= htmlentities($info['path']) ?>" class="col-8 col-sm-6 name-entry text-truncate position-relative">
						<?php if (isset($info['icon'])) : ?>
							<img src="<?= $info['path'] ?>/favicon.ico" />
						<?php endif; ?>
						<span class="text ml-4">/<?= $info['name'] ?></span>
					</a>
					<div class="col-4 text-truncate"><?= implode(', ', $info['tags']) ?></div>
					<div class="col-2 text-truncate"><span class="size"><?= htmlentities($info['info']) ?></span></div>
				</div>
				<a class="btn btn-dl" href="<?= $scriptPath ?>?download=<?= $pathPrefix . $info['name'] ?>"><i class="fa fa-download"></i></a>
			</div>
		<?php endforeach; ?>
		<?php foreach ($folderInfo->getFiles() as $info) : ?>
			<div id="pill-<?= $i++ ?>" class="pill<?= $info['hidden'] ? ' fs-hidden' : '' ?>" style="position: relative" data-name="<?= htmlentities($info['name']) ?>" data-clean="<?= htmlentities($info['clean']) ?>" data-tags="<?= implode(',', $info['tags']) ?>">
				<div class="row">
					<a href="<?= htmlentities($info['path']) ?>" class="col-xs-8 col-sm-6 name-entry text-truncate">
						<?php if (isset($info['icon'])) : ?>
							<img src="<?= $info['path'] ?>/favicon.ico" />
						<?php endif; ?>
						<span class="text"><?= $info['name'] ?></span>
					</a>
					<div class="col-xs-4 text-truncate"><?= implode(', ', $info['tags']) ?></div>
					<div class="col-sm-2 text-truncate d-none d-md-block"><span class="size"><?= htmlentities($info['info']) ?></span></div>
				</div>
				<a class="btn btn-dl" href="<?= $scriptPath ?>?download=<?= $pathPrefix . $info['name'] ?>"><i class="fa fa-download"></i></a>
			</div>
		<?php endforeach; ?>
	</div>
</div>

<?php include __DIR__ . '/footer.php'; ?>