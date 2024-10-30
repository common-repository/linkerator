<?php
/*
  Plugin Name: Linkerator
  Plugin URI: http://www.wowhead-tooltips.com/tools/wordpress/linkerator/
  Description: Scans the content for words you provided and then will convert the text into a link.
  Version: 0.1
  Author: Adam Koch
  Author URI: http://crackpot.ws
*/

/*  Copyright 2009  Adam Koch  (email : admin@crackpot.ws)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// make sure they're not trying anything iffy
if (!defined('ABSPATH'))
	die('Not a chance.');
	
/**
 * Filter the entries
 **/
function linkerator_filter($content)
{
	global $wpdb;
	$table = get_option('linkerator_table');
	
	$sql = "SELECT trig, url, class FROM $table";
	$entries = $wpdb->get_results($sql);
	
	if (sizeof($entries) == 0)
	{
		// no entries
		return $content;
	}
	else
	{
		// we'll use preg_replace for our filtering
		$pattern = $replace = array();
		
		foreach ($entries as $entry)
		{
			// add to the pattern array
			$href = (linkerator_email_url((string)$entry->url) == 'email') ? 'mailto:' . (string)$entry->url : (string)$entry->url;
			$pattern[] = '/' . $entry->trig . '/';
			if (trim((string)$entry->class) == '')
				$replace[] = '<a href="' . $href . '">' . stripslashes((string)$entry->trig) . '</a>';
			else
				$replace[] = '<a href="' . $href . '" class="' . $entry->class . '">' . stripslashes((string)$entry->trig) . '</a>';
		}
		
		$content = preg_replace($pattern, $replace, $content);
		
		return $content;
	}
}

/**
 * Ran when plugin is activated
 **/
function linkerator_install()
{
	global $wpdb, $linkerator_version;
	$table = $wpdb->prefix . 'linkerator';
	if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table)
	{
		// create the sql table
		$sql = "CREATE TABLE $table (
					id INT(8) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					trig TEXT NOT NULL,
					url TEXT NOT NULL,
					class TEXT NULL
				)";
		require_once(ABSPATH . '/wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
	add_option('linkerator_table', $table);
	add_option('linkerator_path', dirname(__FILE__) . '/');
}

/**
 * Ran when the plugin is deactivated
 **/
function linkerator_uninstall()
{
	global $wpdb;
	$table = get_option('linkerator_table');
	$sql = "DROP TABLE IF EXISTS $table";
	$wpdb->query($sql);
	delete_option('linkerator_table');
	if (get_option('linkerator_url'))
		delete_option('linkerator_url');
	delete_option('linkerator_path');
}

/**
 * Adds the admin menu to Options
 **/
function linkerator_admin()
{
	add_options_page('Linkerator Options', 'Linkerator', 8, __FILE__, 'linkerator_menu');
}

/**
 * The admin menu
 **/
function linkerator_menu()
{
	$plugin_url = (!get_option('linkerator_url')) ? $_SERVER['REQUEST_URI'] : get_option('linkerator_url');
	?>
	<script type="text/javascript">
		<!--
		function checkAllImport(truefalse)
		{
			for (i = 0, n = document.linkerator_import.elements.length; i < n; i++) {
				if(document.linkerator_import.elements[i].type == "checkbox") {
					document.linkerator_import.elements[i].checked = truefalse;
				}
			}
		}
		
		function validateForm(oform)
		{
			var url_regex = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
			var email_regex = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
			if (oform.link_trig.value == '')
				alert('You must enter a trigger.');
			else if (oform.link_url.value == '')
				alert('You must enter a URL or e-mail.');
			else if (!email_regex.test(oform.link_url.value) && !url_regex.test(oform.link_url.value))
				alert('You must enter a valid URL or e-mail.');
			else
				oform.submit();
		}
		
		function checkAll(truefalse)
		{
			for (i = 0, n = document.linkerator_manage.elements.length; i < n; i++) {
				if(document.linkerator_manage.elements[i].type == "checkbox") {
					document.linkerator_manage.elements[i].checked = truefalse;
				}
			}
		}
		
		function confirm_import()
		{
			var checked = false;
			for (i = 0, n = document.linkerator_import.elements.length; i < n; i++)
			{
				if (document.linkerator_import.elements[i].checked == true)
				{
					checked = true;
				}
			}
			
			if (!checked)
				alert('You must select at least one trigger to import.');
			else
				document.linkerator_import.submit();
		}
		
		function confirmMassDelete()
		{
			var checked = false;
			for (i = 0, n = document.linkerator_manage.elements.length; i < n; i++)
			{
				if (document.linkerator_manage.elements[i].checked == true)
				{
					checked = true;
				}
			}
			
			if (checked == false)
			{
				alert('You must select at least one to delete.');
			}
			else
			{
				var answer = confirm('Are you sure you want to remove these entries?');
				
				if (answer)
					document.linkerator_manage.submit();
				else
					return false;
			}
		}
		
		function confirmDelete(url)
		{
			var answer = confirm('Are you sure you want to delete this entry?');
			
			if (answer)
				location.href = url;
			else
				return false;
		}
		
		function confirmEmpty(url)
		{
			var answer = confirm('Are you sure you want to remove all entries?');
			
			if (answer)
				location.href = url;
			else
				return false;
		}			
		// -->
	</script>
	<div class="wrap">
	<?php
	
	switch ($_REQUEST['action'])
	{
		case 'edit':
		case 'add':
			linkerator_add($plugin_url, $_REQUEST['id']);
			break;
			
		case 'import':
			linkerator_import($plugin_url);
			break;
		
		default:
			linkerator_main($plugin_url);
			break;
	}
	
	?></div><?php
}

/**
 * Import triggers.txt
 **/
function linkerator_import($plugin_url)
{
	global $wpdb;
	$path = get_option('linkerator_path');
	$table = get_option('linkerator_table');
	
	// make sure the trailing slash is there
	if (substr($path, strlen($path) - 1, 1) != '/')
		$path .= '/';
	
	?>
	<h2>Import Entries</h2>
	<p>Import entries from triggers.txt.  Duplicate triggers will not be shown.</p>
	<?php
	
	if (!file_exists($path . 'triggers.txt'))
	{
		?><div id="message" class="error"><p>The triggers file <tt>triggers.txt</tt> was not found.</p></div><?php
	}
	else
	{
		if (!is_writable($path . 'triggers.txt'))
		{
			?><div id="message" class="error"><p>The triggers file <tt>triggers.txt</tt> is not writable.  Please change permissions to 0777 or 0775.</p></div><?php
		}
		else
		{
			// read the triggers into an array
			$entries = @file($path . 'triggers.txt');

			?>
			<form name="linkerator_import" action="<?= $plugin_url ?>" method="post">
				<input type="hidden" name="action" value="do_import" />
				<div style="margin-bottom: 1.3em;"><input type="button" name="do_import" class="button-primary" value="Import Selected" onclick="confirm_import();" /></div>
				<table class="widefat">
					<thead>
						<tr>
							<th scope="col" class="manage-column check-column"><input type="checkbox" onclick="checkAllImport(this.checked);" /></th>
							<th scope="col" class="manage-column"><?php _e('Trigger'); ?></th>
							<th scope="col" class="manage-column"><?php _e('URL'); ?></th>
							<th scope="col" class="manage-column"><?php _e('Class'); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th scope="col" class="manage-column check-column"><input type="checkbox" onclick="checkAllImport(this.checked);" /></th>
							<th scope="col" class="manage-column"><?php _e('Trigger'); ?></th>
							<th scope="col" class="manage-column"><?php _e('URL'); ?></th>
							<th scope="col" class="manage-column"><?php _e('Class'); ?></th>
						</tr>
					</tfoot>
					<tbody>
						<?php
						if (sizeof($entries) == 0)
						{
							// triggers.txt was empty
							?><tr><td scope="row" colspan="4"><em>The file <tt>triggers.txt</tt> was empty.</em></td></tr><?php
						}
						else
						{
							foreach ($entries as $entry)
							{
								$match = array(chr(10), chr(13));
								$entry = str_replace($match, '', $entry);
								$parts = explode('|', $entry);	// split by a pipe
								if ($parts[0] != '' && $parts[1] != '' && 
									$wpdb->query("SELECT 1 FROM $table WHERE trig='" . addslashes($parts[0]) . "'") == 0)
								{
									$class = ($parts[2] == '') ? 'Not Given' : $parts[2];
									$href = (linkerator_email_url($parts[1]) == 'email') ? 'mailto:' . $parts[1] : $parts[1];
									?>
									<tr>
										<td scope="row" class="checked-column"><input type="checkbox" name="checked[]" value="<?= $entry ?>" /></td>
										<td scope="row"><?php echo $parts[0]; ?></td>
										<td scope="row"><a href="<?= $href ?>"><?php echo $parts[1]; ?></a></td>
										<td scope="row"><?= $class ?></td>
									</tr>
									<?php
								}
							}
						}
						?>
					</tbody>
				</table>
				<p class="submit">
					<input type="button" name="do_import" class="button-primary" value="Import Selected" onclick="confirm_import();" />
				</p>		
			</form>
			<?php
		}
	}
}

/**
 * Add/Edit an entry
 **/
function linkerator_add($plugin_url, $id = -1)
{
	global $wpdb;
	$table = get_option('linkerator_table');
	if ($id > -1)
	{
		// they want to edit an entry
		$sql = "SELECT trig, url, class FROM $table WHERE id=$id";
		$row = $wpdb->get_row($sql);
		
		if (sizeof($row) == 0)
		{
			?><div id="message" class="error"><p>Entry number <?= $id ?> not found.</p></div><?php
		}
	}
	
	?>
	<h2>Add/Edit Entry</h2>
	<p>Use this form to add/edit an entry.</p>
	<form name="linkerator_add" action="<?= $plugin_url ?>" method="post" style="display: inline;">
		<input type="hidden" name="action" value="do_add" />
		<input type="hidden" name="id" value="<?= $id ?>" />
		<p>
			<strong>Trigger:</strong> <a href="#" onclick="jQuery('#trigger-help').toggle(); return false;" title="Click for help.">Help</a>
			<span id="trigger-help" style="font-size: 10px; font-style: italic; display: none;">
				The trigger is the text that will trigger the plugin to add a link.  The triggers are <strong>CaSe SeNsiTiVe</strong>.  Do not include a space before or after.
			</span><br />
			<input type="text" name="link_trig" value="<?= $row->trig ?>" />
		</p>
		<p>
			<strong>URL:</strong> <a href="#" onclick="jQuery('#url-help').toggle(); return false;" title="Click for help.">Help</a>
			<span id="url-help" style="font-size: 10px; font-style: italic; display: none;">
				The URL to add to the trigger.
			</span><br/>
			<input type="text" name="link_url" value="<?= $row->url ?>" />
		</p>
		<p>
			<strong>Class:</strong> <a href="#" onclick="jQuery('#class-help').toggle(); return false;" title="Click for help.">Help</a>
			<span id="class-help" style="font-size: 10px; font-style: italic; display: none;">
				These are equivalent to &lt;a class="{class name}"&gt;
			</span><br />
			<input type="text" name="link_class" value="<?= $row->class ?>" />
		</p>
		<p class="submit">
			<input type="button" name="link_submit" value="Save" class="button-primary" onclick="validateForm(forms.linkerator_add);" />
			<input type="button" class="button-primary" name="link_cancel" value="Cancel, go back." onclick="location.href='<?= $plugin_url ?>';" />
		</p>
	</form>
	<?php
}

/**
 * Main display menu
 **/
function linkerator_main($plugin_url)
{
	global $wpdb;

	if (!get_option('linkerator_url'))
		add_option('linkerator_url', $_SERVER['REQUEST_URI']);
		
	$table = get_option('linkerator_table');
	$paged = 0 + (int)$_GET['paged'];
	$rows_per_page = 20;
	if (!$paged)
		$paged = 1;
	
	if ($_REQUEST['action'] == 'do_add')
	{
		$id = ($_REQUEST['id'] == '') ? -1 : (int)$_REQUEST['id'];
		$trig = $_REQUEST['link_trig'];
		$url = $_REQUEST['link_url'];
		$class = ($_REQUEST['link_class'] == '') ? 'NULL' : "'" . $_REQUEST['link_class'] . "'";
		
		// add or edit depends on the id
		if ($id == -1)
		{
			$proceed = ($wpdb->query("SELECT 1 FROM $table WHERE trig='" . addslashes($trig) . "'") == 0) ? true : false;
			if ($proceed)
				$sql = "INSERT INTO $table VALUES (NULL, '" . addslashes($trig) . "', '$url', $class)";
		}
		else
		{
			// edit entry
			$sql = "UPDATE $table SET trig='" . addslashes($trig) . "', url='$url', class=$class WHERE id=$id LIMIT 1";
		}
		if ($proceed && $wpdb->query($sql))
		{
			// success
			?><div id="message" class="updated fade"><p>Entry added or updated.</p></div><?php
		}
		elseif ($proceed)
		{
			?><div id="message" class="error"><p>Failed to add or update the entry.</p></div><?php
		}
		else
		{
			?><div id="message" class="error"><p>The trigger "<?= $trig ?>" already exists.</p></div><?php
		}
	}
	elseif ($_REQUEST['action'] == 'mass_delete')
	{
		$success = $failed = 0;
		foreach ($_REQUEST['checked'] as $check)
		{
			$sql = "DELETE FROM $table WHERE id=$check LIMIT 1";
			if ($wpdb->query($sql))
				$success++;
			else
				$failed++;
		}
		
		if ($success > 0)
		{
			?><div id="message" class="updated fade"><p>Successfully removed <?= $success ?> entries.</p></div><?php
		}
		
		if ($failed > 0)
		{
			?><div id="message" class="error"><p>Failed to remove <?= $failed ?> entries.</p><?php
		}
	}
	elseif ($_REQUEST['action'] == 'delete')
	{
		$id = $_REQUEST['id'];
		$sql = "DELETE FROM $table WHERE id=$id LIMIT 1";
		
		if ($wpdb->query($sql))
		{
			?><div id="message" class="updated fade"><p>Successfully removed entry.</p></div><?php
		}
		else
		{
			?><div id="message" class="error"><p>Failed to remove entry.</p></div><?php
		}
	}
	elseif ($_REQUEST['action'] == 'do_import')
	{
		$checked = $_REQUEST['checked'];
		
		$success = $failed = 0;
		
		foreach ($checked as $entry)
		{
			$parts = explode('|', $entry);	// split by pipe again
			$class = ($parts[2] == '') ? 'NULL' : "'" . $parts[2] . "'";
			$sql = "INSERT INTO $table VALUES (NULL, '" . addslashes($parts[0]) . "', '" . $parts[1] . "', $class)";
			if ($wpdb->query($sql))
			{
				$success++;
			}
			else
			{
				$failed++;
			}
		}
		
		if ($success > 0)
		{
			?><div id="message" class="updated fade"><p>Successfully imported <?= $success ?> entries.</p></div><?php
		}
		
		if ($failed > 0)
		{
			?><div id="message" class="error"><p>Failed to import <?= $failed ?> entries.</p></div><?php
		}
	}
	elseif ($_REQUEST['action'] == 'export')
	{
		$path = get_option('linkerator_path');
		
		if (substr($path, strlen($path) - 1, 1) != '/')
			$path .= '/';
		
		if (!is_writable($path))
		{
			?><div id="message" class="error"><p>This plugin's directory <tt><?= $path ?></tt> is not writable.  CHMOD to 0777 or 0755.</p></div><?php
		}
		else
		{
			// if triggers.txt already exists then rename it
			if (file_exists($path . 'triggers.txt'))
			{
				if (!is_writable($path . 'triggers.txt'))
				{
					?><div id="message" class="error"><p>The file <tt>triggers.txt</tt> already exists, but is not writable.  CHMOD to 0777 or 0755.</p></div><?php
				}
				elseif (!@rename($path . 'triggers.txt', $path . 'triggers-old.txt'))
				{
					?><div id="message" class="error"><p>The file <tt>triggers.txt</tt> already exists, and I was not able to rename it.</p></div><?php
				}
				else
				{
					?><div id="message" class="updated fade"><p>The file <tt>triggers.txt</tt> already exists, was renamed to <tt>triggers-old.txt</tt>.</p></div><?php
				}
			}
			
			$entries = $wpdb->get_results("SELECT trig, url, class FROM $table");
			
			if (!$entries || sizeof($entries) == 0)
			{
				?><div id="message" class="error"><p>There are no triggers stored.</p></div><?php
			}
			else
			{
				$contents = '';
				
				// build each line of the txt file
				foreach ($entries as $entry)
				{
					$contents .= (string)$entry->trig . '|' . (string)$entry->url;
					if (isset($entry->class))
						$contents .= '|' . (string)$entry->class . chr(10);
					else
						$contents .= chr(10);
				}
				
				// now output the txt file
				if (!file_put_contents($path . 'triggers.txt', $contents))
				{
					?><div id="message" class="error"><p>Unable to export contents to <tt>triggers.txt</tt>.</p></div><?php
				}
				else
				{
					?><div id="message" class="updated fade"><p>Successfully exported entries to <tt>triggers.txt</tt>.</p></div><?php
				}
			}
		}
	}
	elseif ($_REQUEST['action'] == 'empty')
	{
		$sql = "TRUNCATE TABLE `$table`";
		$wpdb->query($sql)
		?><div id="message" class="updated fade"><p>All entries were removed.</p></div><?php
	}
	$count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
	$pages = ceil($count / $rows_per_page);
	$sql = "SELECT id, trig, url, class FROM $table ORDER BY trig ASC LIMIT " . ($paged - 1) * $rows_per_page . "," . $rows_per_page;	
	$triggers = $wpdb->get_results($sql);
	?>
	<h2>Manage Linkerator Entries</h2>
	<p>
		<ul class="subsubsub">
			<li><a href="<?= $plugin_url ?>&amp;action=add">Add Entry</a> | </li>
			<li><a href="<?= $plugin_url ?>&amp;action=import">Import</a> | </li>
			<li><a href="<?= $plugin_url ?>&amp;action=export">Export</a> | </li>
			<li><a href="#" onclick="confirmEmpty('<?= $plugin_url ?>&amp;action=empty');">Remove All</a></li>
		</ul>
	</p>
	<form name="linkerator_manage" action="<?= $plugin_url ?>&amp;paged=<?= $paged ?>" method="post">
		<input type="hidden" name="action" value="mass_delete" />
		<div class="tablenav">
			<div style="width: 50%; display: inline; float: left;">
				<input type="button" name="mass_delete" class="button-primary" value="Delete Selected" onclick="confirmMassDelete();" />
			</div>
			<div style="width: 50%; display: inline; float: right;">
				<?php
					$page_links = paginate_links(array('base' => add_query_arg("paged", "%#%"), 'format' => '', 'total' => $pages, 'current' => $paged));
					if ($page_links) echo "<div class=\"tablenav-pages\">$page_links</div>\n";
				?>	
			</div>
			<div class="alignleft"></div>
		</div>
		<table class="widefat">
			<thead>
				<tr>
					<th scope="col" class="manage-column check-column"><input type="checkbox" onclick="checkAll(this.checked);" /></th>
					<th scope="col" class="manage-column"><?php _e('Trigger'); ?></th>
					<th scope="col" class="manage-column"><?php _e('URL'); ?></th>
					<th scope="col" class="manage-column"><?php _e('Class'); ?></th>
					<th scope="col" class="manage-column"><?php _e('Action'); ?></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th scope="col" class="manage-column check-column"><input type="checkbox" onclick="checkAll(this.checked);" /></th>
					<th scope="col" class="manage-column"><?php _e('Trigger'); ?></th>
					<th scope="col" class="manage-column"><?php _e('URL'); ?></th>
					<th scope="col" class="manage-column"><?php _e('Class'); ?></th>
					<th scope="col" class="manage-column"><?php _e('Action'); ?></th>
				</tr>
			</tfoot>
			<tbody>
				<?php
				if (sizeof($triggers) == 0)
				{
					?><tr><td colspan="4"><em>No entries found.</em></td></tr><?php
				}
				else
				{
					foreach ($triggers as $trig)
					{
						$class = ((string)$trig->class == '') ? 'None Given' : $trig->class;
						$href = (linkerator_email_url((string)$trig->url) == 'email') ? 'mailto:' . (string)$trig->url : (string)$trig->url;
						?>
						<tr id="result-<?= $trig->id ?>">
							<td scope="row" class="checked-column"><input type="checkbox" name="checked[]" value="<?= $trig->id ?>" /></td>
							<td scope="row"><?= $trig->trig ?></td>
							<td scope="row"><a href="<?= $href ?>"><?= $trig->url ?></a></td>
							<td scope="row"><?= $class ?></td>
							<td scope="row">
								<a href="<?= $plugin_url ?>&amp;action=edit&amp;id=<?= $trig->id ?>">Edit</a>&nbsp;&nbsp;
								<a href="#" onclick="confirmDelete('<?= $plugin_url ?>&amp;action=delete&id=<?= $trig->id ?>');">Delete</a>
							</td>
						</tr>
						<?php
					}
				}
				?>
			</tbody>
		</table>
		<div class="tablenav">
			<div style="width: 50%; display: inline; float: left;">
				<input type="button" name="mass_delete" class="button-primary" value="Delete Selected" onclick="confirmMassDelete();" />
			</div>
			<div style="width: 50%; display: inline; float: right;">
				<?php
					$page_links = paginate_links(array('base' => add_query_arg("paged", "%#%"), 'format' => '', 'total' => $pages, 'current' => $paged));
					if ($page_links) echo "<div class=\"tablenav-pages\">$page_links</div>\n";
				?>	
			</div>
			<div class="alignleft"></div>
		</div>
	</form>
	<?php
}

/**
 * Validate e-mail address
 **/
function linkerator_valid_email($email)
{
	$isValid = true;
	$atIndex = strrpos($email, "@");
	if (is_bool($atIndex) && !$atIndex)
	{
		$isValid = false;
	}
	else
	{
		$domain = substr($email, $atIndex+1);
		$local = substr($email, 0, $atIndex);
		$localLen = strlen($local);
		$domainLen = strlen($domain);
		if ($localLen < 1 || $localLen > 64)
		{
			// local part length exceeded
			$isValid = false;
		}
		else if ($domainLen < 1 || $domainLen > 255)
		{
			// domain part length exceeded
			$isValid = false;
		}
		else if ($local[0] == '.' || $local[$localLen-1] == '.')
		{
			// local part starts or ends with '.'
			$isValid = false;
		}
		else if (preg_match('/\\.\\./', $local))
		{
			// local part has two consecutive dots
			$isValid = false;
		}
		else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
		{
			// character not valid in domain part
			$isValid = false;
		}
		else if (preg_match('/\\.\\./', $domain))
		{
			// domain part has two consecutive dots
			$isValid = false;
		}
		else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local)))
		{
			// character not valid in local part unless 
			// local part is quoted
			if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local)))
			{
				$isValid = false;
			}
		}
		if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
		{
			// domain not found in DNS
			$isValid = false;
		}
	}
	return $isValid;
}

/**
 * Checks for a valid URL
 **/
function linkerator_valid_url($str)
{
	// if cURL isn't installed then assume it is valid
	if (!function_exists('curl_init'))
		return true;
	
	// make sure http:, https:, or ftp: exists
	if (!preg_match('/http|ftp|https/i', $str))
		return false;
		
	// we'll use cURL to make sure the link exists
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $str);
	curl_setopt($curl, CURLOPT_HEADER, true);			// we want headers
	curl_setopt($curl, CURLOPT_NOBODY, true);			// do not output the body, headers only
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);		// we want the results in a variable, not printed
	$data = curl_exec($curl);						// send the request
	curl_close($curl);
	
	//die($data);
	// now we need to check if we got a 200 status code, meaning the URL exists
	preg_match('/HTTP\/1\.[1|0]\s(\d{3})/', $data, $match);
	
	if ($match[1] == '200')
		return true;
	else
		return false;
}

/**
 * Checks if string is an e-mail or url
 **/
function linkerator_email_url($in)
{
	if (empty($in))
		return false;
		
	if (linkerator_valid_email($in))
		return 'email';	// its an e-mail
	elseif (linkerator_valid_url($in))
		return 'url';		// its a url
	else
		return false;		// its neither
}

/**
 * Hooks and Filters
 **/
add_filter('the_content', 'linkerator_filter');
add_action('admin_menu', 'linkerator_admin');
register_activation_hook(__FILE__, 'linkerator_install');
register_deactivation_hook(__FILE__, 'linkerator_uninstall');
?>
