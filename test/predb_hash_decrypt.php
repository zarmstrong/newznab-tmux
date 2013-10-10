<?php
require_once(dirname(__FILE__)."/../bin/config.php");
require_once(WWW_DIR."lib/category.php");
require_once(WWW_DIR."lib/groups.php");
require_once(WWW_DIR."lib/nfo.php");
require_once(WWW_DIR."lib/site.php");
require_once ("consoletools.php");
require_once ("namecleaner.php");
require_once ("functions.php");

//this script is adapted from nZEDb decrypt_hashes.php

if (!isset($argv[1]))
	exit ("This script tries to match an MD5 of the releases.name or releases.searchname to preDB.md5.\nphp predb_decrypt_hashes.php true to limit 1000.\nphp predb_decrypt_hashes.php full to run on full database.\n");

echo "\nDecrypt Hashes Started at ".date("g:i:s")."\nMatching preDB MD5 to md5(releases.name or releases.searchname)\n";
preName($argv);

function preName($argv)
{
	$db = new DB();
	$timestart = TIME();
	$limit = ($argv[1] == "full") ? "" : " LIMIT 1000";

	$res = $db->queryDirect("SELECT ID, name, searchname, groupID, categoryID FROM releases WHERE dehashstatus BETWEEN -5 AND 0 AND hashed = true".$limit);
	$total = count($res);
	$counter = 0;
	$show = '';
	if($total > 0)
	{
		$consoletools = new ConsoleTools();
		$category = new Category();
        $functions = new Functions();
		$reset = 0;
		$loops = 1;
		$n = "\n";
		foreach ($res as $row)
		{
			$success = false;
			if (preg_match('/([0-9a-fA-F]{32})/', $row['searchname'], $match) || preg_match('/([0-9a-fA-F]{32})/', $row['name'], $match))
			{
				$pre = $db->queryOneRow(sprintf("SELECT dirname FROM predb WHERE hash = %s", $db->escapeString($match[1])));
				if ($pre !== false)
				{
					$determinedcat = $category->determineCategory($row["groupID"], $pre['dirname']);
					$result = $db->query(sprintf("UPDATE releases SET dehashstatus = 1, relnamestatus = 5, searchname = %s, categoryID = %d WHERE ID = %d", $db->escapeString($pre['dirname']), $determinedcat, $row['ID']));
					if (count($result) > 0)
					{
						$groups = new Groups();
                        $functions = new Functions();
						$groupname = $functions->getByNameByID($row["groupID"]);
						$oldcatname = $functions->getNameByID($row["categoryID"]);
						$newcatname = $functions->getNameByID($determinedcat);

						echo	$n."New name:  ".$pre['dirname'].$n.
							"Old name:  ".$row["searchname"].$n.
							"New cat:   ".$newcatname.$n.
							"Old cat:   ".$oldcatname.$n.
							"Group:     ".$groupname.$n.
							"Method:    "."preDB md5".$n.
							"ReleaseID: ". $row["ID"].$n;

						$success = true;
						$counter++;
					}
				}
			}
			if ($success == false)
			{
				$db->query(sprintf("UPDATE releases SET dehashstatus = dehashstatus - 1 WHERE ID = %d", $row['ID']));
			}
		}
	}
	if ($total > 0)
		echo "\nRenamed ".$counter." releases in ".$consoletools->convertTime(TIME() - $timestart)."\n";
	else
		echo "\nNothing to do.\n";
}