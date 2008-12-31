<?php
require_once 'PreparedSQL.php';

$p = new PreparedSQL('
	SELECT *
	FROM xpto
	WHERE
		a = ?
		AND b = :lala
		AND c = :lala
		AND d = :lala
		AND e = ?
');
$p->set('lala', 123);
$p->set(0, 456);
$p->set(2, "'sad lala :('");
$p->set(4, 789);

assert($p == "
	SELECT *
	FROM xpto
	WHERE
		a = 456
		AND b = 123
		AND c = 'sad lala :('
		AND d = 123
		AND e = 789
", 'SQL output');