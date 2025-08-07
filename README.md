# MySQL-PHP-PreparedStatements
Three quick and dirty procedural functions to make and execute Prepared Statements for MySQL/MariaDB with PHP

## mysqli_ps_insert();
```
mysqli_ps_insert($mysqliconnection,$sql,$fields,$ondup);
```
### $mysqliconnection (OBJECT)
the mysqli connection object
### $sql (STRING)
the $sql statement with #fields# replacing all parameterised insert fields and #dupes# replacing all parameterised ON DUPLICATE KEY UPDATE fields
### $fields (ARRAY)
associative array of fields e.g. $fields["FIELDNAME"]="VALUE";
### $ondup (ARRAY)
associative array of on duplicate key update fields e.g. $ondup["FIELDNAME"]=""; Note that if the ondup field exists in $fields then its value is ignored and it is assumed the update is the same as the value specified for fields.

returns true or false;

e.g. 
```
$fields["AddedBy"]=5;
$fields["Name"]="Joe Bloggs";
$fields["Address"]="5 My Street, My Town";
$ondup["AmendedBy"]=5;
$ondup["Name"]="" // will use the same value as specified in $fields
$ondup["Address"]=""; // will use the same value as specified in $fields;

$success=mysqli_ps_insert($connect,"insert into `table` set #fields# on duplicate key #dupes#",$fields,$ondup);
```
## mysqli_ps_update();
```
mysqli_ps_update($mysqliconnection,$sql,$fields);
```
### $mysqliconnection (OBJECT)
the mysqli connection object
### $sql (STRING)
the $sql statement with #fields# replacing all parameterised insert fields. In the "WHERE" surround values in double pipe characters e.g. WHERE ID=||5|| 
### $fields (ARRAY)
associative array of fields e.g. $fields["FIELDNAME"]="VALUE";

returns true or false;

e.g. 
```
$fields["AmendedBy"]=10;
$fields["Name"]="Joseph Bloggs";
$fields["Address"]="5 My Street, My Town";

$success=mysqli_ps_update($connect,"update `table` set #fields# where `Name`=||Joe Bloggs||",$fields);
```

## mysqli_ps_select();
```
mysqli_ps_update($mysqliconnection,$sql);
```
### $mysqliconnection (OBJECT)
the mysqli connection object
### $sql (STRING)
the $sql statement. In the "WHERE" surround parameterised values in double pipe characters e.g. WHERE ID=||5|| 

returns mysqli result object.
(requires mysqlnd)

e.g. 
```
$fields["AmendedBy"]=10;
$fields["Name"]="Joseph Bloggs";
$fields["Address"]="5 My Street, My Town";

$$result=mysqli_ps_update($connect,"select `Name`,`Address from `table`  where `ID`=||5||";
`/``
