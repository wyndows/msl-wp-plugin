
<br>
<p><span class="form_header">WP Json Settings Page</span></p>
<p>After making your choices, please click <span class="form_highlight">Submit</span>, then wait until the settings page resets to '1' and 'News' respectively (1-2 seconds).  Click on <span class="form_highlight">Display</span> under <span class="form_highlight">WP Json</span> plugin name to view chosen posts.</p>

<!-- The form for setting user choices -->
<form  action='' method='post'>
	<table width='450px'>
		<tr>
			<td valign='top'>
				<label for='number of posts'>Number of Posts </label>
			</td>
			<td valign='top'>
				<input  type='number' name='number_of_posts' value='1' min='1' max='<?php echo $posts; ?>' size='1'>
			</td>
		</tr>

		<tr>
			<td valign='top'>
				<label for='post category'>Post Category </label>
			</td>
			<td valign='top'>
				<select name='post_category'>
					<option value='News'>News</option>
					<option value='Code'>Code</option>
					<option value='Downloads'>Downloads</option>
				</select>
			</td>
		</tr>

		<tr>
			<td valign='top'>
				<input  type='submit' value='Submit'>
			</td>
		</tr>
	</table>
</form>