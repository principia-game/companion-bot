<?php
$sql = new PDO("sqlite:data.db", null, null, [
	PDO::ATTR_ERRMODE				=> PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_DEFAULT_FETCH_MODE	=> PDO::FETCH_ASSOC,
	PDO::ATTR_EMULATE_PREPARES		=> false,
]);

function query($query,$params = []) {
	global $sql;

	$res = $sql->prepare($query);
	$res->execute($params);
	return $res;
}

function fetch($query,$params = []) {
	$res = query($query,$params);
	return $res->fetch();
}

function result($query,$params = []) {
	$res = query($query,$params);
	return $res->fetchColumn();
}

/**
 * Helper function to insert a row into a table.
 */
function insertInto($table, $data) {
	$fields = [];
	$placeholders = [];
	$values = [];

	foreach ($data as $field => $value) {
		$fields[] = $field;
		$placeholders[] = '?';
		$values[] = $value;
	}

	$query = sprintf(
		"INSERT INTO %s (%s) VALUES (%s)",
	$table, implode(',', $fields), implode(',', $placeholders));

	return query($query, $values);
}

function paginate($page, $pp) {
	$page = (is_numeric($page) && $page > 0 ? $page : 1);

	return sprintf(" LIMIT %s, %s", (($page - 1) * $pp), $pp);
}

query(<<<SQL
CREATE TABLE IF NOT EXISTS "levels" (
	"id" TEXT PRIMARY KEY NOT NULL UNIQUE,
	"xp" INTEGER NOT NULL DEFAULT 0
);
SQL);
