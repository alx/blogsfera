<?php
require_once(FS_ABS_PATH.'/php/utils.php');

	/**********************************************************************
	*  Author: Justin Vincent (justin@visunet.ie)
	*  Web...: http://php.justinvincent.com
	*  Name..: ezSQL_mysql
	*  Desc..: mySQL component (part of ezSQL databse abstraction library)
	*
	*/


	/**********************************************************************
	*  ezSQL Database specific class - mySQL
	*/

	if ( ! function_exists ('mysql_connect') ) die('<b>Fatal Error:</b> ezSQL_mysql requires mySQL Lib to be compiled and or linked in to the PHP engine');
	if ( ! class_exists ('fs_ezSQLcore') ) die('<b>Fatal Error:</b> ezSQL_mysql requires ezSQLcore (ez_sql_core.php) to be included/loaded before it can be used');

	class fs_ezSQL_mysql extends fs_ezSQLcore
	{
		var $ezsql_mysql_str;
		var $dbuser = false;
		var $dbpassword = false;
		var $dbname = false;
		var $dbhost = false;

		/**********************************************************************
		*  Constructor - allow the user to perform a qucik connect at the
		*  same time as initialising the ezSQL_mysql class
		*/

		function fs_ezSQL_mysql($dbuser='', $dbpassword='', $dbname='', $dbhost='localhost')
		{
			$this->dbuser = $dbuser;
			$this->dbpassword = $dbpassword;
			$this->dbname = $dbname;
			$this->dbhost = $dbhost;
			$this->ezsql_mysql_str = array
			(
				 1 => fs_r('User name and password are required to connect to database'),
				 2 => fs_r('Error establishing mySQL database connection'),
				 3 => fs_r('Database name is required to connect to database'),
				 4 => fs_r('mySQL database connection is not active'),
				 5 => fs_r('Unexpected error while querying database')
			);

		}

		function ensureConnected()
		{
			if (!$this->is_connected())
			{
				$this->connect();
			}
		}

		function connect()
		{
			$ret = $this->connectImpl($this->dbuser, $this->dbpassword, $this->dbhost);
			if ($ret)
			{
				$this->select($this->dbname);
			}
			
			return $ret;
		}

		function is_connected()
		{
			return isset($this->dbh) && $this->dbh;
		}


		function connectImpl($dbuser='', $dbpassword='', $dbhost='localhost', $add_debug_to_error = true)
		{
			$ezsql_mysql_str = $this->ezsql_mysql_str; 
			$return_val = false;
			// Must have a user and a password
			if ( ! $dbuser )
			{
				$error = $ezsql_mysql_str[1];
				$error .= ($add_debug_to_error ? (' in '.__FILE__.' on line '.__LINE__) : '');
				$this->register_error($error) ;
				$this->show_errors ? trigger_error($ezsql_mysql_str[1],E_USER_WARNING) : null;
			}
			else 
			{
				// Try to establish the server database handle
				ob_start(); // capture sql error
				$this->dbh = mysql_connect($dbhost,$dbuser,$dbpassword,true);
				$output = ob_get_clean();
				if ($this->dbh === false)
				{
					$mysql_error = mysql_error();
					$error = $ezsql_mysql_str[2].":".($mysql_error != '' ? $mysql_error : $output);
					$error .= ($add_debug_to_error ? (' in '.__FILE__.' on line '.__LINE__) : '');
					$this->register_error($error);
					$this->show_errors ? trigger_error($error,E_USER_WARNING) : null;
				}
				else
				{
					$this->dbuser = $dbuser;
					$this->dbpassword = $dbpassword;
					$this->dbhost = $dbhost;
					$return_val = true;
				}
			}
			return $return_val;
		}

		function disconnect()
		{
			if ($this->is_connected())
			{
				ob_start(); // capture sql error
				$res = mysql_close($this->dbh);
				unset($this->dbh);
				$output = ob_get_clean();
				if (!$res)
				{
					$this->register_error(sprintf(fs_r("Can't disconnect: %s"),$output));
					$this->show_errors ? trigger_error($error,E_USER_WARNING) : null;
					return false;
				}
				else
				{
					return true;
				}
			}
			else
			{
				$this->register_error(sprintf(fs_r("Can't disconnect: %s"),fs_r('Not connected')));
				$this->show_errors ? trigger_error($error,E_USER_WARNING) : null;
				return false;
			}
		}

		/**********************************************************************
		*  Try to select a mySQL database
		*/

		function select($dbname='')
		{
			$ezsql_mysql_str = $this->ezsql_mysql_str; 
			$return_val = false;

			// Must have a database name
			if ( ! $dbname )
			{
				$this->register_error($ezsql_mysql_str[3].' in '.__FILE__.' on line '.__LINE__);
				$this->show_errors ? trigger_error($ezsql_mysql_str[3],E_USER_WARNING) : null;
			}

			// Must have an active database connection
			else if ( ! $this->dbh )
			{
				$this->register_error($ezsql_mysql_str[4].' in '.__FILE__.' on line '.__LINE__);
				$this->show_errors ? trigger_error($ezsql_mysql_str[4],E_USER_WARNING) : null;
			}

			// Try to connect to the database
			else if ( !@mysql_query("USE `$dbname`",$this->dbh) )
			{
				// Try to get error supplied by mysql if not use our own
				if ( !$str = @mysql_error($this->dbh))
					  $str = $ezsql_mysql_str[5];

				$this->register_error($str.' in '.__FILE__.' on line '.__LINE__);
				$this->show_errors ? trigger_error($str,E_USER_WARNING) : null;
			}
			else
			{
				$this->dbname = $dbname;
				$return_val = true;
			}

			return $return_val;
		}

		/**********************************************************************
		*  Format a mySQL string correctly for safe mySQL insert
		*  (no mater if magic quotes are on or not)
		*/

		function escape($value)
		{
			/*
			// If there is no existing database connection just do a dummy escape.
			if ( ! isset($this->dbh) || ! $this->dbh )
			{
				return "'$value'";
			}
			*/
	
			// Stripslashes
			if (get_magic_quotes_gpc())
			{
				$value = stripslashes($value);
			}

			// Quote if not a number or a numeric string
			if (!is_numeric($value)) 
			{
				if(version_compare(phpversion(),"4.3.0")=="-1") 
				{
					$value = mysql_escape_string($value);
				}
				else
				{
					$value = "'" . mysql_real_escape_string($value, $this->dbh) . "'";
				}
			}

			return $value;
		}

		/**********************************************************************
		*  Return mySQL specific system date syntax
		*  i.e. Oracle: SYSDATE Mysql: NOW()
		*/

		function sysdate()
		{
			return 'NOW()';
		}

		/**********************************************************************
		*  Perform mySQL query and try to detirmin result value
		*/

		function query($query)
		{
			// Initialise return
			$return_val = 0;

			// Flush cached values..
			$this->flush();

			// For reg expressions
			$query = trim($query);

			// Log how the function was called
			$this->func_call = "\$db->query(\"$query\")";

			// Keep track of the last query for debug..
			$this->last_query = $query;

			// Count how many queries there have been
			$this->num_queries++;

			// Use core file cache function
			if ( $cache = $this->get_cache($query) )
			{
				return $cache;
			}

			// If there is no existing database connection then try to connect

			$this->ensureConnected();

			// Perform the query via std mysql_query function..
			$this->result = @mysql_query($query,$this->dbh);

			// If there is an error then take note of it..
			if ( $str = @mysql_error($this->dbh) )
			{
				$is_insert = true;
				$this->register_error($str);
				$this->show_errors ? trigger_error($str,E_USER_WARNING) : null;
				return false;
			}

			// Query was an insert, delete, update, replace
			$is_insert = false;
			if ( preg_match("/^(insert|delete|update|replace|load|start)\s+/i",$query)||strtolower($query) == 'commit')
			{
				$this->rows_affected = @mysql_affected_rows();

				// Take note of the insert_id
				if ( preg_match("/^(insert|replace)\s+/i",$query) )
				{
					$this->insert_id = @mysql_insert_id($this->dbh);
				}

				// Return number fo rows affected
				$return_val = $this->rows_affected;
			}
			// Query was a select
			else
			{	
				// Take note of column info
				$i=0;
				while ($i < @mysql_num_fields($this->result))
				{
					$this->col_info[$i] = @mysql_fetch_field($this->result);
					$i++;
				}

				// Store Query Results
				$num_rows=0;
				while ( $row = @mysql_fetch_object($this->result) )
				{
					// Store relults as an objects within main array
					$this->last_result[$num_rows] = $row;
					$num_rows++;
				}

				@mysql_free_result($this->result);

				// Log number of rows the query returned
				$this->num_rows = $num_rows;

				// Return number of rows selected
				$return_val = $this->num_rows;
			}

			// disk caching of queries
			$this->store_cache($query,$is_insert);

			// If debug ALL queries
			$this->trace || $this->debug_all ? $this->debug() : null ;

			return $return_val;

		}

	}

?>
