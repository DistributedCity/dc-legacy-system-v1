<table border="0" width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr valign="top">
		<td class="sidebarLeft" valign="top">
			<?php if($content[left]) { include($content[left]); } ?>
		</td>
		<td class="content" valign="top">
			<?php if($content[center]) { include($content[center]); } ?>
		</td>
		<td class="sidebarRight" valign="top">
			<?php if($content[right]) { include($content[right]); } ?>
		</td>
	</tr>
	<tr>
		<td width="125" style="border-left: 1px #999 solid;">
			<img src="/images/master/spacer_clear.gif" width="125" height="1"> 
		</td>
		<td width="100%">
			<img src="/images/master/spacer_clear.gif" width="500" height="1"> 
		</td>
		<td width="125" style="border-right: 1px #999 solid;">
			<img src="/images/master/spacer_clear.gif" width="125" height="1"> 
		</td>
	</tr>
</table>