<?php

$bf = $_GET['bf'];
$key = $_GET['key'];

if (empty($bf) || empty($key))
{
	die('Bibtex entry not found. Exit request.');
}

$bh = Loader::helper('itm_bibtex', 'itm_bibtex');
Loader::library('bibtexbrowser', 'itm_bibtex');
$bibDb = $bh->getBibDb($bf);
$result = $bibDb->multisearch(array('key' => $key));

if (count($result) != 1)
{
	die('Bibtex entry not found. Exit request.');
}

echo '<pre style="font-family: \'Courier New\', Courier, monospace;">' . $bh->renderBibEntryRaw(array_shift($result)) . '</pre>';

?>