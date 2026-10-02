<?php
/**
 * Isolated archive display regression checks; no configuration, files or database are loaded.
 * Run: php tests/anchor_price_publication_groups.php
 */
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
	if (!(error_reporting() & $severity)) {
		return false;
	}
	throw new ErrorException($message, 0, $severity, $file, $line);
});

define('DIR_SYSTEM', dirname(__DIR__) . '/upload/system/');
class Model {}
require dirname(__DIR__) . '/upload/catalog/model/extension/module/anchor_price.php';

class AnchorPublicationGroupFixture extends ModelExtensionModuleAnchorPrice {
	public $rows = array();
	public $reads = 0;

	public function getPublications() {
		$this->reads++;
		return $this->rows;
	}
}

$checks = 0;
function check($condition, $message) {
	global $checks;
	$checks++;
	if (!$condition) {
		throw new RuntimeException('FAIL: ' . $message);
	}
}

function publication($id, $published_at, $corrects_id = null, $source_published_at = null) {
	return array(
		'publication_id' => $id,
		'published_at' => $published_at,
		'corrects_publication_id' => $corrects_id,
		'source_published_at' => $source_published_at,
		'filename' => 'publication-' . $id . '.csv',
		'xml_filename' => 'publication-' . $id . '.xml',
		'checksum_sha256' => 'csv-checksum-' . $id,
		'xml_checksum_sha256' => 'xml-checksum-' . $id,
		'product_count' => 6
	);
}

function publicationIds($rows) {
	return array_column($rows, 'publication_id');
}

$model = new AnchorPublicationGroupFixture();
check($model->getPublicationGroups() === array(), 'Empty archive stays empty.');

$originals = array(
	publication(1, '2026-09-30 15:34:44'),
	publication(2, '2026-10-01 07:00:05'),
	publication(3, '2026-10-02 07:00:07')
);
$corrections = array(
	publication(4, '2026-10-02 08:45:00', 1, '2026-09-30 15:34:44'),
	publication(5, '2026-10-02 08:45:00', 2, '2026-10-01 07:00:05'),
	publication(6, '2026-10-02 08:45:00', 3, '2026-10-02 07:00:07')
);
$model->rows = array($corrections[1], $originals[0], $corrections[2], $originals[2], $originals[1], $corrections[0]);
$input = $model->rows;
$groups = $model->getPublicationGroups();
check(count($groups) === 3, 'Three original/correction pairs produce three rows.');
check(publicationIds($groups) === array(6, 5, 4), 'Groups follow original snapshot dates, not correction order.');
check(array_column($groups, 'display_published_at') === array('2026-10-02 07:00:07', '2026-10-01 07:00:05', '2026-09-30 15:34:44'), 'Original publication times are shown unchanged.');
check($model->rows === $input, 'Grouping never changes source rows.');
foreach ($groups as $index => $group) {
	$correction = $corrections[2 - $index];
	$original = $originals[2 - $index];
	check($group['original_publication'] === $original, 'Original download metadata is retained exactly.');
	unset($group['display_published_at'], $group['original_publication']);
	check($group === $correction, 'Main corrected publication ID, timestamps and download metadata are retained exactly.');
}

// Input ordering has no influence on the selected version or chronological order.
$model->rows = array_reverse($input);
check($model->getPublicationGroups() === $groups, 'Reversed input produces identical grouped rows.');

// The original visible publication is authoritative even if redundant source metadata differs.
$model->rows = array(publication(8, '2026-10-02 08:55:00', 2, '2026-09-28 10:00:00'), $originals[1]);
$group = $model->getPublicationGroups()[0];
check($group['display_published_at'] === $originals[1]['published_at'], 'Visible original date wins over conflicting correction metadata.');
check($group['published_at'] === '2026-10-02 08:55:00', 'Actual correction timestamp is not overwritten.');

// Separate publications on the same day remain separate snapshots, even at identical timestamps.
$model->rows = array(
	publication(11, '2026-10-01 12:00:00'),
	publication(10, '2026-10-01 12:00:00'),
	publication(9, '2026-10-01 07:00:00'),
	publication(30, '2026-10-02 09:00:00', 10, '2026-10-01 12:00:00')
);
$groups = $model->getPublicationGroups();
check(count($groups) === 3, 'Same-day publications are not merged by calendar date.');
check(publicationIds($groups) === array(11, 30, 9), 'Timestamp ties use original ID, not the newer correction ID.');
check($groups[0]['original_publication'] === false && $groups[2]['original_publication'] === false, 'Uncorrected snapshots do not duplicate their own download links.');
check($groups[1]['original_publication']['publication_id'] === 10, 'Corrected same-day snapshot links to its own original.');

// Expired or otherwise unavailable originals are absent from the public source list.
$model->rows = array(
	publication(100, '2026-10-02 08:45:00', 1, '2026-09-30 15:34:44'),
	publication(2, '2026-10-01 07:00:05')
);
$groups = $model->getPublicationGroups();
check(publicationIds($groups) === array(2, 100), 'Orphan correction is ordered by its recorded original date.');
check($groups[1]['original_publication'] === false, 'Orphan correction never fabricates an original link.');
check($groups[1]['display_published_at'] === '2026-09-30 15:34:44', 'Missing original falls back to source publication time.');

$model->rows = array(publication(101, '2026-10-02 08:46:00', 99));
$group = $model->getPublicationGroups()[0];
check($group['display_published_at'] === '2026-10-02 08:46:00', 'Correction without source timestamp safely uses its actual timestamp.');
check($group['original_publication'] === false, 'Missing original and timestamp do not fabricate history.');

// Legacy rows lacking correction columns remain compatible.
$legacy = publication(7, '2026-10-01 18:00:00');
unset($legacy['corrects_publication_id'], $legacy['source_published_at']);
$model->rows = array($legacy);
$group = $model->getPublicationGroups()[0];
check($group['display_published_at'] === $legacy['published_at'] && $group['original_publication'] === false, 'Legacy uncorrected publication is displayed normally.');

// Unique constraints normally prevent this; selection must still be deterministic.
$model->rows = array(
	publication(50, '2026-10-02 09:00:00', 1, '2026-09-30 15:34:44'),
	publication(52, '2026-10-02 08:59:00', 1, '2026-09-30 15:34:44'),
	$originals[0],
	publication(51, '2026-10-02 09:00:00', 1, '2026-09-30 15:34:44')
);
$groups = $model->getPublicationGroups();
check(count($groups) === 1 && $groups[0]['publication_id'] === 51, 'Latest correction timestamp wins; matching timestamps use highest correction ID.');
$model->rows = array_reverse($model->rows);
check($model->getPublicationGroups() === $groups, 'Multiple-correction choice is input-order independent.');
check($model->reads === 10, 'Each grouping call reads the existing public archive exactly once.');

echo 'PASS: ' . $checks . " publication grouping checks.\n";
