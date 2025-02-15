--TEST--
Extending PDO Test #4
--DESCRIPTION--
Verification of capabilities for extending PDO.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php
if (!extension_loaded("pdo_sqlsrv")) {
    die("skip Extension not loaded");
}
if (PHP_VERSION_ID < 80000) {
    die("skip Test designed for PHP 8.*");
}
?>
--FILE--
<?php
include 'MsCommon.inc';

function Extend()
{
    include 'MsSetup.inc';

    $testName = "PDO - Extension";
    StartTest($testName);

    $data = array(  array('10', 'Abc', 'zxy'),
            array('20', 'Def', 'wvu'),
            array('30', 'Ghi', 'tsr'));

    $conn1 = PDOConnect('ExPDO', $server, $uid, $pwd, true);
    var_dump(get_class($conn1));

    // Prepare test table
    DropTable($conn1, $tableName);
    $conn1->exec("CREATE TABLE [$tableName] (id int NOT NULL PRIMARY KEY, val VARCHAR(10), val2 VARCHAR(16))");
    $stmt1 = $conn1->prepare("INSERT INTO [$tableName] VALUES(?, ?, ?)");
    var_dump(get_class($stmt1));
    foreach ($data as $row)
    {
        $stmt1->execute($row);
    }
    unset($stmt1);

    echo "===QUERY===\n";

    // Retrieve test data via a direct query
    var_dump($conn1->getAttribute(PDO::ATTR_STATEMENT_CLASS));
    $conn1->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('ExPDOStatement', array($conn1)));
    var_dump($conn1->getAttribute(PDO::ATTR_STATEMENT_CLASS));
    $stmt1 = $conn1->query("SELECT * FROM [$tableName]");
    var_dump(get_class($stmt1));
    var_dump(get_class($stmt1->dbh));

    echo "===FOREACH===\n";

    foreach($stmt1 as $obj)
    {
        var_dump($obj);
    }

    echo "===DONE===\n";

    // Cleanup
    DropTable($conn1, $tableName);
    $stmt1 = null;
    $conn1 = null;

    EndTest($testName);
}

class ExPDO extends PDO
{
    function __destruct()
    {
        echo __METHOD__ . "()\n";
    }

    function query(string $sql, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        echo __METHOD__ . "()\n";
        $stmt = parent::query($sql);
        return ($stmt);
    }
}

class ExPDOStatement extends PDOStatement
{
    public $dbh;

    protected function __construct($dbh)
    {
        $this->dbh = $dbh;
        $this->setFetchMode(PDO::FETCH_ASSOC);
        echo __METHOD__ . "()\n";
    }

    function __destruct()
    {
        echo __METHOD__ . "()\n";
    }

    function execute(?array $params = null) : bool
    {
        echo __METHOD__ . "()\n";
        return parent::execute();
    }
}


//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{

    try
    {
        Extend();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECTF--
string(5) "ExPDO"
string(12) "PDOStatement"
===QUERY===
array(1) {
  [0]=>
  string(12) "PDOStatement"
}
array(2) {
  [0]=>
  string(14) "ExPDOStatement"
  [1]=>
  array(1) {
    [0]=>
    object(ExPDO)#%d (0) {
    }
  }
}
ExPDO::query()
ExPDOStatement::__construct()
string(14) "ExPDOStatement"
string(5) "ExPDO"
===FOREACH===
array(3) {
  ["id"]=>
  string(2) "10"
  ["val"]=>
  string(3) "Abc"
  ["val2"]=>
  string(3) "zxy"
}
array(3) {
  ["id"]=>
  string(2) "20"
  ["val"]=>
  string(3) "Def"
  ["val2"]=>
  string(3) "wvu"
}
array(3) {
  ["id"]=>
  string(2) "30"
  ["val"]=>
  string(3) "Ghi"
  ["val2"]=>
  string(3) "tsr"
}
===DONE===
ExPDOStatement::__destruct()
Test "PDO - Extension" completed successfully.
ExPDO::__destruct()