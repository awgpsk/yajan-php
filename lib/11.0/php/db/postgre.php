<?php

class PostgreDatabase extends DBDriver
{
	
	function __construct($parm,$id)
	{
		parent::__construct();
		global $DB_QUERY_TRACE_FILE,$DB_QUERY_TRACE_DUMP,$SHOW_DB_ERROR;
		$this->messageObject = new Messages("DatabaseAdaptor");
		$this->dbMode=$parm['dbmode'];
		$this->link=null;
		$this->commitMode=true;
		
		$this->start=0;
		$this->timecount=0;
		$this->parm = $parm;
		$sec = new Encryption("shravan");
		$this->parm['password']=$sec->encrypt($this->parm['password']);
		$this->queryCount=0;
		$this->queryErrorStatus=false;
		$this->showError = $SHOW_DB_ERROR;
		$this->dbId = $id;
		if(isset($parm['showError']))
		{
			$this->showError = $parm['showError'];
		}
		
		$str = "host=".$parm["server"]." port=".$parm["port"]." dbname=".$parm["database"]." user=".$parm["username"]." password=".$parm["password"];
		//$this->link = new mysqli($parm['server'], $parm['username'], $parm['password'],$parm['database']);
		$this->link = pg_connect($str);
		if (!$this->link) 
		{
			$this->dbError();
		}

		$this->autoExecute = true;
		if($this->commitMode==true)
		{
			//$this->link->autocommit(TRUE);
		}
		$this->queryTrace = null;
		if($DB_QUERY_TRACE_DUMP && isset($DB_QUERY_TRACE_FILE) && $DB_QUERY_TRACE_FILE!="")
		{
			$this->queryTrace = $DB_QUERY_TRACE_FILE;
		}
	}
	function isAvilable()
	{
		if($this->link)
		{
			return true;
		}
		return false;
	}

	function bindVar($var,$val)
	{
		//$this->scnMgmt->write($this,$var."|".base64_encode($val),"VAR");
	}
	function bindWith($obj)
	{
		
	}
	function getDescriptor($name,$type,$dbType)
	{
		//$obj = new Descriptor($this->scnMgmt);
		//$this->scnMgmt->write($this,$name."|".$type."|".$dbType,"DESC");
		//return $obj;
	}
	function parse($q,$type,$mode='',$unbuf='')
	{
		$this->queryErrorStatus=1;
		$this->q = $q;
		$this->type = $type;
		$this->resource=null;
		if($type=="DQL" && !$this->isReadable())
		{
			return null;
		}
		if($type=="DML" && !$this->isWritable())
		{
			return null;
		}
		if($type=="DDL" && !$this->isEditable())
		{
			return null;
		}

		
		$this->startTimecount();
		$this->resource = pg_query($this->link,$this->q);//$this->link->query($this->q);

		if ($this->resource == false)
		{
			$this->queryErrorStatus=2;
			echo $this->link->error ;
			return null;
			///trigger_error(mysql_error() . n . $q, E_USER_ERROR);
		}
		
		$this->endTimecount();
		$this->queryTrace();
		if(!$this->resource)
		{
			$this->queryErrorStatus;
			return false;
		}
		return $this->resource;
	}
	function execute()
	{
		return $this->resource;
	}
	function safe_query($q='',$type,$debug='',$unbuf='')
	{
		$this->parse($q,$type,$mode,$unbuf);
		return $this->execute();
	}
	function getRow()
	{
		return pg_fetch_row($this->resource);//->fetch_assoc();
	}
	function getDataset(&$data)
	{
		$data = array();
		$i=0;
		$this->startTimecount();
		while ($row = pg_fetch_array($this->resource,null,PGSQL_ASSOC)) 
		{
			$data[count($data)]=array_change_key_case($row,CASE_UPPER);
			$i++;
		}
		$this->endTimecount();
		$this->addQuerycount();
		return $i;
	}
	function getFieldsCount()
	{
		return pg_num_fields($this->resource);//->field_count;
	}
	function getField($i)
	{
		//$total = pg_num_fields($this->resource);
		//$finfo = $this->resource->fetch_fields();
		//$ix=0;
		//foreach ($finfo as $val) 
		//for($i=0;$i<$total;$i++)
		//{
		$name = pg_field_name($this->resource,$i);
		$size = pg_field_size($this->resource,$i);
		$type = pg_field_type($this->resource,$i);	
		return new RecordsetColumns($name,$type,$size);
			
		//}
	}
	function getColumns()
	{
		$t=array();
		//$finfo = $this->resource->fetch_fields();
		$total = pg_num_fields($this->resource);
		//$ix=0;
		//foreach ($finfo as $val) 
		for($i=0;$i<$total;$i++)
		{
			$name = pg_field_name($this->resource,$i);
			$size = pg_field_size($this->resource,$i);
			$type = pg_field_type($this->resource,$i);	
			$t[count($t)] = new RecordsetColumns($name,$type,$size);
		}		
		return $t;
	}
	function getCommitStatus()
	{
		/*
		if ($result = $this->link->query("SELECT @@autocommit")) 
		{
			$row = $result->fetch_row();
			$result->free();
			return $row[0];
		}
		*/
		return -1;
	}
	function autoCommit($val)
	{
		$this->commitMode=$val;
		//return $this->link->autocommit($val);
		//$this->link->autocommit($val);
	}
	function commit()
	{
		//return $this->link->commit();
		//return $this->link->commit();
	}
	function rollback()
	{
		//return $this->link->rollback();
		//return $this->link->rollback();
	}
	function resultFree()
	{
		pg_free_result($this->resource);
	}
	function closeDatabase()
	{
		pg_close($this->link);
	}
	function dbError()
	{
		header('Status: 503 Service Unavailable');
		$this->messageObject->add('error',$this->link->connect_error,$this->q);
		if($this->showError)
		{
			die("Error : ".$this->link->connect_error);
		}
		else
		{
			die("");
		}
	}
}
?>
