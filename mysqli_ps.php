<?php
	function mysqli_ps_insert($connect,$sql,$fields,$ondup=null)
	{
		$types="";
		if(is_array($fields))
		{
			ksort($fields);
		}
		if(is_array($ondup))
		{
			ksort($ondup);
		}
		foreach($fields as $key=>$value)
		{
			$values[]=$value;
			if(is_numeric($value))
			{
				if(intval($value)==$value)
				{
					$types.="i";
				}
				else
				{
					$types.="d";
				}
			}
			else
			{
				$types.="s";
			}
			$fieldlist.="`" . $key . "`=?,";
		}	
		foreach($ondup as $key=>$value)
		{
			if($fields[$key])
			{
				$duplist.="`".$key . "`=VALUES(`" . $key . "`),";
			}
			else
			{
				$values[]=$value;
				$duplist.="`" . $key . "`=?,";
				if(is_numeric($value))
				{
					if(intval($value)==$value)
					{
						$types.="i";
					}
					else
					{
						$types.="d";
					}
				}
				else
				{
					$types.="s";
				}
			}
		}
		$sql = str_replace("#fields#",substr($fieldlist,0,strlen($fieldlist)-1),$sql);
		$sql = str_replace("#dupes#",substr($duplist,0,strlen($duplist)-1),$sql);
		try
		{
			$stmt = mysqli_prepare($connect, $sql);
			$params = array_merge([$types], $values);
			$refs = [];
			foreach ($params as $k => $v) 
			{
				$refs[$k] = &$params[$k];
			}
			call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $refs));
			$success=mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
			return $success;
		}	catch (Exception $e)
		{
			error_log($e);
			return false;
		}
	}
	function mysqli_ps_update($connect,$sql,$fields)
	{
		$types="";
		ksort($fields);
		preg_match_all('/\|\|(.+)\|\|/Ui',$sql, $wheres);
		$sql=preg_replace('/\|\|.+\|\|/Ui', '?',$sql);
		foreach($fields as $key=>$value)
		{ 
			$values[]=$value;
			if(is_numeric($value))
			{
				if(intval($value)==$value)
				{
					$types.="i";
				}
				else
				{
					$types.="d";
				}
			}
			else
			{
				$types.="s";
			}
			$fieldlist.="`" . $key . "`=?,";
		}	
		foreach($wheres[1] as $key=>$value)
		{
			$values[]=$value;
			if(is_numeric($value))
			{
				if(intval($value)==$value)
				{
					$types.="i";
				}
				else
				{
					$types.="d";
				}
			}
			else
			{
				$types.="s";
			}
		}
		$sql = str_replace("#fields#",substr($fieldlist,0,strlen($fieldlist)-1),$sql);
		try
		{
			$stmt = mysqli_prepare($connect, $sql);
			$params = array_merge([$types], $values);
			$refs = [];
			foreach ($params as $k => $v) 
			{
				$refs[$k] = &$params[$k];
			}
			call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $refs));
			$success=mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
			return $success;
		} catch(Exception $e)
		{
			error_log($e);
			return false;
		}
		
	}
	
	function mysqli_ps_select($connect,$sql)
	{
		$types="";
		preg_match_all('/\|\|(.+)\|\|/Ui',$sql, $wheres);
		$sql=preg_replace('/\|\|.+\|\|/Ui', '?',$sql);
		foreach($wheres[1] as $key=>$value)
		{
			$values[]=$value;
			if(is_numeric($value))
			{
				if(intval($value)==$value)
				{
					$types.="i";
				}
				else
				{
					$types.="d";
				}
			}
			else
			{
				$types.="s";
			}
		}
		try 
		{
			$stmt = mysqli_prepare($connect, $sql);
			$params = array_merge([$types], $values);
			$refs = [];
			foreach ($params as $k => $v) 
			{
				$refs[$k] = &$params[$k];
			}
			call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $refs));
			mysqli_stmt_execute($stmt);
			$result=mysqli_stmt_get_result($stmt);
			mysqli_stmt_close($stmt);
			return $result;
		}
		catch(Exception $e)
		{
			error_log($e);
			return false;
		}
	}
?>
