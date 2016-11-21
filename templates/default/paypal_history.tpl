{extends file="page.tpl"}
{block name="content"}
        <table class="listing">
            <thead>
                <tr>
                    <td colspan="6" class="right">
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
                    <th class="small_head">#</th>
                    <th class="left">
                        <a href="?tri=date_log">
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
            <tfoot>
                <tr>
                    <td colspan="6" class="center">
                        {_T string="Pages:"}<br/>
                        <ul class="pages">{$pagination}</ul>
                    </td>
                </tr>
            </tfoot>
            <tbody>
{foreach from=$logs item=log name=eachlog}
                <tr class="{if $smarty.foreach.eachlog.iteration % 2 eq 0}even{else}odd{/if}">
                    <td class="center">{$smarty.foreach.eachlog.iteration}</td>
                    <td class="nowrap big_date_row">
                        {if isset($log.duplicate)}<img class="img-dup" src="{path_for name="plugin_res" data=["plugin" => $module_id, "path" => "images/warning.png"]}" alt="{_T string="duplicate" domain="paypal"}"/>{/if}
                        {$log.history_date|date_format:"%a %d/%m/%Y - %R"}
                    </td>
                    <td>
    {if $log.request.custom}
                        <a href="{$galette_base_path}voir_adherent.php?id_adh={$log.request.custom}">
    {/if}
                        {$log.request.last_name} {$log.request.first_name}
    {if isset($log.request.transaction_subject)}
                        </a>
    {/if}
                    </td>
                    <td>
                        {$log.request.item_name}
                    </td>
                    <td>{$log.request.mc_gross}</td>
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
{/block}

{block name="javascripts"}
        <script type="text/javascript">
            $('#nbshow').change(function() {
                this.form.submit();
            });

            $(function() {
                var _elt = $('<img src="{path_for name="plugin_res" data=["plugin" => $module_id, "path" => "images/info.png"]}" class="reqhide" alt="" title="{_T string="Show/hide full request" domain="paypal"}"/>');
                $('.request').hide().prev('tr').find('td.info').prepend(_elt);
                $('.reqhide').click(function() {
                    $(this).parents('tr').next('.request').toggle();
                });
            });
        </script>
{/block}
