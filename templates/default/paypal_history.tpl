		<table id="listing">
			<thead>
				<tr>
					<td colspan="3" class="right">
						<form action="paypal_history.php" method="get" id="historyform">
							<span>
								<label for="nbshow">{_T string="Records per page:"}</label>
								<select name="nbshow" id="nbshow">
									{html_options options=$nbshow_options selected=$numrows}
								</select>
								<noscript> <span><input type="submit" value="{_T string="Change"}" /></span></noscript>
							</span>
						</form>
					</td>
				</tr>
				<tr>
					<th class="listing small_head">#</th>
					<th class="listing left date_row">
						<a href="?tri=date_log" class="listing">
							{_T string="Date"}
							{if $paypal_history->orderby eq "date_log"}
								{if $paypal_history->getDirection() eq "DESC"}
							<img src="{$template_subdir}images/down.png" width="10" height="6" alt="{_T string="Ascendent"}"/>
								{else}
							<img src="{$template_subdir}images/up.png" width="10" height="6" alt="{_T string="Descendant"}"/>
								{/if}
							{/if}
						</a>
					</th>
					<th class="listing left date_row">
						<a href="?tri=ip_log" class="listing">
							{_T string="Request"}
						</a>
					</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="3" class="center">
						{_T string="Pages:"}<br/>
						<ul class="pages">{$pagination}</ul>
					</td>
				</tr>
			</tfoot>
			<tbody>
{foreach from=$logs item=log name=eachlog}
				<tr class="cotis-never">
					<td class="center">{$smarty.foreach.eachlog.iteration}</td>
					<td class="nowrap">{$log.history_date|date_format:"%a %d/%m/%Y - %R"}</td>
					<td class="nowrap"><pre class="request">{$log.request}</pre></td>
				</tr>
{foreachelse}
				<tr><td colspan="3" class="emptylist">{_T string="logs are empty"}</td></tr>
{/foreach}
			</tbody>
		</table>
		<script type="text/javascript">
            $('#nbshow').change(function() {ldelim}
                this.form.submit();
            {rdelim});

            $(function() {ldelim}
                var _elt = $('<img src="../../templates/default/images/info.png" class="reqhide" alt="" title="{_T string="Show full request"}"/>');
                $('.request').hide().parent().prepend(_elt);
                $('.reqhide').click(function() {ldelim}
                    $(this).next('.request').show();
                {rdelim});
            {rdelim});
		</script>