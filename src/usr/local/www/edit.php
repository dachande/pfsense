<?php
/*
	edit.php
*/
/* ====================================================================
 *  Copyright (c)  2004-2015  Electric Sheep Fencing, LLC. All rights reserved. 
 *  Copyright (c)  2004, 2005 Scott Ullrich
 *
 *  Redistribution and use in source and binary forms, with or without modification, 
 *  are permitted provided that the following conditions are met: 
 *
 *  1. Redistributions of source code must retain the above copyright notice,
 *      this list of conditions and the following disclaimer.
 *
 *  2. Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in
 *      the documentation and/or other materials provided with the
 *      distribution. 
 *
 *  3. All advertising materials mentioning features or use of this software 
 *      must display the following acknowledgment:
 *      "This product includes software developed by the pfSense Project
 *       for use in the pfSense software distribution. (http://www.pfsense.org/). 
 *
 *  4. The names "pfSense" and "pfSense Project" must not be used to
 *       endorse or promote products derived from this software without
 *       prior written permission. For written permission, please contact
 *       coreteam@pfsense.org.
 *
 *  5. Products derived from this software may not be called "pfSense"
 *      nor may "pfSense" appear in their names without prior written
 *      permission of the Electric Sheep Fencing, LLC.
 *
 *  6. Redistributions of any form whatsoever must retain the following
 *      acknowledgment:
 *
 *  "This product includes software developed by the pfSense Project
 *  for use in the pfSense software distribution (http://www.pfsense.org/).
  *
 *  THIS SOFTWARE IS PROVIDED BY THE pfSense PROJECT ``AS IS'' AND ANY
 *  EXPRESSED OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 *  IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 *  PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE pfSense PROJECT OR
 *  ITS CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 *  SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 *  NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 *  LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 *  HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
 *  STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 *  ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
 *  OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *  ====================================================================
 *
 */
/*
	pfSense_MODULE:	shell
*/

##|+PRIV
##|*IDENT=page-diagnostics-edit
##|*NAME=Diagnostics: Edit FIle
##|*DESCR=Allow access to the 'Diagnostics: Edit File' page.
##|*MATCH=edit.php*
##|*MATCH=browser.php*
##|*MATCH=filebrowser/browser.php*
##|-PRIV

$pgtitle = array(gettext("Diagnostics"), gettext("Edit file"));
require("guiconfig.inc");

if ($_POST['action']) {
	switch ($_POST['action']) {
		case 'load':
			if (strlen($_POST['file']) < 1) {
				print('|5|' . '<div class="alert alert-danger" role="alert">'.gettext("No file name specified").'</div>' . '|');
			} elseif (is_dir($_POST['file'])) {
				print('|4|' . '<div class="alert alert-danger" role="alert">' . gettext("Loading a directory is not supported") .'</div>' . '|');
			} elseif (!is_file($_POST['file'])) {
				print('|3|' . '<div class="alert alert-danger" role="alert">' . gettext("File does not exist or is not a regular file") . '</div>' . '|');
			} else {
				$data = file_get_contents(urldecode($_POST['file']));
				if ($data === false) {
					print('|1|' . '<div class="alert alert-danger" role="alert">' . gettext("Failed to read file") . '</div>' . '|');
				} else {
					$data = base64_encode($data);
					print("|0|{$_POST['file']}|{$data}|");
				}
			}
			exit;

		case 'save':
			if (strlen($_POST['file']) < 1) {
				print('|' . '<div class="alert alert-danger" role="alert">'.gettext("No file name specified").'</div>' . '|');
			} else {
				conf_mount_rw();
				$_POST['data'] = str_replace("\r", "", base64_decode($_POST['data']));
				$ret = file_put_contents($_POST['file'], $_POST['data']);
				conf_mount_ro();
				if ($_POST['file'] == "/conf/config.xml" || $_POST['file'] == "/cf/conf/config.xml") {
					if (file_exists("/tmp/config.cache")) {
						unlink("/tmp/config.cache");
					}
					disable_security_checks();
				}
				if ($ret === false) {
					print('|' . '<div class="alert alert-danger" role="alert">' . gettext("Failed to write file") . '</div>' . '|');
				} elseif ($ret != strlen($_POST['data'])) {
					print('|' . '<div class="alert alert-danger" role="alert">' . gettext("Error while writing file") . '</div>' . '|');
				} else {
					print('|' . '<div class="alert alert-success" role="alert">' . gettext("File saved successfully") . '</div>' . '|');
				}
			}
			exit;
	}
	exit;
}

require("head.inc");
?>
<!-- file status box -->
<div style="display:none; background:#eeeeee;" id="fileStatusBox">
		<strong id="fileStatus"></strong>
</div>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext("Save / Load a file from the filesystem")?></h2></div>
	<div class="panel-body">
		<form>
			<input type="text" class="form-control" id="fbTarget"/>
			<input type="button" class="btn btn-default btn-sm"	  onclick="loadFile();" value="<?=gettext('Load')?>" />
			<input type="button" class="btn btn-default btn-sm"	  id="fbOpen"		   value="<?=gettext('Browse')?>" />
			<input type="button" class="btn btn-default btn-sm"	  onclick="saveFile();" value="<?=gettext('Save')?>" />
		</form>

		<div id="fbBrowser" style="display:none; border:1px dashed gray; width:98%;"></div>

		<div style="background:#eeeeee;" id="fileOutput">
			<script type="text/javascript">
			//<![CDATA[
			window.onload=function(){
				document.getElementById("fileContent").wrap='off';
			}
			//]]>
			</script>
			<textarea id="fileContent" name="fileContent" class="form-control" rows="30" cols=""></textarea>
		</div>

	</div>
</div>

<script>
	function loadFile() {
		jQuery("#fileStatus").html("");
		jQuery("#fileStatusBox").show(500);
		jQuery.ajax(
			"<?=$_SERVER['SCRIPT_NAME']?>", {
				type: "post",
				data: "action=load&file=" + jQuery("#fbTarget").val(),
				complete: loadComplete
			}
		);
	}

	function loadComplete(req) {
		jQuery("#fileContent").show(1000);
		var values = req.responseText.split("|");
		values.shift(); values.pop();

		if (values.shift() == "0") {
			var file = values.shift();
			var fileContent = window.atob(values.join("|"));

			jQuery("#fileContent").val(fileContent);
		}
		else {
			jQuery("#fileStatus").html(values[0]);
			jQuery("#fileContent").val("");
		}

		jQuery("#fileContent").show(1000);
	}

	function saveFile(file) {
		jQuery("#fileStatus").html("");
		jQuery("#fileStatusBox").show(500);

		var fileContent = Base64.encode(jQuery("#fileContent").val());
		fileContent = fileContent.replace(/\+/g, "%2B");

		jQuery.ajax(
			"<?=$_SERVER['SCRIPT_NAME']?>", {
				type: "post",
				data: "action=save&file=" + jQuery("#fbTarget").val() +
							"&data=" + fileContent,
				complete: function(req) {
					var values = req.responseText.split("|");
					jQuery("#fileStatus").html(values[1]);
				}
			}
		);
	}

	<?php if ($_GET['action'] == "load"): ?>
		events.push(function() {
			jQuery("#fbTarget").val("<?=htmlspecialchars($_GET['path'])?>");
			loadFile();
		});
	<?php endif; ?>
</script>

<?php include("foot.inc");

outputJavaScriptFileInline("filebrowser/browser.js");