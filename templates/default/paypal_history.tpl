{extends file="page.tpl"}
{block name="content"}
        <table class="listing">
            <thead>
                <tr>
                    <td colspan="6" class="right">
                        <form action="{path_for name="filter_paypal_history"}" method="post" id="historyform">
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
                    <th class="small_head">#</th>
                    <th class="left">
                        <a href="{path_for name="paypal_history" data=["option" => {_T string='order' domain="routes"}, "value" => "Galette\Filters\HistoryList::ORDERBY_DATE"|constant]}">
                            {_T string="Date"}
                            {if $paypal_history->filters->orderby eq constant('Galette\Filters\HistoryList::ORDERBY_DATE')}
                                {if $paypal_history->filters->getDirection() eq constant('Galette\Filters\HistoryList::ORDER_ASC')}
                            <img src="{base_url}/{$template_subdir}images/down.png" width="10" height="6" alt="{_T string="Ascendent"}"/>
                                {else}
                            <img src="{base_url}/{$template_subdir}images/up.png" width="10" height="6" alt="{_T string="Descendant"}"/>
                                {/if}
                            {/if}
                        </a>
                    </th>
                    <th>
                        {_T string="Name"}
                    </th>
                    <th>
                        {_T string="Subject"}
                    </th>
                    <th>
                        {_T string="Amount"}
                    </th>
                    <th class="left actions_row"></th>
                </tr>
            </thead>
            <tbody>
{foreach from=$logs item=log name=eachlog}
                <tr class="{if $smarty.foreach.eachlog.iteration % 2 eq 0}even{else}odd{/if}">
                    <td class="center" data-scope="row">
                        {$smarty.foreach.eachlog.iteration}
                        <span class="row-title">
                            {_T string="History entry %id" pattern="/%id/" replace=$smarty.foreach.eachlog.iteration}
                        </span>
                    </td>
                    <td class="nowrap" data-title="{_T string="Data"}">
                        {if isset($log.duplicate)}<img class="img-dup" src="{path_for name="plugin_res" data=["plugin" => $module_id, "path" => "images/warning.png"]}" alt="{_T string="duplicate" domain="paypal"}"/>{/if}
                        {$log.history_date|date_format:"%a %d/%m/%Y - %R"}
                    </td>
                    <td data-title="{_T string="Name"}">
    {if is_array($log.request)}
        {if isset($log.request.custom)}
                        <a href="{path_for name="member" data=["id" => $log.request.custom]}">
        {/if}
                        {$log.request.last_name} {$log.request.first_name}
        {if isset($log.request.custom)}
                        </a>
        {/if}
    {else}
        {_T string="No request or unable to read request" domain="paypal"}
    {/if}
                    </td>
                    <td data-title="{_T string="Subject"}">
    {if is_array($log.request)}
                        {$log.request.item_name}
    {/if}
                    </td>
                    <td data-title="{_T string="Amount"}">
    {if is_array($log.request)}
                        {$log.request.mc_gross}
    {/if}
                    </td>
                    <td class="nowrap info"></td>
                </tr>
                <tr class="request tbl_line_{if $smarty.foreach.eachlog.iteration % 2 eq 0}even{else}odd{/if}">
                    <th colspan="2">{_T string="Request" domain="paypal"}</th>
                    <td colspan="4"><pre>{$log.raw_request}</pre></td>
                </tr>
{foreachelse}
                <tr><td colspan="6" class="emptylist">{_T string="logs are empty"}</td></tr>
{/foreach}
            </tbody>
        </table>
{if $logs|@count != 0}
        <div class="center cright">
            {_T string="Pages:"}<br/>
            <ul class="pages">{$pagination}</ul>
        </div>
{/if}
{/block}

{block name="javascripts"}
        <script type="text/javascript">
            $('#nbshow').change(function() {
                this.form.submit();
            });

            $(function() {
                var _elt = $('<img src="{path_for name="plugin_res" data=["plugin" => $module_id, "path" => "{$template_subdir}images/info.png"]}" class="reqhide" alt="" title="{_T string="Show/hide full request" domain="paypal"}"/>');
                $('.request').hide().prev('tr').find('td.info').prepend(_elt);
                $('.reqhide').click(function() {
                    $(this).parents('tr').next('.request').toggle();
                });
            });
        </script>
{/block}
